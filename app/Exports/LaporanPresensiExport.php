<?php

namespace App\Exports;

use App\Models\RekapPresensiBulanan;
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
        // Kalau dipanggil dari controller dengan data pre-built (Harian/Mingguan/Bulanan
        // via aggregator), pakai itu. Kalau tidak, fallback ke query langsung (Bulanan).
        if ($this->prebuilt !== null) {
            return $this->prebuilt;
        }

        return RekapPresensiBulanan::query()
            ->with(['karyawan.user'])
            ->when($this->periode, fn ($query) => $query->where('periode', $this->periode))
            ->when($this->karyawan_id, fn ($query) => $query->where('karyawan_id', $this->karyawan_id))
            ->orderByDesc('created_at')
            ->get();
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
            $p->karyawan?->user?->nama ?? $p->karyawan?->nik,
            $p->jumlah_hadir,
            $p->jumlah_terlambat,
            $p->jumlah_tidak_hadir,
            (string) $p->total_potongan_keterlambatan,
            $p->status,
            now()->format('Y-m-d H:i:s'),
        ];
    }
}
