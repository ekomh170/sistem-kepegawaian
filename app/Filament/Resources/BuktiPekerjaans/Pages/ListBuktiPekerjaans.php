<?php

namespace App\Filament\Resources\BuktiPekerjaans\Pages;

use App\Filament\Resources\BuktiPekerjaans\BuktiPekerjaanResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListBuktiPekerjaans extends ListRecords
{
    protected static string $resource = BuktiPekerjaanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->visible(fn () => \Illuminate\Support\Facades\Auth::user()?->isAdmin()),
        ];
    }
}
