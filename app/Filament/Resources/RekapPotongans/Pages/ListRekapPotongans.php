<?php

namespace App\Filament\Resources\RekapPotongans\Pages;

use App\Filament\Resources\RekapPotongans\RekapPotonganResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListRekapPotongans extends ListRecords
{
    protected static string $resource = RekapPotonganResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
