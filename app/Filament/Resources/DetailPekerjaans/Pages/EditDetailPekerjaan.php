<?php

namespace App\Filament\Resources\DetailPekerjaans\Pages;

use App\Filament\Resources\DetailPekerjaans\DetailPekerjaanResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditDetailPekerjaan extends EditRecord
{
    protected static string $resource = DetailPekerjaanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
