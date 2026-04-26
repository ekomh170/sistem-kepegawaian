<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Karyawan extends Model
{
    use SoftDeletes;

    protected $table = 'tb_karyawan';

    protected $fillable = [
        'user_id',
        'nik',
        'posisi_karyawan',
        'tgl_masuk',
        'status_kontrak',
        'no_hp',
        'bidang_tugas',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
