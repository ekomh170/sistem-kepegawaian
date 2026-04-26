<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Presensi extends Model
{
    protected $table = 'tb_presensi';

    protected $fillable = [
        'karyawan_id',
        'jadwal_kerja_id',
        'lokasi_gps_id',
        'tgl_presensi',
        'jam_masuk',
        'jam_keluar',
        'status',
        'foto_masuk',
        'foto_keluar',
        'durasi_menit',
    ];

    public function karyawan()
    {
        return $this->belongsTo(Karyawan::class);
    }
}
