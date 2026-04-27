<?php

namespace App\Filament\Resources\JadwalKerjas\Pages;

use App\Filament\Resources\JadwalKerjas\JadwalKerjaResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditJadwalKerja extends EditRecord
{
    protected static string $resource = JadwalKerjaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
