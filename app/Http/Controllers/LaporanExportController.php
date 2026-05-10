<?php

namespace App\Http\Controllers;

use App\Models\RekapPresensiBulanan;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LaporanExportController extends Controller
{
    public function exportCsv(Request $request): StreamedResponse
    {
        $request->validate([
            'periode' => 'nullable|string|max:20',
            'karyawan_id' => 'nullable|integer|exists:tb_karyawan,id',
        ]);

        $rekapPresensiBulanans = RekapPresensiBulanan::query()
            ->with(['karyawan.user'])
            ->when($request->filled('periode'), fn ($query) => $query->where('periode', $request->string('periode')))
            ->when($request->filled('karyawan_id'), fn ($query) => $query->where('karyawan_id', $request->integer('karyawan_id')))
            ->orderByDesc('created_at')
            ->get();

        $filename = 'laporan-rekap-presensi-bulanan-' . now()->format('Ymd-His') . '.csv';

        return response()->streamDownload(function () use ($rekapPresensiBulanans): void {
            $handle = fopen('php://output', 'wb');

            fputcsv($handle, [
                'Periode',
                'Karyawan',
                'Total Hadir',
                'Total Terlambat',
                'Total Tidak Hadir',
                'Total Potongan Keterlambatan',
                'Gaji Pokok',
                'Gaji Bersih',
                'Status',
                'Tanggal Generate',
            ]);

            foreach ($rekapPresensiBulanans as $p) {
                fputcsv($handle, [
                    $p->periode,
                    $p->karyawan?->user?->nama ?? $p->karyawan?->nik,
                    $p->jumlah_hadir,
                    $p->jumlah_terlambat,
                    $p->jumlah_tidak_hadir,
                    (string) $p->total_potongan_keterlambatan,
                    (string) $p->gaji_pokok,
                    (string) $p->gaji_bersih,
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
            'karyawan_id' => 'nullable|integer|exists:tb_karyawan,id',
        ]);

        $filename = 'laporan-rekap-presensi-bulanan-' . now()->format('Ymd-His') . '.xlsx';
        return \Maatwebsite\Excel\Facades\Excel::download(
            new \App\Exports\LaporanPresensiExport($request->string('periode'), $request->integer('karyawan_id')),
            $filename
        );
    }

    public function exportPdf(Request $request)
    {
        $request->validate([
            'periode' => 'nullable|string|max:20',
            'karyawan_id' => 'nullable|integer|exists:tb_karyawan,id',
        ]);

        $rekapPresensiBulanans = RekapPresensiBulanan::query()
            ->with(['karyawan.user'])
            ->when($request->filled('periode'), fn ($query) => $query->where('periode', $request->string('periode')))
            ->when($request->filled('karyawan_id'), fn ($query) => $query->where('karyawan_id', $request->integer('karyawan_id')))
            ->orderByDesc('created_at')
            ->get();

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('exports.laporan-pdf', [
            'penggajians' => $rekapPresensiBulanans,
            'periode' => $request->string('periode') ?: 'Semua Periode'
        ]);

        return $pdf->download('laporan-rekap-presensi-bulanan-' . now()->format('Ymd-His') . '.pdf');
    }

    // ========== EXPORT PRESENSI HARIAN ==========

    private function getPresensiQuery(Request $request)
    {
        $request->validate([
            'periode' => 'nullable|string|max:20',
            'karyawan_id' => 'nullable|integer|exists:tb_karyawan,id',
        ]);

        return \App\Models\Presensi::query()
            ->with(['karyawan.user'])
            ->when($request->filled('periode'), fn ($q) => $q->whereRaw("DATE_FORMAT(tanggal, '%Y-%m') = ?", [$request->string('periode')]))
            ->when($request->filled('karyawan_id'), fn ($q) => $q->where('karyawan_id', $request->integer('karyawan_id')))
            ->orderBy('tanggal', 'desc')
            ->orderBy('karyawan_id')
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
            $p->karyawan?->user?->nama ?? '-',
            $p->karyawan?->nik ?? '-',
            $p->jam_masuk ? \Carbon\Carbon::parse($p->jam_masuk)->format('H:i') : '-',
            $p->jam_keluar ? \Carbon\Carbon::parse($p->jam_keluar)->format('H:i') : '-',
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
        $presensis = $this->getPresensiQuery($request);
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
        ]);

        return $pdf->download('laporan-presensi-' . now()->format('Ymd-His') . '.pdf');
    }
}
