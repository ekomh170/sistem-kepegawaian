<?php

namespace App\Http\Controllers;

use App\Models\BuktiPekerjaan;
use App\Models\DetailPekerjaan;
use App\Models\JadwalPekerjaan;
use App\Models\Presensi;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Throwable;

class KaryawanMobileController extends Controller
{
    public function beranda(): View
    {
        $userId = Auth::id();
        $today = Carbon::today()->toDateString();

        // Jadwal hari ini
        $jadwalHariIni = JadwalPekerjaan::getJadwalHarian($userId, $today);

        // Presensi hari ini (1 per hari)
        $presensiHariIni = Presensi::query()
            ->where('user_id', $userId)
            ->whereDate('tanggal', $today)
            ->first();

        // Daftar tugas hari ini (via jadwal)
        $tugasHariIni = DetailPekerjaan::query()
            ->where('user_id', $userId)
            ->whereHas('jadwal', fn ($q) => $q->whereDate('tanggal_kerja', $today))
            ->get();

        // Bukti yang sudah diupload hari ini
        $buktiHariIni = BuktiPekerjaan::query()
            ->where('user_id', $userId)
            ->whereIn('detail_pekerjaan_id', $tugasHariIni->pluck('id'))
            ->get()
            ->keyBy('detail_pekerjaan_id');

        return view('karyawan.beranda', [
            'jadwalHariIni'   => $jadwalHariIni,
            'presensiHariIni' => $presensiHariIni,
            'tugasHariIni'    => $tugasHariIni,
            'buktiHariIni'    => $buktiHariIni,
            'today'           => $today,
        ]);
    }

    public function formPresensiMasuk(): View|RedirectResponse
    {
        $userId = Auth::id();
        $today = Carbon::today()->toDateString();

        $presensiHariIni = Presensi::query()
            ->where('user_id', $userId)
            ->whereDate('tanggal', $today)
            ->first();

        if ($presensiHariIni && $presensiHariIni->jam_masuk && !$presensiHariIni->jam_keluar) {
            return redirect()->route('karyawan.presensi.pulang');
        }

        return view('karyawan.presensi-masuk', [
            'presensiHariIni' => $presensiHariIni,
        ]);
    }

    public function submitPresensiMasuk(Request $request, PresensiController $presensiController): RedirectResponse
    {
        try {
            $response = $presensiController->checkIn($request);
            $payload = $response->getData(true);

            if (($payload['success'] ?? false) !== true) {
                return back()->withInput()->with('error', $payload['message'] ?? 'Presensi masuk gagal diproses.');
            }

            return redirect()
                ->route('karyawan.tugas')
                ->with('success', $payload['message'] ?? 'Presensi masuk berhasil dicatat. Berikut daftar tugas hari ini.');
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            report($exception);
            return back()->withInput()->with('error', 'Terjadi kendala saat menyimpan presensi masuk. Details: ' . $exception->getMessage());
        }
    }

    public function formPresensiPulang(): View
    {
        $userId = Auth::id();
        $today = Carbon::today()->toDateString();

        $presensiHariIni = Presensi::query()
            ->where('user_id', $userId)
            ->whereDate('tanggal', $today)
            ->first();

        return view('karyawan.presensi-pulang', [
            'presensiHariIni' => $presensiHariIni,
        ]);
    }

    public function submitPresensiPulang(Request $request, PresensiController $presensiController): RedirectResponse
    {
        try {
            $response = $presensiController->checkOut($request);
            $payload = $response->getData(true);

            if (($payload['success'] ?? false) !== true) {
                return back()->withInput()->with('error', $payload['message'] ?? 'Presensi pulang gagal diproses.');
            }

            return redirect()
                ->route('karyawan.beranda')
                ->with('success', $payload['message'] ?? 'Presensi pulang berhasil dicatat.');
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            report($exception);
            return back()->withInput()->with('error', 'Terjadi kendala saat menyimpan presensi pulang.');
        }
    }

