<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Admin extends Model
{
    protected $table = 'tb_admin';

    public const UPDATED_AT = null;

    protected $fillable = [
        'user_id',
        'nip',
        'divisi',
        'level_akses',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function laporans(): HasMany
    {
        return $this->hasMany(Laporan::class, 'admin_id');
    }
}
