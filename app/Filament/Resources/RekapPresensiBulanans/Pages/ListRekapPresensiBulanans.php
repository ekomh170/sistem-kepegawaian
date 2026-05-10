<?php

namespace App\Filament\Resources\RekapPresensiBulanans\Pages;

use App\Filament\Resources\RekapPresensiBulanans\RekapPresensiBulananResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListRekapPresensiBulanans extends ListRecords
{
    protected static string $resource = RekapPresensiBulananResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
