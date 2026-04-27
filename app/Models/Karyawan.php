<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Karyawan extends Model
{
    protected $table = 'tb_karyawan';

    public const UPDATED_AT = null;

    protected $fillable = [
        'user_id',
        'nik',
        'posisi_karyawan',
        'tgl_masuk',
        'status_kontrak',
        'no_hp',
        'bidang_tugas',
    ];

    protected function casts(): array
    {
        return [
            'tgl_masuk' => 'date',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function jadwalKerjas(): HasMany
    {
        return $this->hasMany(Jadwal_kerja::class, 'karyawan_id');
    }

    public function presensis(): HasMany
    {
        return $this->hasMany(Presensi::class, 'karyawan_id');
    }

    public function laporans(): HasMany
    {
        return $this->hasMany(Laporan::class, 'karyawan_id');
    }
}
