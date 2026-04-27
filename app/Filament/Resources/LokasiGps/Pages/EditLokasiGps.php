<?php

namespace App\Filament\Resources\LokasiGps\Pages;

use App\Filament\Resources\LokasiGps\LokasiGpsResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditLokasiGps extends EditRecord
{
    protected static string $resource = LokasiGpsResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
