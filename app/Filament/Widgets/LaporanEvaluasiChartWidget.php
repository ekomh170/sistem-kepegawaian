<?php

namespace App\Filament\Widgets;

use App\Models\Presensi;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class LaporanEvaluasiChartWidget extends ChartWidget
{
    protected ?string $heading = 'Grafik Evaluasi Kehadiran per Bulan';

    protected static ?int $sort = 4;

    protected int|string|array $columnSpan = 'full';

    protected ?string $maxHeight = '350px';

    protected function getData(): array
    {
        // V3: agregasi langsung dari tb_presensi per bulan (tidak ada tabel rekap terpisah)
        $rekap = Presensi::query()
            ->select(
                DB::raw("DATE_FORMAT(tanggal, '%Y-%m') as periode"),
                DB::raw("SUM(CASE WHEN status_presensi IN ('hadir','terlambat') THEN 1 ELSE 0 END) as total_hadir"),
                DB::raw("SUM(CASE WHEN status_presensi = 'terlambat' THEN 1 ELSE 0 END) as total_terlambat"),
                DB::raw("SUM(CASE WHEN status_presensi = 'tidak_hadir' THEN 1 ELSE 0 END) as total_tidak_hadir")
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
        return (Auth::user()?->isAdmin() || Auth::user()?->isSupervisor());
    }
}
