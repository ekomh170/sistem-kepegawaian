<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Jadwal_kerja extends Model
{
    protected $table = 'tb_jadwal_kerja';

    public const UPDATED_AT = null;

    protected $fillable = [
        'karyawan_id',
        'tanggal',
        'hari',
        'jam_masuk',
        'jam_keluar',
        'lokasi_kerja',
    ];

    protected function casts(): array
    {
        return [
            'tanggal' => 'date',
        ];
    }

    public function karyawan(): BelongsTo
    {
        return $this->belongsTo(Karyawan::class, 'karyawan_id');
    }

    public function presensis(): HasMany
    {
        return $this->hasMany(Presensi::class, 'jadwal_kerja_id');
    }
}
