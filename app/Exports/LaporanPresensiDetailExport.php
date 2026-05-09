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
            ->when($this->periode, fn ($q) => $q->whereRaw("DATE_FORMAT(tgl_presensi, '%Y-%m') = ?", [$this->periode]))
            ->when($this->karyawan_id, fn ($q) => $q->where('karyawan_id', $this->karyawan_id))
            ->orderBy('tgl_presensi', 'desc')
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
            'Jam Pulang',
            'Status',
            'Durasi (menit)',
            'Keterlambatan (menit)',
            'Potongan (Rp)',
        ];
    }

    public function map($p): array
    {
        return [
            $p->tgl_presensi,
            $p->karyawan?->user?->nama ?? '-',
            $p->karyawan?->nik ?? '-',
            $p->jam_masuk ? Carbon::parse($p->jam_masuk)->format('H:i') : '-',
            $p->jam_pulang ? Carbon::parse($p->jam_pulang)->format('H:i') : '-',
            match ($p->status) {
                'hadir' => 'Hadir',
                'terlambat' => 'Terlambat',
                'tidak_hadir' => 'Alpa',
                'izin' => 'Izin',
                default => $p->status,
            },
            $p->durasi_menit ?? 0,
            $p->keterlambatan_menit ?? 0,
            $p->potongan ?? 0,
        ];
    }
}
