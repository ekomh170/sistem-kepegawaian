<?php

namespace App\Filament\Resources\LokasiGps\Pages;

use App\Filament\Resources\LokasiGps\LokasiGpsResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListLokasiGps extends ListRecords
{
    protected static string $resource = LokasiGpsResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
