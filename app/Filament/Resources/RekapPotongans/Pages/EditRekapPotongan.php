<?php

namespace App\Filament\Resources\RekapPotongans\Pages;

use App\Filament\Resources\RekapPotongans\RekapPotonganResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditRekapPotongan extends EditRecord
{
    protected static string $resource = RekapPotonganResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
