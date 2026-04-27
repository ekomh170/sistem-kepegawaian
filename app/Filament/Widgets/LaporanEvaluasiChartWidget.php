<?php

namespace App\Filament\Widgets;

use App\Models\Laporan;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class LaporanEvaluasiChartWidget extends ChartWidget
{
    protected ?string $heading = 'Grafik Evaluasi Kehadiran';

    protected int|string|array $columnSpan = 'full';

    protected function getData(): array
    {
        $rekap = Laporan::query()
            ->select(
                'periode',
                DB::raw('AVG(total_hadir) as rata_hadir'),
                DB::raw('AVG(total_terlambat) as rata_terlambat')
            )
            ->groupBy('periode')
            ->orderBy('periode')
            ->limit(6)
            ->get();

        return [
            'datasets' => [
                [
                    'label' => 'Rata-rata Hadir',
                    'data' => $rekap->pluck('rata_hadir')->map(fn ($item) => (int) round($item))->all(),
                    'borderColor' => '#16a34a',
                    'backgroundColor' => 'rgba(22, 163, 74, 0.15)',
                ],
                [
                    'label' => 'Rata-rata Terlambat',
                    'data' => $rekap->pluck('rata_terlambat')->map(fn ($item) => (int) round($item))->all(),
                    'borderColor' => '#f97316',
                    'backgroundColor' => 'rgba(249, 115, 22, 0.15)',
                ],
            ],
            'labels' => $rekap->pluck('periode')->all(),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }

    public static function canView(): bool
    {
        return in_array(Auth::user()?->role, ['admin', 'supervisor'], true);
    }
}
