<?php

namespace App\Http\Controllers;

use App\Models\Jadwal_kerja;
use App\Models\Karyawan;
use App\Models\Lokasi_gps;
use App\Models\Presensi;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PresensiController extends Controller
{
    /**
     * UC-10: Melihat Jadwal Kerja (Karyawan view)
     */
    public function viewSchedule(Request $request): JsonResponse
    {
        $user = Auth::user();
        $karyawan = Karyawan::where('user_id', $user->id)->firstOrFail();
        
        $schedules = Jadwal_kerja::where('karyawan_id', $karyawan->id)
            ->where('tanggal', '>=', Carbon::now()->startOfDay())
            ->orderBy('tanggal', 'asc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $schedules,
            'message' => 'Jadwal kerja berhasil diambil',
        ]);
    }

    /**
     * UC-11: Validasi Lokasi GPS
     * Check apakah lokasi karyawan sesuai dengan lokasi kerja yang diizinkan
     */
    public function validateGPS(Request $request): JsonResponse
    {
        $request->validate([
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
        ]);

        $user = Auth::user();
        $karyawan = Karyawan::where('user_id', $user->id)->firstOrFail();

        $today = Carbon::now()->toDateString();
        $jadwal = Jadwal_kerja::where('karyawan_id', $karyawan->id)
            ->where('tanggal', $today)
            ->first();

        if (!$jadwal) {
            return response()->json([
                'success' => false,
                'valid' => false,
                'message' => 'Tidak ada jadwal kerja hari ini',
            ], 400);
        }

        // Get all allowed GPS locations
        $allowedLocations = Lokasi_gps::all();

        $userLat = $request->latitude;
        $userLon = $request->longitude;

        foreach ($allowedLocations as $location) {
            $distance = $this->calculateDistance(
                $userLat,
                $userLon,
                $location->latitude,
                $location->longitude
            );

            if ($distance <= $location->radius_meter) {
                return response()->json([
                    'success' => true,
                    'valid' => true,
                    'location' => $location->nama_lokasi,
                    'distance' => round($distance, 2),
                    'message' => 'Lokasi valid, boleh presensi',
                ]);
            }
        }

        return response()->json([
            'success' => false,
            'valid' => false,
            'message' => 'Lokasi Anda di luar area kerja yang diizinkan',
        ], 422);
    }

    /**
     * UC-07: Melakukan Presensi Masuk (Check-in)
     */
    public function checkIn(Request $request): JsonResponse
    {
        $request->validate([
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'foto_masuk' => 'nullable|image|max:5120', // max 5MB
        ]);

        $user = Auth::user();
        $karyawan = Karyawan::where('user_id', $user->id)->firstOrFail();

        // Validate GPS location
        $gpsValidation = $this->validateGPSInternal(
            $request->latitude,
            $request->longitude
        );

        if (!$gpsValidation['valid']) {
            return response()->json([
                'success' => false,
                'message' => $gpsValidation['message'],
            ], 422);
        }

        $today = Carbon::now()->toDateString();
        
        // Check if already checked in today
        $existingPresensi = Presensi::where('karyawan_id', $karyawan->id)
            ->where('tgl_presensi', $today)
            ->first();

        if ($existingPresensi && $existingPresensi->jam_masuk) {
            return response()->json([
                'success' => false,
                'message' => 'Anda sudah melakukan presensi masuk hari ini',
            ], 422);
        }

        $jadwal = Jadwal_kerja::where('karyawan_id', $karyawan->id)
            ->where('tanggal', $today)
            ->firstOrFail();

        $lokasiGps = Lokasi_gps::where('nama_lokasi', $gpsValidation['location'])->first();
        $now = Carbon::now();
        $jamMasukJadwal = Carbon::parse($today . ' ' . $jadwal->jam_masuk);
        $status = $now->greaterThan($jamMasukJadwal) ? 'terlambat' : 'hadir';

        // Handle photo upload
        $fotoMasukPath = null;
        if ($request->hasFile('foto_masuk')) {
            $fotoMasukPath = $request->file('foto_masuk')->store('presensi/masuk', 'public');
        }

        if ($existingPresensi) {
            // Update existing record
            $existingPresensi->update([
                'jam_masuk' => $now,
                'foto_masuk' => $fotoMasukPath ?? $existingPresensi->foto_masuk,
                'status' => $status,
                'lokasi_gps_id' => $lokasiGps->id,
                'jadwal_kerja_id' => $jadwal->id,
            ]);
            $presensi = $existingPresensi;
        } else {
            // Create new record
            $presensi = Presensi::create([
                'karyawan_id' => $karyawan->id,
                'jadwal_kerja_id' => $jadwal->id,
                'lokasi_gps_id' => $lokasiGps->id,
                'tgl_presensi' => $today,
                'jam_masuk' => $now,
                'status' => $status,
                'foto_masuk' => $fotoMasukPath,
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => $presensi,
            'status' => $status,
            'message' => 'Presensi masuk berhasil dicatat (' . ucfirst($status) . ')',
        ]);
    }

    /**
     * UC-08: Melakukan Presensi Pulang (Check-out)
     */
    public function checkOut(Request $request): JsonResponse
    {
        $request->validate([
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'foto_keluar' => 'nullable|image|max:5120',
        ]);

        $user = Auth::user();
        $karyawan = Karyawan::where('user_id', $user->id)->firstOrFail();

        // Validate GPS
        $gpsValidation = $this->validateGPSInternal(
            $request->latitude,
            $request->longitude
        );

        if (!$gpsValidation['valid']) {
            return response()->json([
                'success' => false,
                'message' => $gpsValidation['message'],
            ], 422);
        }

        $today = Carbon::now()->toDateString();
        $presensi = Presensi::where('karyawan_id', $karyawan->id)
            ->where('tgl_presensi', $today)
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

        // Handle photo upload
        $fotoKeluarPath = null;
        if ($request->hasFile('foto_keluar')) {
            $fotoKeluarPath = $request->file('foto_keluar')->store('presensi/keluar', 'public');
        }

        $now = Carbon::now();
        $jamMasuk = Carbon::parse($presensi->jam_masuk);
        $durasiMenit = $jamMasuk->diffInMinutes($now);

        $presensi->update([
            'jam_keluar' => $now,
            'foto_keluar' => $fotoKeluarPath,
            'durasi_menit' => $durasiMenit,
        ]);

        return response()->json([
            'success' => true,
            'data' => $presensi,
            'durasi_menit' => $durasiMenit,
            'message' => 'Presensi pulang berhasil dicatat',
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
            'foto' => 'required|image|max:5120',
        ]);

        $user = Auth::user();
        $karyawan = Karyawan::where('user_id', $user->id)->firstOrFail();

        $presensi = Presensi::where('id', $request->presensi_id)
            ->where('karyawan_id', $karyawan->id)
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
     * UC-12: Melihat Riwayat Presensi (Karyawan view)
     */
    public function viewHistory(Request $request): JsonResponse
    {
        $request->validate([
            'bulan' => 'nullable|date_format:Y-m',
            'limit' => 'nullable|integer|max:100',
        ]);

        $user = Auth::user();
        $karyawan = Karyawan::where('user_id', $user->id)->firstOrFail();

        $query = Presensi::where('karyawan_id', $karyawan->id)
            ->with(['jadwalKerja', 'lokasiGps', 'verifikasi']);

        // Filter by month if provided
        if ($request->bulan) {
            $query->whereYear('tgl_presensi', substr($request->bulan, 0, 4))
                  ->whereMonth('tgl_presensi', substr($request->bulan, 5, 2));
        }

        $history = $query->orderBy('tgl_presensi', 'desc')
            ->limit($request->limit ?? 30)
            ->get();

        $summary = [
            'total_hadir' => $history->where('status', 'hadir')->count(),
            'total_terlambat' => $history->where('status', 'terlambat')->count(),
            'total_tidak_hadir' => $history->where('status', 'tidak_hadir')->count(),
            'total_izin' => $history->where('status', 'izin')->count(),
        ];

        return response()->json([
            'success' => true,
            'data' => $history,
            'summary' => $summary,
            'message' => 'Riwayat presensi berhasil diambil',
        ]);
    }

    /**
     * UC-03 & UC-04: Monitoring Kehadiran Real-Time & Laporan Presensi
     * Get current attendance status (untuk supervisor & admin)
     */
    public function getCurrentAttendance(Request $request): JsonResponse
    {
        $user = Auth::user();
        
        $today = Carbon::now()->toDateString();
        $presensis = Presensi::where('tgl_presensi', $today)
            ->with(['karyawan', 'jadwalKerja', 'lokasiGps', 'verifikasi'])
            ->get();

        $summary = [
            'hadir' => $presensis->where('status', 'hadir')->count(),
            'terlambat' => $presensis->where('status', 'terlambat')->count(),
            'tidak_hadir' => $presensis->where('status', 'tidak_hadir')->count(),
            'izin' => $presensis->where('status', 'izin')->count(),
            'menunggu_verifikasi' => $presensis->whereNull('verifikasi')->count(),
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
     * Internal helper: Calculate distance between two GPS points using Haversine formula
     */
    private function calculateDistance(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadius = 6371000; // meters

        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat / 2) * sin($dLat / 2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($dLon / 2) * sin($dLon / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }

    /**
     * Internal helper: Validate GPS location
     */
    private function validateGPSInternal(float $latitude, float $longitude): array
    {
        $allowedLocations = Lokasi_gps::all();

        foreach ($allowedLocations as $location) {
            $distance = $this->calculateDistance(
                $latitude,
                $longitude,
                $location->latitude,
                $location->longitude
            );

            if ($distance <= $location->radius_meter) {
                return [
                    'valid' => true,
                    'location' => $location->nama_lokasi,
                    'distance' => round($distance, 2),
                ];
            }
        }

        return [
            'valid' => false,
            'message' => 'Lokasi di luar area kerja yang diizinkan',
        ];
    }
}
