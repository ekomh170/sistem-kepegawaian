<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DetailPekerjaan extends Model
{
    use HasFactory;

    protected $table = 'tb_detail_pekerjaan';
    public const UPDATED_AT = null;

    protected $fillable = [
        'jadwal_id',
        'user_id',
        'nama_lokasi',
        'alamat_lokasi',
        'latitude',
        'longitude',
        'radius_meter',
        'keterangan_pekerjaan',
        'status',
        'alasan_tolak',
    ];

    protected $casts = [
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
    ];

    public function jadwal(): BelongsTo
    {
        return $this->belongsTo(JadwalPekerjaan::class, 'jadwal_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function buktiPekerjaans(): HasMany
    {
        return $this->hasMany(BuktiPekerjaan::class, 'detail_pekerjaan_id');
    }
}
