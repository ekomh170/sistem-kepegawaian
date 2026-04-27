<?php

namespace App\Http\Controllers;

use App\Models\Laporan;
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

        $laporans = Laporan::query()
            ->with(['karyawan.user', 'admin.user'])
            ->when($request->filled('periode'), fn ($query) => $query->where('periode', $request->string('periode')))
            ->when($request->filled('karyawan_id'), fn ($query) => $query->where('karyawan_id', $request->integer('karyawan_id')))
            ->orderByDesc('tgl_generate')
            ->get();

        $filename = 'laporan-presensi-' . now()->format('Ymd-His') . '.csv';

        return response()->streamDownload(function () use ($laporans): void {
            $handle = fopen('php://output', 'wb');

            fputcsv($handle, [
                'Periode',
                'Karyawan',
                'Admin',
                'Total Hadir',
                'Total Terlambat',
                'Total Tidak Hadir',
                'Estimasi Gaji',
                'Tanggal Generate',
            ]);

            foreach ($laporans as $laporan) {
                fputcsv($handle, [
                    $laporan->periode,
                    $laporan->karyawan?->nik,
                    $laporan->admin?->nip,
                    $laporan->total_hadir,
                    $laporan->total_terlambat,
                    $laporan->total_tidak_hadir,
                    (string) $laporan->estimasi_gaji,
                    optional($laporan->tgl_generate)->format('Y-m-d H:i:s'),
                ]);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
