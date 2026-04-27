<?php

namespace App\Filament\Resources\Laporans\Pages;

use App\Filament\Widgets\LaporanEvaluasiChartWidget;
use App\Filament\Resources\Laporans\LaporanResource;
use Filament\Actions\Action;
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
                ->visible(fn (): bool => Auth::user()?->role === 'admin'),
            Action::make('export_csv')
                ->label('Export CSV')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->url(fn (): string => route('laporan.export'), shouldOpenInNewTab: true),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            LaporanEvaluasiChartWidget::class,
        ];
    }
}
