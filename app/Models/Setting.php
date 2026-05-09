<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    protected $table = 'tb_setting';

    protected $fillable = [
        'key',
        'value',
        'group',
        'label',
        'type',
    ];

    /**
     * Helper untuk mengambil nilai setting dengan cache agar tidak berat
     */
    public static function get($key, $default = null)
    {
        return Cache::rememberForever("setting_{$key}", function () use ($key, $default) {
            $setting = self::where('key', $key)->first();
            return $setting ? $setting->value : $default;
        });
    }

    /**
     * Helper untuk update setting sekaligus hapus cache
     */
    public static function set($key, $value)
    {
        $setting = self::updateOrCreate(['key' => $key], ['value' => $value]);
        Cache::forget("setting_{$key}");
        return $setting;
    }

    /**
     * Helper untuk menghapus semua cache setting
     */
    public static function clearCache()
    {
        self::all()->each(function ($setting) {
            Cache::forget("setting_{$setting->key}");
        });
    }
}
