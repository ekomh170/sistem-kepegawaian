<?php

namespace App\Filament\Resources\Akuns\Pages;

use App\Filament\Resources\Akuns\AkunResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditAkun extends EditRecord
{
    protected static string $resource = AkunResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    /**
     * V3: posisi hanya untuk karyawan.
     */
    protected function mutateFormDataBeforeSave(array $data): array
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
