<?php

namespace App\Filament\Widgets;

use App\Models\RekapPotongan;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class LaporanEvaluasiChartWidget extends ChartWidget
{
    protected ?string $heading = 'Grafik Evaluasi Kehadiran per Bulan';

    protected int|string|array $columnSpan = 'full';

    protected ?string $maxHeight = '350px';

    protected function getData(): array
    {
        $rekap = RekapPotongan::query()
            ->select(
                'periode',
                DB::raw('SUM(jumlah_hadir) as total_hadir'),
                DB::raw('SUM(jumlah_terlambat) as total_terlambat'),
                DB::raw('SUM(jumlah_tidak_hadir) as total_tidak_hadir'),
                DB::raw('SUM(total_potongan_keterlambatan) as total_potongan')
            )
            ->groupBy('periode')
            ->orderBy('periode')
            ->limit(6)
            ->get();

        return [
            'datasets' => [
                [
                    'label' => 'Hadir',
                    'data' => $rekap->pluck('total_hadir')->map(fn ($item) => (int) $item)->all(),
                    'borderColor' => '#16a34a',
                    'backgroundColor' => 'rgba(22, 163, 74, 0.2)',
                    'fill' => true,
                    'tension' => 0.3,
                ],
                [
                    'label' => 'Terlambat',
                    'data' => $rekap->pluck('total_terlambat')->map(fn ($item) => (int) $item)->all(),
                    'borderColor' => '#f97316',
                    'backgroundColor' => 'rgba(249, 115, 22, 0.2)',
                    'fill' => true,
                    'tension' => 0.3,
                ],
                [
                    'label' => 'Tidak Hadir',
                    'data' => $rekap->pluck('total_tidak_hadir')->map(fn ($item) => (int) $item)->all(),
                    'borderColor' => '#ef4444',
                    'backgroundColor' => 'rgba(239, 68, 68, 0.2)',
                    'fill' => true,
                    'tension' => 0.3,
                ],
            ],
            'labels' => $rekap->pluck('periode')->map(fn ($p) => \Carbon\Carbon::parse($p . '-01')->translatedFormat('F Y'))->all(),
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
