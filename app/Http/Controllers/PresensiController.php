<?php

namespace App\Http\Controllers;

use App\Models\DetailPekerjaan;
use App\Models\JadwalPekerjaan;
use App\Models\Presensi;
use App\Models\Setting;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PresensiController extends Controller
{
    /**
     * UC-07: Melakukan Presensi Masuk (Check-in) - Hanya di kantor pusat
     */
    public function checkIn(Request $request): JsonResponse
    {
        $request->validate([
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'foto_masuk' => 'nullable|image', // ukuran sengaja tidak dibatasi
            'foto_masuk_base64' => 'nullable|string',
        ]);

        if (!$request->hasFile('foto_masuk') && !$request->filled('foto_masuk_base64')) {
            return response()->json([
                'success' => false,
                'message' => 'Foto presensi wajib diunggah atau diambil via kamera',
            ], 422);
        }

        $user = Auth::user();

        $today = Carbon::now()->toDateString();

        // Cek jadwal hari ini (kalau hari libur, presensi ditolak)
        $jadwal = JadwalPekerjaan::getJadwalHarian($user->id, $today);
        if ($jadwal && $jadwal->isHariLibur()) {
            return response()->json([
                'success' => false,
                'message' => 'Hari ini ditandai sebagai hari libur. Presensi tidak diperlukan.',
            ], 422);
        }

        // Cek sudah check-in hari ini
        $existingPresensi = Presensi::where('user_id', $user->id)
            ->whereDate('tanggal', $today)
            ->first();

        if ($existingPresensi && $existingPresensi->jam_masuk) {
            return response()->json([
                'success' => false,
                'message' => 'Anda sudah melakukan presensi masuk hari ini',
            ], 422);
        }

        // Validasi GPS terhadap kantor pusat (Dinamis dari Setting)
        $userLat = $request->latitude;
        $userLon = $request->longitude;

        $kantorLat = Setting::get('kantor_lat', -6.2087634);
        $kantorLng = Setting::get('kantor_lng', 106.8222568);
        $radius = Setting::get('kantor_radius', 500);

        $distance = $this->calculateDistance($userLat, $userLon, $kantorLat, $kantorLng);
        if ($distance > $radius) {
            return response()->json([
                'success' => false,
                'message' => 'Lokasi di luar area kantor pusat (Jarak: ' . round($distance, 2) . 'm)',
            ], 422);
        }

        // Hitung keterlambatan (toleransi dari Setting)
        $now = Carbon::now();
        $jamKerja = $jadwal?->jam_masuk ?? '08:00:00';
        $jamMasukJadwal = Carbon::parse($today . ' ' . $jamKerja);
        $toleransi = (int) Setting::get('toleransi_menit', 10);

        $menitTerlambat = 0;
        $potonganTerlambat = 0;

        if ($now->greaterThan($jamMasukJadwal->copy()->addMinutes($toleransi))) {
            $statusPresensi = 'terlambat';
            $menitTerlambat = (int) $jamMasukJadwal->diffInMinutes($now);
            $potonganTerlambat = Presensi::hitungPotongan($menitTerlambat);
        } else {
            $statusPresensi = 'hadir';
        }

        // Handle photo upload
        $fotoMasukPath = null;
        if ($request->hasFile('foto_masuk')) {
            $fotoMasukPath = $request->file('foto_masuk')->store('presensi/masuk', 'public');
        } elseif ($request->filled('foto_masuk_base64')) {
            $imageParts = explode(";base64,", $request->foto_masuk_base64);
            $imageBase64 = base64_decode($imageParts[1] ?? $imageParts[0]);
            $fileName = 'presensi/masuk/' . uniqid() . '.jpg';
            \Illuminate\Support\Facades\Storage::disk('public')->put($fileName, $imageBase64);
            $fotoMasukPath = $fileName;
        }

        $payload = [
            'jadwal_id' => $jadwal?->id,
            'jam_masuk' => $now,
            'foto_masuk' => $fotoMasukPath,
            'latitude_masuk' => $request->latitude,
            'longitude_masuk' => $request->longitude,
            'status_presensi' => $statusPresensi,
            'menit_terlambat' => $menitTerlambat,
            'potongan_terlambat' => $potonganTerlambat,
        ];

        if ($existingPresensi) {
            $existingPresensi->update(array_filter($payload, fn ($v) => $v !== null) + [
                'foto_masuk' => $fotoMasukPath ?? $existingPresensi->foto_masuk,
            ]);
            $presensi = $existingPresensi;
        } else {
            $presensi = Presensi::create([
                'user_id' => $user->id,
                'tanggal' => $today,
            ] + $payload);
        }

        return response()->json([
            'success' => true,
            'data' => $presensi,
            'status' => $statusPresensi,
            'message' => 'Presensi masuk berhasil dicatat (' . ucfirst($statusPresensi) . ')',
        ]);
    }

    /**
     * UC-08: Melakukan Presensi Pulang (Check-out)
     */
    public function checkOut(Request $request): JsonResponse
    {
        $user = Auth::user();

        $today = Carbon::now()->toDateString();
        $presensi = Presensi::where('user_id', $user->id)
            ->whereDate('tanggal', $today)
            ->firstOrFail();

        if (!$presensi->jam_masuk) {
            return response()->json([
                'success' => false,
                'message' => 'Anda belum melakukan presensi masuk hari ini',
            ], 422);
        }

        if ($presensi->jam_keluar) {
            return response()->json([
                'success' => false,
                'message' => 'Anda sudah melakukan presensi pulang hari ini',
            ], 422);
        }

        // Handle foto keluar (opsional)
        $fotoKeluarPath = null;
        if ($request->hasFile('foto_keluar')) {
            $fotoKeluarPath = $request->file('foto_keluar')->store('presensi/keluar', 'public');
        } elseif ($request->filled('foto_keluar_base64')) {
            $imageParts = explode(";base64,", $request->foto_keluar_base64);
            $imageBase64 = base64_decode($imageParts[1] ?? $imageParts[0]);
            $fileName = 'presensi/keluar/' . uniqid() . '.jpg';
            \Illuminate\Support\Facades\Storage::disk('public')->put($fileName, $imageBase64);
            $fotoKeluarPath = $fileName;
        }

        $now = Carbon::now();

        $presensi->update([
            'jam_keluar' => $now,
            'foto_keluar' => $fotoKeluarPath,
            'latitude_keluar' => $request->latitude,
            'longitude_keluar' => $request->longitude,
        ]);

        return response()->json([
            'success' => true,
            'data' => $presensi,
            'message' => 'Presensi pulang berhasil dicatat',
        ]);
    }

    /**
     * UC: Upload Bukti Pekerjaan per Tugas
     */
    public function submitBuktiPekerjaan(Request $request): JsonResponse
    {
        $request->validate([
            'detail_pekerjaan_id' => 'required|exists:tb_detail_pekerjaan,id',
            'foto_before' => 'nullable|image', // ukuran sengaja tidak dibatasi
            'foto_after' => 'nullable|image',  // ukuran sengaja tidak dibatasi
            'foto_before_base64' => 'nullable|string',
            'foto_after_base64' => 'nullable|string',
            'keterangan' => 'nullable|string',
        ]);

        if ((!$request->hasFile('foto_before') && !$request->filled('foto_before_base64')) ||
            (!$request->hasFile('foto_after') && !$request->filled('foto_after_base64'))) {
            return response()->json([
                'success' => false,
                'message' => 'Kedua foto (Before & After) wajib diunggah',
            ], 422);
        }

        $user = Auth::user();

        // Validasi bahwa detail pekerjaan ini memang milik user ini
        $detailPekerjaan = DetailPekerjaan::where('id', $request->detail_pekerjaan_id)
            ->where('user_id', $user->id)
            ->firstOrFail();

        // Handle foto upload
        $fotoBeforePath = null;
        if ($request->hasFile('foto_before')) {
            $fotoBeforePath = $request->file('foto_before')->store('bukti_pekerjaan/before', 'public');
        } elseif ($request->filled('foto_before_base64')) {
            $imageParts = explode(";base64,", $request->foto_before_base64);
            $imageBase64 = base64_decode($imageParts[1] ?? $imageParts[0]);
            $fileName = 'bukti_pekerjaan/before/' . uniqid() . '.jpg';
            \Illuminate\Support\Facades\Storage::disk('public')->put($fileName, $imageBase64);
            $fotoBeforePath = $fileName;
        }

        $fotoAfterPath = null;
        if ($request->hasFile('foto_after')) {
            $fotoAfterPath = $request->file('foto_after')->store('bukti_pekerjaan/after', 'public');
        } elseif ($request->filled('foto_after_base64')) {
            $imageParts = explode(";base64,", $request->foto_after_base64);
            $imageBase64 = base64_decode($imageParts[1] ?? $imageParts[0]);
            $fileName = 'bukti_pekerjaan/after/' . uniqid() . '.jpg';
            \Illuminate\Support\Facades\Storage::disk('public')->put($fileName, $imageBase64);
            $fotoAfterPath = $fileName;
        }

        $bukti = \App\Models\BuktiPekerjaan::create([
            'detail_pekerjaan_id' => $detailPekerjaan->id,
            'user_id' => $user->id,
            'foto_before' => $fotoBeforePath,
            'foto_after' => $fotoAfterPath,
            'keterangan' => $request->keterangan,
            'status' => 'pending',
            'uploaded_at' => Carbon::now(),
        ]);

        return response()->json([
            'success' => true,
            'data' => $bukti,
            'message' => 'Bukti pekerjaan berhasil diupload',
        ]);
    }

    /**
     * UC-11: Validasi Lokasi GPS (terhadap kantor pusat)
     */
    public function validateGPS(Request $request): JsonResponse
    {
        $request->validate([
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
        ]);

        $kantorLat = Setting::get('kantor_lat', -6.2087634);
        $kantorLng = Setting::get('kantor_lng', 106.8222568);
        $radius = Setting::get('kantor_radius', 500);

        $distance = $this->calculateDistance(
            $request->latitude,
            $request->longitude,
            $kantorLat,
            $kantorLng
        );

        if ($distance <= $radius) {
            return response()->json([
                'success' => true,
                'valid' => true,
                'location' => Setting::get('nama_perusahaan', 'Kantor Pusat'),
                'distance' => round($distance, 2),
                'message' => 'Lokasi valid, boleh presensi',
            ]);
        }

        return response()->json([
            'success' => false,
            'valid' => false,
            'message' => 'Lokasi Anda di luar area kantor pusat (Jarak: ' . round($distance, 2) . 'm)',
        ], 422);
    }

    /**
     * UC-10: Melihat Jadwal Kerja (Karyawan view)
     */
    public function viewSchedule(Request $request): JsonResponse
    {
        $user = Auth::user();

        $schedules = JadwalPekerjaan::where('user_id', $user->id)
            ->where('tanggal_kerja', '>=', Carbon::now()->startOfDay())
            ->orderBy('tanggal_kerja', 'asc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $schedules,
            'message' => 'Jadwal kerja berhasil diambil',
        ]);
    }

    /**
     * UC-12: Melihat Riwayat Presensi (Karyawan view)
     */
    public function viewHistory(Request $request): JsonResponse
    {
        $request->validate([
            'bulan' => 'nullable|date_format:Y-m',
            'limit' => 'nullable|integer|max:100',
        ]);

        $user = Auth::user();

        $query = Presensi::where('user_id', $user->id);

        if ($request->bulan) {
            $query->whereYear('tanggal', substr($request->bulan, 0, 4))
                  ->whereMonth('tanggal', substr($request->bulan, 5, 2));
        }

        $history = $query->orderBy('tanggal', 'desc')
            ->limit($request->limit ?? 30)
            ->get();

        $summary = [
            'total_hadir' => $history->where('status_presensi', 'hadir')->count(),
            'total_terlambat' => $history->where('status_presensi', 'terlambat')->count(),
            'total_tidak_hadir' => $history->where('status_presensi', 'tidak_hadir')->count(),
            'total_izin' => $history->where('status_presensi', 'izin')->count(),
        ];

        return response()->json([
            'success' => true,
            'data' => $history,
            'summary' => $summary,
            'message' => 'Riwayat presensi berhasil diambil',
        ]);
    }

    /**
     * UC-03 & UC-04: Monitoring Kehadiran Real-Time (supervisor & admin)
     */
    public function getCurrentAttendance(Request $request): JsonResponse
    {
        $today = Carbon::now()->toDateString();
        $presensis = Presensi::where('tanggal', $today)
            ->with(['user'])
            ->get();

        $summary = [
            'hadir' => $presensis->where('status_presensi', 'hadir')->count(),
            'terlambat' => $presensis->where('status_presensi', 'terlambat')->count(),
            'tidak_hadir' => $presensis->where('status_presensi', 'tidak_hadir')->count(),
            'izin' => $presensis->where('status_presensi', 'izin')->count(),
            'menunggu_verifikasi' => $presensis->where('status_verifikasi', 'pending')->count(),
        ];

        return response()->json([
            'success' => true,
            'date' => $today,
            'data' => $presensis,
            'summary' => $summary,
            'message' => 'Data kehadiran real-time berhasil diambil',
        ]);
    }

    /**
     * UC-09: Upload Foto Presensi (standalone endpoint)
     */
    public function uploadFoto(Request $request): JsonResponse
    {
        $request->validate([
            'presensi_id' => 'required|exists:tb_presensi,id',
            'tipe' => 'required|in:masuk,keluar',
            'foto' => 'required|image', // ukuran sengaja tidak dibatasi
        ]);

        $user = Auth::user();

        $presensi = Presensi::where('id', $request->presensi_id)
            ->where('user_id', $user->id)
            ->firstOrFail();

        $fotoPath = $request->file('foto')->store(
            "presensi/{$request->tipe}",
            'public'
        );

        $field = $request->tipe === 'masuk' ? 'foto_masuk' : 'foto_keluar';
        $presensi->update([$field => $fotoPath]);

        return response()->json([
            'success' => true,
            'data' => $presensi,
            'path' => $fotoPath,
            'message' => 'Foto presensi berhasil diupload',
        ]);
    }

    /**
     * Internal helper: Calculate distance (Haversine)
     */
    private function calculateDistance(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadius = 6371000;
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        $a = sin($dLat / 2) * sin($dLat / 2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($dLon / 2) * sin($dLon / 2);
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        return $earthRadius * $c;
    }
}
