<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Verifikasi extends Model
{
    protected $table = 'tb_verifikasi';

    protected $fillable = [
        'presensi_id',
        'supervisor_id',
        'status',
        'catatan',  
        'tgl_verifikasi',
        'alasan_tolak',

    ];

    public function karyawan()
    {
        return $this->belongsTo(Karyawan::class);
    }
}
