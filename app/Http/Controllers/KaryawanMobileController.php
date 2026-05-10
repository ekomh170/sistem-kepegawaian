<?php

namespace App\Http\Controllers;

use App\Models\BuktiPekerjaan;
use App\Models\DetailPekerjaan;
use App\Models\Jadwal;
use App\Models\Karyawan;
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
        $karyawan = $this->resolveKaryawan();
        $today = Carbon::today()->toDateString();

        // Jadwal hari ini
        $jadwalHariIni = Jadwal::getJadwalHarian($karyawan->id, $today);

        // Presensi hari ini (1 per hari)
        $presensiHariIni = Presensi::query()
            ->with('verifikasi')
            ->where('karyawan_id', $karyawan->id)
            ->where('tanggal', $today)
            ->first();

        // Daftar tugas hari ini (via jadwal)
        $tugasHariIni = DetailPekerjaan::query()
            ->where('karyawan_id', $karyawan->id)
            ->whereHas('jadwal', fn ($q) => $q->where('tanggal_kerja', $today))
            ->get();

        // Bukti yang sudah diupload hari ini
        $buktiHariIni = BuktiPekerjaan::query()
            ->where('karyawan_id', $karyawan->id)
            ->whereIn('detail_pekerjaan_id', $tugasHariIni->pluck('id'))
            ->get()
            ->keyBy('detail_pekerjaan_id');

        return view('karyawan.beranda', [
            'jadwalHariIni' => $jadwalHariIni,
            'presensiHariIni' => $presensiHariIni,
            'tugasHariIni' => $tugasHariIni,
            'buktiHariIni' => $buktiHariIni,
            'today' => $today,
        ]);
    }

    public function formPresensiMasuk(): View
    {
        $karyawan = $this->resolveKaryawan();
        $today = Carbon::today()->toDateString();

        $presensiHariIni = Presensi::query()
            ->where('karyawan_id', $karyawan->id)
            ->where('tanggal', $today)
            ->first();

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
                ->route('karyawan.beranda')
                ->with('success', $payload['message'] ?? 'Presensi masuk berhasil dicatat.');
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            report($exception);
            return back()->withInput()->with('error', 'Terjadi kendala saat menyimpan presensi masuk. Details: ' . $exception->getMessage());
        }
    }

    public function formPresensiPulang(): View
    {
        $karyawan = $this->resolveKaryawan();
        $today = Carbon::today()->toDateString();

        $presensiHariIni = Presensi::query()
            ->where('karyawan_id', $karyawan->id)
            ->where('tanggal', $today)
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
    public function daftarTugas(): View
    {
        $karyawan = $this->resolveKaryawan();
        $today = Carbon::today()->toDateString();

        $tugasHariIni = DetailPekerjaan::query()
            ->with('jadwal')
            ->where('karyawan_id', $karyawan->id)
            ->whereHas('jadwal', fn ($q) => $q->where('tanggal_kerja', $today))
            ->get();

        $buktiHariIni = BuktiPekerjaan::query()
            ->where('karyawan_id', $karyawan->id)
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
        $karyawan = $this->resolveKaryawan();
        $detailId = $request->query('detail_pekerjaan_id');

        $tugas = DetailPekerjaan::with('jadwal')
            ->where('id', $detailId)
            ->where('karyawan_id', $karyawan->id)
            ->firstOrFail();

        // Hanya bisa upload jika tugas sudah diterima
        if ($tugas->status !== 'disetujui') {
            return redirect()->route('karyawan.tugas')->with('error', 'Anda harus menerima tugas terlebih dahulu sebelum upload bukti.');
        }

        $buktiExisting = BuktiPekerjaan::where('detail_pekerjaan_id', $detailId)
            ->where('karyawan_id', $karyawan->id)
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
     * Karyawan menerima tugas (status → disetujui)
     */
    public function terimaTugas(Request $request): RedirectResponse
    {
        $request->validate(['detail_pekerjaan_id' => 'required|exists:tb_detail_pekerjaan,id']);

        $karyawan = $this->resolveKaryawan();
        $tugas = DetailPekerjaan::where('id', $request->detail_pekerjaan_id)
            ->where('karyawan_id', $karyawan->id)
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

        $karyawan = $this->resolveKaryawan();
        $tugas = DetailPekerjaan::where('id', $request->detail_pekerjaan_id)
            ->where('karyawan_id', $karyawan->id)
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
        $karyawan = $this->resolveKaryawan();

        $startDate = Carbon::today();
        $endDate = Carbon::today()->addDays(6);

        $jadwalMingguan = Jadwal::query()
            ->where('karyawan_id', $karyawan->id)
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
        $karyawan = $this->resolveKaryawan();

        $riwayat = Presensi::query()
            ->with(['verifikasi'])
            ->where('karyawan_id', $karyawan->id)
            ->orderByDesc('tanggal')
            ->limit(30)
            ->get();

        $summary = [
            'hadir' => $riwayat->where('status_presensi', 'hadir')->count(),
            'terlambat' => $riwayat->where('status_presensi', 'terlambat')->count(),
            'tidak_hadir' => $riwayat->where('status_presensi', 'tidak_hadir')->count(),
            'izin' => $riwayat->where('status_presensi', 'izin')->count(),
        ];

        return view('karyawan.riwayat', [
            'riwayat' => $riwayat,
            'summary' => $summary,
            'laporanTerbaru' => null,
        ]);
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/login');
    }

    private function resolveKaryawan(): Karyawan
    {
        return Karyawan::query()
            ->where('user_id', Auth::id())
            ->firstOrFail();
    }
}
