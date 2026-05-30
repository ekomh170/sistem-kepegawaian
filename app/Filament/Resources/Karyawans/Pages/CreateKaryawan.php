<?php

namespace App\Filament\Resources\Karyawans\Pages;

use App\Filament\Resources\Karyawans\KaryawanResource;
use Filament\Resources\Pages\CreateRecord;

class CreateKaryawan extends CreateRecord
{
    protected static string $resource = KaryawanResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['role'] = 'karyawan';

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return KaryawanResource::getUrl('index');
    }
}
