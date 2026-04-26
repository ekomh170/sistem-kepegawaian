<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Supervisor extends Model
{
    protected $table = 'supervisors';

    protected $fillable = [
        'user_id',
        'jabatan',
        'level_akses',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
