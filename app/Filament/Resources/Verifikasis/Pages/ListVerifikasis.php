<?php

namespace App\Filament\Resources\Verifikasis\Pages;

use App\Filament\Resources\Verifikasis\VerifikasiResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListVerifikasis extends ListRecords
{
    protected static string $resource = VerifikasiResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
