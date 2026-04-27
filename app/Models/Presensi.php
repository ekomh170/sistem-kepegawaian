<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Presensi extends Model
{
    protected $table = 'tb_presensi';

    public const UPDATED_AT = null;

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

    protected function casts(): array
    {
        return [
            'tgl_presensi' => 'date',
            'jam_masuk' => 'datetime',
            'jam_keluar' => 'datetime',
            'durasi_menit' => 'integer',
        ];
    }

    public function karyawan(): BelongsTo
    {
        return $this->belongsTo(Karyawan::class, 'karyawan_id');
    }

    public function jadwalKerja(): BelongsTo
    {
        return $this->belongsTo(Jadwal_kerja::class, 'jadwal_kerja_id');
    }

    public function lokasiGps(): BelongsTo
    {
        return $this->belongsTo(Lokasi_gps::class, 'lokasi_gps_id');
    }

    public function verifikasi(): HasOne
    {
        return $this->hasOne(Verifikasi::class, 'presensi_id');
    }
}
