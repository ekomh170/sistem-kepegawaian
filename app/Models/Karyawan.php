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
        'no_ktp',
        'no_hp',
        'alamat',
        'foto',
        'tgl_masuk',
        'status_kontrak',
        'bidang_tugas',
        'gaji_pokok',
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

    public function jadwals(): HasMany
    {
        return $this->hasMany(Jadwal::class, 'karyawan_id');
    }

    public function detailPekerjaans(): HasMany
    {
        return $this->hasMany(DetailPekerjaan::class, 'karyawan_id');
    }

    public function presensis(): HasMany
    {
        return $this->hasMany(Presensi::class, 'karyawan_id');
    }

    public function rekapPresensiBulanans(): HasMany
    {
        return $this->hasMany(RekapPresensiBulanan::class, 'karyawan_id');
    }

    public function buktiPekerjaans(): HasMany
    {
        return $this->hasMany(BuktiPekerjaan::class, 'karyawan_id');
    }
}
