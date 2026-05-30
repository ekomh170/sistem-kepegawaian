<?php

namespace App\Filament\Resources\Presensis\Pages;

use App\Filament\Resources\Presensis\PresensiResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Auth;

class EditPresensi extends EditRecord
{
    protected static string $resource = PresensiResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
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