    /**
     * Halaman Daftar Tugas — Karyawan melihat & upload bukti per tugas
     */
    public function daftarTugas(): View|RedirectResponse
    {
        $userId = Auth::id();
        $today = Carbon::today()->toDateString();

        $presensiHariIni = Presensi::where('user_id', $userId)
            ->whereDate('tanggal', $today)
            ->whereNotNull('jam_masuk')
            ->first();

        if (!$presensiHariIni) {
            return redirect()
                ->route('karyawan.presensi.masuk')
                ->with('error', 'Anda harus presensi masuk terlebih dahulu untuk melihat daftar tugas.');
        }

        $tugasHariIni = DetailPekerjaan::query()
            ->with('jadwal')
            ->where('user_id', $userId)
            ->whereHas('jadwal', fn ($q) => $q->whereDate('tanggal_kerja', $today))
            ->get();

        $buktiHariIni = BuktiPekerjaan::query()
            ->where('user_id', $userId)
            ->whereIn('detail_pekerjaan_id', $tugasHariIni->pluck('id'))
            ->get()
            ->keyBy('detail_pekerjaan_id');

        return view('karyawan.tugas', [
            'tugasHariIni' => $tugasHariIni,
            'buktiHariIni' => $buktiHariIni,
            'today' => $today,
        ]);
    }

    /**
     * Form upload bukti untuk tugas tertentu
     */
    public function formUploadBukti(Request $request): View|RedirectResponse
    {
        $userId = Auth::id();
        $detailId = $request->query('detail_pekerjaan_id');

        $tugas = DetailPekerjaan::with('jadwal')
            ->where('id', $detailId)
            ->where('user_id', $userId)
            ->firstOrFail();

        // Hanya bisa upload jika tugas sudah diterima
        if ($tugas->status !== 'disetujui') {
            return redirect()->route('karyawan.tugas')->with('error', 'Anda harus menerima tugas terlebih dahulu sebelum upload bukti.');
        }

        $buktiExisting = BuktiPekerjaan::where('detail_pekerjaan_id', $detailId)
            ->where('user_id', $userId)
            ->first();

        return view('karyawan.upload-bukti', [
            'tugas' => $tugas,
            'buktiExisting' => $buktiExisting,
        ]);
    }

    /**
     * Submit upload bukti pekerjaan
     */
    public function submitUploadBukti(Request $request, PresensiController $presensiController): RedirectResponse
    {
        try {
            $response = $presensiController->submitBuktiPekerjaan($request);
            $payload = $response->getData(true);

            if (($payload['success'] ?? false) !== true) {
                return back()->withInput()->with('error', $payload['message'] ?? 'Upload bukti gagal.');
            }

            return redirect()
                ->route('karyawan.tugas')
                ->with('success', $payload['message'] ?? 'Bukti pekerjaan berhasil diupload.');
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            report($exception);
            return back()->withInput()->with('error', 'Terjadi kendala: ' . $exception->getMessage());
        }
    }

    /**
     * Halaman detail bukti pekerjaan yang sudah diupload
     */
    public function detailBukti(int $detail_pekerjaan_id): View|RedirectResponse
    {
        $userId = Auth::id();

        $tugas = DetailPekerjaan::with('jadwal')
            ->where('id', $detail_pekerjaan_id)
            ->where('user_id', $userId)
            ->firstOrFail();

        $bukti = BuktiPekerjaan::where('detail_pekerjaan_id', $detail_pekerjaan_id)
            ->where('user_id', $userId)
            ->first();

        if (!$bukti) {
            return redirect()->route('karyawan.tugas')->with('error', 'Bukti pekerjaan belum diupload.');
        }

        return view('karyawan.detail-bukti', [
            'tugas' => $tugas,
            'bukti' => $bukti,
        ]);
    }

    /**
     * Karyawan menerima tugas (status → disetujui)
     */
    public function terimaTugas(Request $request): RedirectResponse
    {
        $request->validate(['detail_pekerjaan_id' => 'required|exists:tb_detail_pekerjaan,id']);

        $tugas = DetailPekerjaan::where('id', $request->detail_pekerjaan_id)
            ->where('user_id', Auth::id())
            ->where('status', 'pending')
            ->firstOrFail();

        $tugas->update(['status' => 'disetujui']);

        return redirect()->route('karyawan.tugas')->with('success', 'Tugas "' . $tugas->nama_lokasi . '" berhasil diterima.');
    }

