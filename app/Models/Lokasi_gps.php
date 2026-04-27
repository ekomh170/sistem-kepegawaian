<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Lokasi_gps extends Model
{
    protected $table = 'tb_lokasi_gps';

    public const UPDATED_AT = null;

    protected $fillable = [
        'latitude',
        'longitude',
        'radius_meter',
        'nama_lokasi',
        'timestamp',
        'akurasi',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'radius_meter' => 'integer',
            'timestamp' => 'datetime',
            'akurasi' => 'float',
        ];
    }

    public function presensis(): HasMany
    {
        return $this->hasMany(Presensi::class, 'lokasi_gps_id');
    }
}
