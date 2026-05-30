<?php

namespace App\Filament\Resources\JadwalPekerjaans\Pages;

use App\Filament\Resources\JadwalPekerjaans\JadwalPekerjaanResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListJadwals extends ListRecords
{
    protected static string $resource = JadwalPekerjaanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
