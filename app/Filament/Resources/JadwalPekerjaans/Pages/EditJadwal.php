<?php

namespace App\Filament\Resources\JadwalPekerjaans\Pages;

use App\Filament\Resources\JadwalPekerjaans\JadwalPekerjaanResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditJadwal extends EditRecord
{
    protected static string $resource = JadwalPekerjaanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
