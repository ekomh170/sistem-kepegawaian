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
        'karyawan_id',
        'tanggal',
        'jam_masuk',
        'jam_pulang',
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
        'tanggal' => 'date',
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
    ];

    public function karyawan(): BelongsTo
    {
        return $this->belongsTo(Karyawan::class);
    }

    public function buktiPekerjaans(): HasMany
    {
        return $this->hasMany(BuktiPekerjaan::class, 'detail_pekerjaan_id');
    }
}
