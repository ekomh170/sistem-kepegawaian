<?php

namespace App\Filament\Resources\Presensis\Pages;

use App\Filament\Resources\Presensis\PresensiResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\Auth;

class ViewPresensi extends ViewRecord
{
    protected static string $resource = PresensiResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()
                ->visible(fn () => (Auth::user()?->isAdmin() || Auth::user()?->isSupervisor())),
        ];
    }
}
