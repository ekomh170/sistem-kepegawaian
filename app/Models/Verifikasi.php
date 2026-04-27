<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Verifikasi extends Model
{
    protected $table = 'tb_verifikasi';

    public const UPDATED_AT = null;

    protected $fillable = [
        'presensi_id',
        'supervisor_id',
        'status',
        'catatan',
        'tgl_verifikasi',
        'alasan_tolak',
    ];

    protected function casts(): array
    {
        return [
            'tgl_verifikasi' => 'datetime',
        ];
    }

    public function presensi(): BelongsTo
    {
        return $this->belongsTo(Presensi::class, 'presensi_id');
    }

    public function supervisor(): BelongsTo
    {
        return $this->belongsTo(Supervisor::class, 'supervisor_id');
    }
}
