<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Presensi extends Model
{
    protected $table = 'tb_presensi';

    public const UPDATED_AT = null;

    protected $fillable = [
        'user_id',
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
        // V3: verifikasi inline (dulu tb_verifikasi). status_valid V2 dilebur ke status_verifikasi.
        'status_verifikasi',
        'diverifikasi_oleh',
        'catatan_verifikasi',
        'tgl_verifikasi',
    ];

    protected function casts(): array
    {
        return [
            'tanggal' => 'date',
            'jam_masuk' => 'datetime',
            'jam_keluar' => 'datetime',
            'tgl_verifikasi' => 'datetime',
            'menit_terlambat' => 'integer',
            'potongan_terlambat' => 'decimal:2',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function jadwal(): BelongsTo
    {
        return $this->belongsTo(JadwalPekerjaan::class, 'jadwal_id');
    }

    public function verifikator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'diverifikasi_oleh');
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

    /**
     * Verifikasi presensi oleh supervisor (inline V3).
     */
    public function verifikasi(User $supervisor, string $status, ?string $catatan = null): self
    {
        $this->update([
            'status_verifikasi' => $status,
            'diverifikasi_oleh' => $supervisor->id,
            'catatan_verifikasi' => $catatan,
            'tgl_verifikasi' => now(),
        ]);

        return $this;
    }

    public function sudahDiverifikasi(): bool
    {
        return $this->status_verifikasi !== null && $this->status_verifikasi !== 'pending';
    }

    public function sudahCheckOut(): bool
    {
        return ! is_null($this->jam_keluar);
    }

    public static function getRiwayatBulanan(int $userId, string $periode): Collection
    {
        $start = Carbon::parse($periode . '-01')->startOfMonth();
        $end = $start->copy()->endOfMonth();

        return self::where('user_id', $userId)
            ->whereBetween('tanggal', [$start, $end])
            ->orderBy('tanggal')
            ->get();
    }
}
