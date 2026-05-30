<?php

namespace App\Http\Controllers;

use App\Models\DetailPekerjaan;
use App\Models\Presensi;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LaporanExportController extends Controller
{
    /**
     * Build rekap presensi per karyawan dengan agregasi on-the-fly dari tb_presensi
     * (semua jenis: Harian/Mingguan/Bulanan/Tahunan). V3 tidak lagi punya tabel
     * rekap terpisah — angka rekap dihitung langsung dari presensi.
     *
     * @return Collection<int, object>
     */
    private function buildRekapPresensi(Request $request): Collection
    {
        $jenis   = strtolower((string) $request->string('jenis')) ?: 'bulanan';
        $periode = (string) $request->string('periode');
        $userId  = $request->integer('karyawan_id');

        $query = Presensi::query();

        if ($periode !== '') {
            [$start, $end] = $this->periodeRange($jenis, $periode);
            $query->whereBetween('tanggal', [$start, $end]);
        }

        $rows = $query
            ->selectRaw('
                user_id,
                SUM(CASE WHEN status_presensi IN ("hadir","terlambat") THEN 1 ELSE 0 END) AS jumlah_hadir,
                SUM(CASE WHEN status_presensi = "terlambat" THEN 1 ELSE 0 END) AS jumlah_terlambat,
                SUM(CASE WHEN status_presensi = "tidak_hadir" THEN 1 ELSE 0 END) AS jumlah_tidak_hadir,
                COALESCE(SUM(potongan_terlambat), 0) AS total_potongan
            ')
            ->when($userId, fn ($q) => $q->where('user_id', $userId))
            ->groupBy('user_id')
            ->get();

        $users = User::whereIn('id', $rows->pluck('user_id'))->get()->keyBy('id');

        return $rows->map(fn ($r) => (object) [
            'periode' => $periode,
            'user' => $users->get($r->user_id),
            'jumlah_hadir' => (int) $r->jumlah_hadir,
            'jumlah_terlambat' => (int) $r->jumlah_terlambat,
            'jumlah_tidak_hadir' => (int) $r->jumlah_tidak_hadir,
            'total_potongan' => (float) $r->total_potongan,
            'status' => 'rekap_otomatis',
        ]);
    }

    /**
     * @return array{0:string,1:string} [start, end] tanggal
     */
    private function periodeRange(string $jenis, string $periode): array
    {
        return match ($jenis) {
            'harian'   => [$periode, $periode],
            'mingguan' => [$periode, Carbon::parse($periode)->addDays(6)->toDateString()],
            'tahunan'  => [$periode . '-01-01', $periode . '-12-31'],
            default    => [
                Carbon::parse($periode . '-01')->startOfMonth()->toDateString(),
                Carbon::parse($periode . '-01')->endOfMonth()->toDateString(),
            ],
        };
    }

    public function exportCsv(Request $request): StreamedResponse
    {
        $request->validate([
            'periode' => 'nullable|string|max:20',
            'jenis' => 'nullable|string|max:20',
            'karyawan_id' => 'nullable|integer|exists:tb_user,id',
        ]);

        $rekap = $this->buildRekapPresensi($request);

        $filename = 'laporan-rekap-presensi-bulanan-' . now()->format('Ymd-His') . '.csv';

        return response()->streamDownload(function () use ($rekap): void {
            $handle = fopen('php://output', 'wb');

            fputcsv($handle, [
                'Periode',
                'Karyawan',
                'Total Hadir',
                'Total Terlambat',
                'Total Tidak Hadir',
                'Total Potongan Keterlambatan',
                'Status',
                'Tanggal Generate',
            ]);

            foreach ($rekap as $p) {
                fputcsv($handle, [
                    $p->periode,
                    $p->user?->nama ?? $p->user?->nik,
                    $p->jumlah_hadir,
                    $p->jumlah_terlambat,
                    $p->jumlah_tidak_hadir,
                    (string) $p->total_potongan,
                    $p->status,
                    now()->format('Y-m-d H:i:s'),
                ]);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function exportExcel(Request $request)
    {
        $request->validate([
            'periode' => 'nullable|string|max:20',
            'jenis' => 'nullable|string|max:20',
            'karyawan_id' => 'nullable|integer|exists:tb_user,id',
        ]);

        $jenis = ucfirst(strtolower((string) $request->string('jenis')) ?: 'bulanan');
        $filename = 'laporan-rekap-presensi-' . strtolower($jenis) . '-' . now()->format('Ymd-His') . '.xlsx';

        return \Maatwebsite\Excel\Facades\Excel::download(
            new \App\Exports\LaporanPresensiExport(
                $request->string('periode'),
                $request->integer('karyawan_id'),
                $this->buildRekapPresensi($request),
                $jenis,
            ),
            $filename
        );
    }

    public function exportPdf(Request $request)
    {
        $request->validate([
            'periode' => 'nullable|string|max:20',
            'jenis' => 'nullable|string|max:20',
            'karyawan_id' => 'nullable|integer|exists:tb_user,id',
        ]);

        $rekap = $this->buildRekapPresensi($request);

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('exports.laporan-pdf', [
            'penggajians' => $rekap,
            'periode' => $request->string('periode') ?: 'Semua Periode',
            'jenis' => $request->string('jenis') ?: 'Bulanan',
        ]);

        return $pdf->download('laporan-jumlah-presensi-per-karyawan-' . now()->format('Ymd-His') . '.pdf');
    }

    // ========== EXPORT PRESENSI HARIAN ==========

    private function getPresensiQuery(Request $request)
    {
        $request->validate([
            'periode' => 'nullable|string|max:20',
            'karyawan_id' => 'nullable|integer|exists:tb_user,id',
        ]);

        return Presensi::query()
            ->with(['user'])
            ->when($request->filled('periode'), function ($q) use ($request) {
                $periode = (string) $request->string('periode');
                if (strlen($periode) === 10) {
                    $end = Carbon::parse($periode)->addDays(6)->toDateString();
                    return $q->whereBetween('tanggal', [$periode, $end]);
                }
                return $q->whereRaw("DATE_FORMAT(tanggal, '%Y-%m') = ?", [$periode]);
            })
            ->when($request->filled('karyawan_id'), fn ($q) => $q->where('user_id', $request->integer('karyawan_id')))
            ->orderBy('tanggal', 'desc')
            ->orderBy('user_id')
            ->get();
    }

    private function presensiHeadings(): array
    {
        return [
            'Tanggal',
            'Karyawan',
            'NIK',
            'Jam Masuk',
            'Jam Keluar',
            'Status',
            'Keterlambatan (menit)',
            'Potongan (Rp)',
        ];
    }

    private function presensiRow($p): array
    {
        return [
            $p->tanggal,
            $p->user?->nama ?? '-',
            $p->user?->nik ?? '-',
            $p->jam_masuk ? Carbon::parse($p->jam_masuk)->format('H:i') : '-',
            $p->jam_keluar ? Carbon::parse($p->jam_keluar)->format('H:i') : '-',
            match ($p->status_presensi) {
                'hadir' => 'Hadir',
                'terlambat' => 'Terlambat',
                'tidak_hadir' => 'Alpa',
                'izin' => 'Izin',
                default => $p->status_presensi,
            },
            $p->menit_terlambat ?? 0,
            $p->potongan_terlambat ?? 0,
        ];
    }

    public function exportPresensiCsv(Request $request): StreamedResponse
    {
        $presensis = $this->getPresensiQuery($request);
        $filename = 'laporan-presensi-' . now()->format('Ymd-His') . '.csv';

        return response()->streamDownload(function () use ($presensis): void {
            $handle = fopen('php://output', 'wb');
            fputcsv($handle, $this->presensiHeadings());

            foreach ($presensis as $p) {
                fputcsv($handle, $this->presensiRow($p));
            }
            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function exportPresensiExcel(Request $request)
    {
        $filename = 'laporan-presensi-' . now()->format('Ymd-His') . '.xlsx';

        return \Maatwebsite\Excel\Facades\Excel::download(
            new \App\Exports\LaporanPresensiDetailExport($request->string('periode'), $request->integer('karyawan_id')),
            $filename
        );
    }

    public function exportPresensiPdf(Request $request)
    {
        $presensis = $this->getPresensiQuery($request);

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('exports.laporan-presensi-pdf', [
            'presensis' => $presensis,
            'periode' => $request->string('periode') ?: 'Semua Periode',
            'jenis' => $request->string('jenis') ?: 'Harian',
        ]);

        return $pdf->download('laporan-presensi-' . now()->format('Ymd-His') . '.pdf');
    }

    // ========== EXPORT REKAP PEKERJAAN ==========

    private function getPekerjaanQuery(Request $request)
    {
        $request->validate([
            'periode' => 'nullable|string|max:20',
            'karyawan_id' => 'nullable|integer|exists:tb_user,id',
        ]);

        return DetailPekerjaan::query()
            ->with(['user', 'jadwal', 'buktiPekerjaans'])
            ->join('tb_jadwal_pekerjaan', 'tb_jadwal_pekerjaan.id', '=', 'tb_detail_pekerjaan.jadwal_id')
            ->when($request->filled('periode'), function ($q) use ($request) {
                $periode = (string) $request->string('periode');
                if (strlen($periode) === 10) {
                    $end = Carbon::parse($periode)->addDays(6)->toDateString();
                    return $q->whereBetween('tb_jadwal_pekerjaan.tanggal_kerja', [$periode, $end]);
                }
                return $q->whereRaw("DATE_FORMAT(tb_jadwal_pekerjaan.tanggal_kerja, '%Y-%m') = ?", [$periode]);
            })
            ->when($request->filled('karyawan_id'), fn ($q) => $q->where('tb_detail_pekerjaan.user_id', $request->integer('karyawan_id')))
            ->orderByDesc('tb_jadwal_pekerjaan.tanggal_kerja')
            ->orderBy('tb_detail_pekerjaan.user_id')
            ->select('tb_detail_pekerjaan.*')
            ->get();
    }

    private function pekerjaanHeadings(): array
    {
        return [
            'Tanggal',
            'Karyawan',
            'NIK',
            'Nama Lokasi',
            'Status Tugas',
            'Alasan Tolak',
            'Bukti Diupload',
            'Keterangan',
        ];
    }

    private function pekerjaanRow($p): array
    {
        return [
            optional($p->jadwal?->tanggal_kerja)->format('Y-m-d') ?? '-',
            $p->user?->nama ?? '-',
            $p->user?->nik ?? '-',
            $p->nama_lokasi ?? '-',
            match ($p->status) {
                'disetujui' => 'Diterima',
                'ditolak' => 'Ditolak',
                default => 'Menunggu',
            },
            $p->alasan_tolak ?? '-',
            $p->buktiPekerjaans->count() > 0 ? 'Ya' : 'Belum',
            $p->keterangan_pekerjaan ?? '-',
        ];
    }

    public function exportPekerjaanCsv(Request $request): StreamedResponse
    {
        $data = $this->getPekerjaanQuery($request);
        $filename = 'laporan-rekap-pekerjaan-' . now()->format('Ymd-His') . '.csv';

        return response()->streamDownload(function () use ($data): void {
            $handle = fopen('php://output', 'wb');
            fputcsv($handle, $this->pekerjaanHeadings());

            foreach ($data as $p) {
                fputcsv($handle, $this->pekerjaanRow($p));
            }
            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function exportPekerjaanExcel(Request $request)
    {
        $filename = 'laporan-rekap-pekerjaan-' . now()->format('Ymd-His') . '.xlsx';

        return \Maatwebsite\Excel\Facades\Excel::download(
            new \App\Exports\LaporanPekerjaanExport($request->string('periode'), $request->integer('karyawan_id')),
            $filename
        );
    }

    public function exportPekerjaanPdf(Request $request)
    {
        $data = $this->getPekerjaanQuery($request);

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('exports.laporan-pekerjaan-pdf', [
            'pekerjaans' => $data,
            'periode' => $request->string('periode') ?: 'Semua Periode',
            'jenis' => $request->string('jenis') ?: 'Bulanan',
        ]);

        return $pdf->download('laporan-rekap-pekerjaan-' . now()->format('Ymd-His') . '.pdf');
    }
}
