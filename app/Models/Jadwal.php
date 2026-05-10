<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Jadwal extends Model
{
    protected $table = 'tb_jadwal';

    public const UPDATED_AT = null;

    protected $fillable = [
        'karyawan_id',
        'admin_id',
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

    public function karyawan(): BelongsTo
    {
        return $this->belongsTo(Karyawan::class, 'karyawan_id');
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'admin_id');
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

    public static function getJadwalHarian(int $karyawanId, string $tanggal): ?self
    {
        return self::where('karyawan_id', $karyawanId)
            ->where('tanggal_kerja', $tanggal)
            ->first();
    }

    public static function getJadwalBulanan(int $karyawanId, string $periode): Collection
    {
        $start = Carbon::parse($periode . '-01')->startOfMonth();
        $end = $start->copy()->endOfMonth();

        return self::where('karyawan_id', $karyawanId)
            ->whereBetween('tanggal_kerja', [$start, $end])
            ->orderBy('tanggal_kerja')
            ->get();
    }
}
