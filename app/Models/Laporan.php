<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Laporan extends Model
{
    protected $table = 'tb_laporan';

    protected $fillable = [
        'karyawan_id',
        'admin_id',
        'periode',
        'total_hadir',
        'total_terlambat',
        'total_tidak_hadir',
        'estimasi_gaji',
        'tgl_generate',
    ];

    public function karyawan()
    {
        return $this->belongsTo(Karyawan::class);
    }
}
