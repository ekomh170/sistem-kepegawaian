<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Laporan extends Model
{
    protected $table = 'tb_laporan';

    public const UPDATED_AT = null;

    protected $fillable = [
        'karyawan_id',
        'admin_id',
        'periode',
        'total_hadir',
        'total_terlambat',
        'total_tidak_hadir',
        'estimasi_gaji',
        'tgl_generate',
    ];

    protected function casts(): array
    {
        return [
            'total_hadir' => 'integer',
            'total_terlambat' => 'integer',
            'total_tidak_hadir' => 'integer',
            'estimasi_gaji' => 'decimal:2',
            'tgl_generate' => 'datetime',
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
}
