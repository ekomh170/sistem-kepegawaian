<?php

namespace App\Filament\Resources\DetailPekerjaans\Pages;

use App\Filament\Resources\DetailPekerjaans\DetailPekerjaanResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewDetailPekerjaan extends ViewRecord
{
    protected static string $resource = DetailPekerjaanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()
                ->visible(fn () => \Illuminate\Support\Facades\Auth::user()?->isAdmin()),
        ];
    }
}
