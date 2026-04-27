<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Notifikasi extends Model
{
    protected $table = 'tb_notifikasi';

    public const UPDATED_AT = null;

    protected $fillable = [
        'user_id',
        'tipe',
        'pesan',
        'terbaca',
        'tgl_kirim',
        'channel',
    ];

    protected function casts(): array
    {
        return [
            'terbaca' => 'boolean',
            'tgl_kirim' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