    /**
     * Karyawan menolak tugas (status → ditolak, wajib isi alasan)
     */
    public function tolakTugas(Request $request): RedirectResponse
    {
        $request->validate([
            'detail_pekerjaan_id' => 'required|exists:tb_detail_pekerjaan,id',
            'alasan_tolak' => 'required|string|min:10|max:500',
        ], [
            'alasan_tolak.required' => 'Alasan penolakan wajib diisi.',
            'alasan_tolak.min' => 'Alasan penolakan minimal 10 karakter.',
        ]);

        $tugas = DetailPekerjaan::where('id', $request->detail_pekerjaan_id)
            ->where('user_id', Auth::id())
            ->where('status', 'pending')
            ->firstOrFail();

        $tugas->update([
            'status' => 'ditolak',
            'alasan_tolak' => $request->alasan_tolak,
        ]);

        return redirect()->route('karyawan.tugas')->with('success', 'Tugas "' . $tugas->nama_lokasi . '" berhasil ditolak.');
    }

    public function jadwalMingguan(): View
    {
        $userId = Auth::id();

        $startDate = Carbon::today();
        $endDate = Carbon::today()->addDays(6);

        $jadwalMingguan = JadwalPekerjaan::query()
            ->where('user_id', $userId)
            ->whereBetween('tanggal_kerja', [$startDate->toDateString(), $endDate->toDateString()])
            ->orderBy('tanggal_kerja')
            ->get();

        return view('karyawan.jadwal', [
            'jadwalMingguan' => $jadwalMingguan,
            'startDate' => $startDate,
            'endDate' => $endDate,
        ]);
    }

    public function riwayat(): View
    {
        $userId = Auth::id();

        $riwayat = Presensi::query()
            ->where('user_id', $userId)
            ->orderByDesc('tanggal')
            ->limit(30)
            ->get();

        $summary = [
            'hadir'       => $riwayat->where('status_presensi', 'hadir')->count(),
            'terlambat'   => $riwayat->where('status_presensi', 'terlambat')->count(),
            'tidak_hadir' => $riwayat->where('status_presensi', 'tidak_hadir')->count(),
            'izin'        => $riwayat->where('status_presensi', 'izin')->count(),
        ];

        $riwayatPekerjaan = DetailPekerjaan::query()
            ->with('jadwal')
            ->join('tb_jadwal_pekerjaan', 'tb_detail_pekerjaan.jadwal_id', '=', 'tb_jadwal_pekerjaan.id')
            ->where('tb_detail_pekerjaan.user_id', $userId)
            ->where('tb_jadwal_pekerjaan.tanggal_kerja', '>=', now()->subDays(30)->toDateString())
            ->orderByDesc('tb_jadwal_pekerjaan.tanggal_kerja')
            ->select('tb_detail_pekerjaan.*')
            ->get();

        $bulanIni = Carbon::today();
        $presensBulanIni = Presensi::query()
            ->where('user_id', $userId)
            ->whereYear('tanggal', $bulanIni->year)
            ->whereMonth('tanggal', $bulanIni->month)
            ->get();

        $totalPotongan = $presensBulanIni->sum('potongan_terlambat');
        $hariHadir     = $presensBulanIni->whereIn('status_presensi', ['hadir', 'terlambat'])->count();

        return view('karyawan.riwayat', [
            'riwayat'          => $riwayat,
            'summary'          => $summary,
            'riwayatPekerjaan' => $riwayatPekerjaan,
            'totalPotongan'    => $totalPotongan,
            'hariHadir'        => $hariHadir,
            'namaBulan'        => $bulanIni->translatedFormat('F Y'),
        ]);
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/login')->with('status', 'Berhasil keluar. Sampai jumpa!');
    }
}
