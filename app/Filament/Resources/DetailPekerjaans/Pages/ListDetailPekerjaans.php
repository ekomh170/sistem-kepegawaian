<?php

namespace App\Filament\Resources\DetailPekerjaans\Pages;

use App\Filament\Resources\DetailPekerjaans\DetailPekerjaanResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListDetailPekerjaans extends ListRecords
{
    protected static string $resource = DetailPekerjaanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->visible(fn () => \Illuminate\Support\Facades\Auth::user()?->role === 'admin'),
        ];
    }
}
