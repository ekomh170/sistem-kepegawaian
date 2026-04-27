<?php

namespace App\Filament\Resources\Notifikasis\Pages;

use App\Filament\Resources\Notifikasis\NotifikasiResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListNotifikasis extends ListRecords
{
    protected static string $resource = NotifikasiResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
