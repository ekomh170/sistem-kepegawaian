<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Presensi extends Model
{
    protected $table = 'tb_presensi';

    public const UPDATED_AT = null;

    protected $fillable = [
        'karyawan_id',
        'jadwal_id',
        'tanggal',
        'jam_masuk',
        'jam_keluar',
        'foto_masuk',
        'foto_keluar',
        'latitude_masuk',
        'longitude_masuk',
        'latitude_keluar',
        'longitude_keluar',
        'menit_terlambat',
        'potongan_terlambat',
        'status_presensi',
        'status_valid',
    ];

    protected function casts(): array
    {
        return [
            'tanggal' => 'date',
            'jam_masuk' => 'datetime',
            'jam_keluar' => 'datetime',
            'menit_terlambat' => 'integer',
            'potongan_terlambat' => 'decimal:2',
        ];
    }

    public function karyawan(): BelongsTo
    {
        return $this->belongsTo(Karyawan::class, 'karyawan_id');
    }

    public function jadwal(): BelongsTo
    {
        return $this->belongsTo(Jadwal::class, 'jadwal_id');
    }

    public function verifikasi(): HasOne
    {
        return $this->hasOne(Verifikasi::class, 'presensi_id');
    }

    public static function hitungKeterlambatan(string $jamMasuk, string $jamJadwal): int
    {
        $masuk = Carbon::parse($jamMasuk);
        $jadwal = Carbon::parse($jamJadwal);

        return $masuk->greaterThan($jadwal) ? (int) $jadwal->diffInMinutes($masuk) : 0;
    }

    public static function hitungPotongan(int $menitTerlambat): float
    {
        if ($menitTerlambat <= 10) {
            return 0;
        }

        // Rp10.000 per 10 menit keterlambatan (toleransi 10 menit pertama)
        $blok = (int) ceil(($menitTerlambat - 10) / 10);
        return $blok * 10000;
    }

    public function getStatusPresensi(): string
    {
        return $this->status_presensi ?? '-';
    }

    public static function getRiwayatBulanan(int $karyawanId, string $periode): Collection
    {
        $start = Carbon::parse($periode . '-01')->startOfMonth();
        $end = $start->copy()->endOfMonth();

        return self::where('karyawan_id', $karyawanId)
            ->whereBetween('tanggal', [$start, $end])
            ->orderBy('tanggal')
            ->get();
    }
}
