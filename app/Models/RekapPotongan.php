<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RekapPotongan extends Model
{
    protected $table = 'tb_rekap_potongan';

    protected $fillable = [
        'karyawan_id',
        'admin_id',
        'periode',
        'jumlah_hadir',
        'jumlah_tidak_hadir',
        'jumlah_terlambat',
        'total_potongan_keterlambatan',
        'gaji_pokok',
        'gaji_bersih',
        'catatan',
        'status',
    ];

    protected $casts = [
        'total_potongan_keterlambatan' => 'decimal:2',
        'gaji_pokok' => 'decimal:2',
        'gaji_bersih' => 'decimal:2',
    ];

    public function karyawan(): BelongsTo
    {
        return $this->belongsTo(Karyawan::class, 'karyawan_id');
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'admin_id');
    }

    /**
     * Hitung rekap potongan berdasarkan data presensi bulanan
     */
    public function hitungRekapPotongan(): self
    {
        $presensis = Presensi::where('karyawan_id', $this->karyawan_id)
            ->whereRaw("DATE_FORMAT(tgl_presensi, '%Y-%m') = ?", [$this->periode])
            ->get();

        $this->jumlah_hadir = $presensis->whereIn('status', ['hadir', 'terlambat'])->count();
        $this->jumlah_tidak_hadir = $presensis->where('status', 'tidak_hadir')->count();
        $this->jumlah_terlambat = $presensis->where('status', 'terlambat')->count();

        $this->total_potongan_keterlambatan = $presensis
            ->where('status', 'terlambat')
            ->sum('potongan');

        // Ambil gaji pokok dari data karyawan
        $this->gaji_pokok = $this->karyawan?->gaji_pokok ?? 0;
        $this->gaji_bersih = $this->gaji_pokok - $this->total_potongan_keterlambatan;

        return $this;
    }

    /**
     * Generate slip potongan (return data array untuk PDF/view)
     */
    public function generateSlipPotongan(): array
    {
        $karyawan = $this->karyawan()->with('user')->first();
        $namaPerusahaan = Setting::get('nama_perusahaan', 'CV Boss Muda Mandiri');

        return [
            'perusahaan' => $namaPerusahaan,
            'karyawan_nama' => $karyawan?->user?->nama ?? '-',
            'karyawan_nik' => $karyawan?->nik ?? '-',
            'posisi' => $karyawan?->posisi_karyawan ?? '-',
            'periode' => $this->periode,
            'jumlah_hadir' => $this->jumlah_hadir,
            'jumlah_tidak_hadir' => $this->jumlah_tidak_hadir,
            'jumlah_terlambat' => $this->jumlah_terlambat,
            'total_potongan' => $this->total_potongan_keterlambatan,
            'catatan' => $this->catatan,
            'status' => $this->status,
            'tanggal_cetak' => now()->translatedFormat('d F Y'),
        ];
    }
}
