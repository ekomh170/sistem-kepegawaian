<?php

namespace App\Filament\Resources\LaporanPresensis\Pages;

use App\Filament\Widgets\LaporanEvaluasiChartWidget;
use App\Filament\Resources\LaporanPresensis\LaporanPresensiResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Auth;

class ListLaporans extends ListRecords
{
    protected static string $resource = LaporanPresensiResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->visible(fn (): bool => (Auth::user()?->isAdmin() || Auth::user()?->isSupervisor())),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            LaporanEvaluasiChartWidget::class,
        ];
    }
}
