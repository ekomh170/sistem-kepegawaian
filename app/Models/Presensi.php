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
        'tgl_presensi',
        'jam_masuk',
        'jam_pulang',
        'status',
        'foto_masuk',
        'foto_keluar',
        'lat_masuk',
        'long_masuk',
        'durasi_menit',
        'keterlambatan_menit',
        'potongan',
    ];

    protected function casts(): array
    {
        return [
            'tgl_presensi' => 'date',
            'jam_masuk' => 'datetime',
            'jam_pulang' => 'datetime',
            'durasi_menit' => 'integer',
        ];
    }

    public function karyawan(): BelongsTo
    {
        return $this->belongsTo(Karyawan::class, 'karyawan_id');
    }

    public function verifikasi(): HasOne
    {
        return $this->hasOne(Verifikasi::class, 'presensi_id');
    }
}
