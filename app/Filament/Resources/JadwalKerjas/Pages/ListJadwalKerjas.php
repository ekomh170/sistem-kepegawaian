<?php

namespace App\Filament\Resources\JadwalKerjas\Pages;

use App\Filament\Resources\JadwalKerjas\JadwalKerjaResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListJadwalKerjas extends ListRecords
{
    protected static string $resource = JadwalKerjaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
