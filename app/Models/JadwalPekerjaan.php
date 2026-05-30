<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class JadwalPekerjaan extends Model
{
    protected $table = 'tb_jadwal_pekerjaan';

    public const UPDATED_AT = null;

    protected $fillable = [
        'user_id',
        'dibuat_oleh',
        'tanggal_kerja',
        'jam_masuk',
        'jam_pulang',
        'hari_libur',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_kerja' => 'date',
            'hari_libur' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function pembuatJadwal(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dibuat_oleh');
    }

    public function detailPekerjaans(): HasMany
    {
        return $this->hasMany(DetailPekerjaan::class, 'jadwal_id');
    }

    public function presensi(): HasOne
    {
        return $this->hasOne(Presensi::class, 'jadwal_id');
    }

    public function isHariLibur(): bool
    {
        return (bool) $this->hari_libur;
    }

    public static function getJadwalHarian(int $userId, string $tanggal): ?self
    {
        return self::where('user_id', $userId)
            ->whereDate('tanggal_kerja', $tanggal)
            ->first();
    }

    public static function getJadwalBulanan(int $userId, string $periode): Collection
    {
        $start = Carbon::parse($periode . '-01')->startOfMonth();
        $end = $start->copy()->endOfMonth();

        return self::where('user_id', $userId)
            ->whereBetween('tanggal_kerja', [$start, $end])
            ->orderBy('tanggal_kerja')
            ->get();
    }
}
