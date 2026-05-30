<?php

namespace App\Filament\Resources\Presensis\Pages;

use App\Filament\Resources\Presensis\PresensiResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreatePresensi extends CreateRecord
{
    protected static string $resource = PresensiResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return self::isiVerifikator($data);
    }

    /**
     * Saat status verifikasi != pending, isi otomatis verifikator + tanggal.
     */
    public static function isiVerifikator(array $data): array
    {
        if (($data['status_verifikasi'] ?? 'pending') !== 'pending') {
            $data['diverifikasi_oleh'] = $data['diverifikasi_oleh'] ?? Auth::id();
            $data['tgl_verifikasi'] = $data['tgl_verifikasi'] ?? now();
        } else {
            $data['diverifikasi_oleh'] = null;
            $data['tgl_verifikasi'] = null;
        }

        return $data;
    }
}
