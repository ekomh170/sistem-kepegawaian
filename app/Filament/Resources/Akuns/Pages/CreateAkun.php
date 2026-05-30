<?php

namespace App\Filament\Resources\Akuns\Pages;

use App\Filament\Resources\Akuns\AkunResource;
use Filament\Resources\Pages\CreateRecord;

class CreateAkun extends CreateRecord
{
    protected static string $resource = AkunResource::class;

    /**
     * V3: semua data (nama/email/password/role/nik/no_hp/posisi) langsung ke tb_user.
     * posisi hanya relevan untuk karyawan — di-null-kan untuk role lain.
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (($data['role'] ?? null) !== 'karyawan') {
            $data['posisi'] = null;
        }

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return AkunResource::getUrl('index');
    }
}
