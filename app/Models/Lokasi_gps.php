<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Lokasi_gps extends Model
{
    protected $table = 'tb_lokasi_gps';

    protected $fillable = [
        'latitude',
        'longitude',
        'radius_meter',
        'nama_lokasi',
        'timestamp',
        'akurasi',
    ];

    public function karyawan()
    {
        return $this->belongsTo(Karyawan::class);
    }
}
