<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class LaporanPresensiExport implements FromCollection, WithHeadings, WithMapping
{
    protected $periode;
    protected $karyawan_id;
    protected ?Collection $prebuilt;
    protected string $jenis;

    public function __construct($periode = null, $karyawan_id = null, ?Collection $prebuilt = null, string $jenis = 'Bulanan')
    {
        $this->periode = $periode;
        $this->karyawan_id = $karyawan_id;
        $this->prebuilt = $prebuilt;
        $this->jenis = $jenis;
    }

    public function collection()
    {
        // V3: rekap selalu di-build on-the-fly oleh controller (LaporanExportController)
        // lalu dikirim sebagai prebuilt collection.
        return $this->prebuilt ?? collect();
    }

    public function headings(): array
    {
        return [
            'Periode (' . $this->jenis . ')',
            'Karyawan',
            'Total Hadir',
            'Total Terlambat',
            'Total Tidak Hadir',
            'Total Potongan Keterlambatan',
            'Status',
            'Tanggal Generate',
        ];
    }

    public function map($p): array
    {
        return [
            $p->periode,
            $p->user?->nama ?? $p->user?->nik,
            $p->jumlah_hadir,
            $p->jumlah_terlambat,
            $p->jumlah_tidak_hadir,
            (string) $p->total_potongan,
            $p->status,
            now()->format('Y-m-d H:i:s'),
        ];
    }
}
