<?php

namespace App\Exports;

use App\Models\DetailPekerjaan;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class LaporanPekerjaanExport implements FromCollection, WithHeadings, WithMapping
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
        return DetailPekerjaan::query()
            ->with(['user', 'jadwal', 'buktiPekerjaans'])
            ->join('tb_jadwal_pekerjaan', 'tb_jadwal_pekerjaan.id', '=', 'tb_detail_pekerjaan.jadwal_id')
            ->when(
                $this->periode,
                fn ($q) => $q->whereRaw("DATE_FORMAT(tb_jadwal_pekerjaan.tanggal_kerja, '%Y-%m') = ?", [$this->periode])
            )
            ->when($this->karyawan_id, fn ($q) => $q->where('tb_detail_pekerjaan.user_id', $this->karyawan_id))
            ->orderByDesc('tb_jadwal_pekerjaan.tanggal_kerja')
            ->orderBy('tb_detail_pekerjaan.user_id')
            ->select('tb_detail_pekerjaan.*')
            ->get();
    }

    public function headings(): array
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

    public function map($p): array
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
}
