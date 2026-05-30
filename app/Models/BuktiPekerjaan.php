<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BuktiPekerjaan extends Model
{
    protected $table = 'tb_bukti_pekerjaan';

    protected $fillable = [
        'detail_pekerjaan_id',
        'user_id',
        'foto_before',
        'foto_after',
        'keterangan',
        'status',
        'uploaded_at',
    ];

    protected function casts(): array
    {
        return [
            'uploaded_at' => 'datetime',
        ];
    }

    public function detailPekerjaan(): BelongsTo
    {
        return $this->belongsTo(DetailPekerjaan::class, 'detail_pekerjaan_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
