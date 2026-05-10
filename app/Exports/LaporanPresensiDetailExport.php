<?php

namespace App\Exports;

use App\Models\Presensi;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class LaporanPresensiDetailExport implements FromCollection, WithHeadings, WithMapping
{
    protected $periode;
    protected $karyawan_id;

    public function __construct($periode = null, $karyawan_id = null)
    {
        $this->periode = $periode;
        $this->karyawan_id = $karyawan_id;
    }

    public function collection()
    {
        return Presensi::query()
            ->with(['karyawan.user'])
            ->when($this->periode, fn ($q) => $q->whereRaw("DATE_FORMAT(tanggal, '%Y-%m') = ?", [$this->periode]))
            ->when($this->karyawan_id, fn ($q) => $q->where('karyawan_id', $this->karyawan_id))
            ->orderBy('tanggal', 'desc')
            ->orderBy('karyawan_id')
            ->get();
    }

    public function headings(): array
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

    public function map($p): array
    {
        return [
            $p->tanggal,
            $p->karyawan?->user?->nama ?? '-',
            $p->karyawan?->nik ?? '-',
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
}
