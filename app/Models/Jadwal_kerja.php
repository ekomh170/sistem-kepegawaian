<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Jadwal_kerja extends Model
{
    protected $table = 'tb_jadwal_kerja';

    protected $fillable = [
        'karyawan_id',
        'tanggal',
        'hari',
        'jam_mulai',
        'jam_selesai',
        'lokasi_kerja',
    ];

    public function karyawan()
    {
        return $this->belongsTo(Karyawan::class);
    }
}
