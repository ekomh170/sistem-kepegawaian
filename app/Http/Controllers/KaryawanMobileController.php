<?php

namespace App\Http\Controllers;

use App\Models\Jadwal_kerja;
use App\Models\Karyawan;
use App\Models\Laporan;
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

        $jadwalHariIni = Jadwal_kerja::query()
            ->where('karyawan_id', $karyawan->id)
            ->where('tanggal', $today)
            ->first();

        $presensiHariIni = Presensi::query()
            ->with('verifikasi')
            ->where('karyawan_id', $karyawan->id)
            ->where('tgl_presensi', $today)
            ->first();

        return view('karyawan.beranda', [
            'jadwalHariIni' => $jadwalHariIni,
            'presensiHariIni' => $presensiHariIni,
            'today' => $today,
        ]);
    }

    public function formPresensiMasuk(): View
    {
        $karyawan = $this->resolveKaryawan();
        $today = Carbon::today()->toDateString();

        $presensiHariIni = Presensi::query()
            ->where('karyawan_id', $karyawan->id)
            ->where('tgl_presensi', $today)
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

            return back()->withInput()->with('error', 'Terjadi kendala saat menyimpan presensi masuk.');
        }
    }

    public function formPresensiPulang(): View
    {
        $karyawan = $this->resolveKaryawan();
        $today = Carbon::today()->toDateString();

        $presensiHariIni = Presensi::query()
            ->where('karyawan_id', $karyawan->id)
            ->where('tgl_presensi', $today)
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

    public function jadwalMingguan(): View
    {
        $karyawan = $this->resolveKaryawan();

        $startDate = Carbon::today();
        $endDate = Carbon::today()->addDays(6);

        $jadwalMingguan = Jadwal_kerja::query()
            ->where('karyawan_id', $karyawan->id)
            ->whereBetween('tanggal', [$startDate->toDateString(), $endDate->toDateString()])
            ->orderBy('tanggal')
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
            ->orderByDesc('tgl_presensi')
            ->limit(30)
            ->get();

        $summary = [
            'hadir' => $riwayat->where('status', 'hadir')->count(),
            'terlambat' => $riwayat->where('status', 'terlambat')->count(),
            'tidak_hadir' => $riwayat->where('status', 'tidak_hadir')->count(),
            'izin' => $riwayat->where('status', 'izin')->count(),
        ];

        $laporanTerbaru = Laporan::query()
            ->where('karyawan_id', $karyawan->id)
            ->orderByDesc('tgl_generate')
            ->first();

        return view('karyawan.riwayat', [
            'riwayat' => $riwayat,
            'summary' => $summary,
            'laporanTerbaru' => $laporanTerbaru,
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
