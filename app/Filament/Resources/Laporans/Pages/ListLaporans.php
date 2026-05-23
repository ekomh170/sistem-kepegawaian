<?php

namespace App\Filament\Resources\Laporans\Pages;

use App\Filament\Widgets\LaporanEvaluasiChartWidget;
use App\Filament\Resources\Laporans\LaporanResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Auth;

class ListLaporans extends ListRecords
{
    protected static string $resource = LaporanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->visible(fn (): bool => in_array(Auth::user()?->role, ['admin', 'supervisor'], true)),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            LaporanEvaluasiChartWidget::class,
        ];
    }
}
