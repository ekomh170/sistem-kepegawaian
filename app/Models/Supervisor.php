<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Supervisor extends Model
{
    protected $table = 'tb_supervisor';

    public const UPDATED_AT = null;

    protected $fillable = [
        'user_id',
        'jabatan',
        'level_akses',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function verifikasis(): HasMany
    {
        return $this->hasMany(Verifikasi::class, 'supervisor_id');
    }
}
