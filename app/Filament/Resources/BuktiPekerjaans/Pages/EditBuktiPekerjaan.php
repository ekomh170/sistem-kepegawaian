<?php

namespace App\Filament\Resources\BuktiPekerjaans\Pages;

use App\Filament\Resources\BuktiPekerjaans\BuktiPekerjaanResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditBuktiPekerjaan extends EditRecord
{
    protected static string $resource = BuktiPekerjaanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
