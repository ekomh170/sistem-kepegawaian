<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * V3: gabungan tb_laporan + tb_rekap_presensi_bulanan.
 * - user_id NULL    => laporan agregat (metadata laporan untuk semua karyawan)
 * - user_id != NULL => rekap per-karyawan (jumlah_* + total_potongan diisi)
 */
class LaporanPresensi extends Model
{
    protected $table = 'tb_laporan_presensi';

    public const UPDATED_AT = null;

    protected $fillable = [
        'user_id',
        'generated_by',
        'judul',
        'periode',
        'jenis',
        'jumlah_hadir',
        'jumlah_terlambat',
        'jumlah_tidak_hadir',
        'total_potongan',
        'file_path',
        'tgl_generate',
    ];

    protected function casts(): array
    {
        return [
            'tgl_generate' => 'datetime',
            'total_potongan' => 'decimal:2',
        ];
    }

    /**
     * Subjek rekap (karyawan). Untuk laporan agregat user_id NULL.
     */
    public function karyawan(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Pembuat laporan (admin/supervisor).
     */
    public function generator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generated_by');
    }

    public function isRekapPerKaryawan(): bool
    {
        return ! is_null($this->user_id);
    }
}
