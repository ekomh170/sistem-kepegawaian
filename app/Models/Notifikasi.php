<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Notifikasi extends Model
{
    protected $table = 'tb_notifikasi';

    protected $fillable = [
        'user_id',
        'tipe',
        'pesan',
        'sudah_dibaca',
        'tanggal_kirim',
        'chanel',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
