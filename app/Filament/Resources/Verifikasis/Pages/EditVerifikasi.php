<?php

namespace App\Filament\Resources\Verifikasis\Pages;

use App\Filament\Resources\Verifikasis\VerifikasiResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditVerifikasi extends EditRecord
{
    protected static string $resource = VerifikasiResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
