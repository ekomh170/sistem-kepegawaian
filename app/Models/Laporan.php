<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Laporan extends Model
{
    protected $table = 'tb_laporan';

    public const UPDATED_AT = null;

    protected $fillable = [
        'judul',
        'jenis',
        'periode',
        'filter',
        'file_path',
        'generated_by',
        'tgl_generate',
    ];

    protected function casts(): array
    {
        return [
            'filter' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generated_by');
    }
}
