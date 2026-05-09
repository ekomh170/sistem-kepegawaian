<?php

namespace App\Filament\Widgets;

use App\Models\Karyawan;
use App\Models\Presensi;
use App\Models\RekapPotongan;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

class AttendanceRealtimeStatsWidget extends StatsOverviewWidget
{
    protected ?string $pollingInterval = '15s';

    protected function getStats(): array
    {
        $today = Carbon::today()->toDateString();
        $bulanIni = Carbon::now()->format('Y-m');
        $bulanLalu = Carbon::now()->subMonth()->format('Y-m');

        // Data hari ini
        $hadirHariIni = Presensi::where('tgl_presensi', $today)
            ->whereIn('status', ['hadir', 'terlambat'])
            ->count();

        $terlambatHariIni = Presensi::where('tgl_presensi', $today)
            ->where('status', 'terlambat')
            ->count();

        // Data bulan ini
        $hadirBulanIni = Presensi::whereRaw("DATE_FORMAT(tgl_presensi, '%Y-%m') = ?", [$bulanIni])
            ->whereIn('status', ['hadir', 'terlambat'])
            ->count();

        $terlambatBulanIni = Presensi::whereRaw("DATE_FORMAT(tgl_presensi, '%Y-%m') = ?", [$bulanIni])
            ->where('status', 'terlambat')
            ->count();

        $alpaBulanIni = Presensi::whereRaw("DATE_FORMAT(tgl_presensi, '%Y-%m') = ?", [$bulanIni])
            ->where('status', 'tidak_hadir')
            ->count();

        // Total karyawan aktif
        $totalKaryawan = Karyawan::count();

        // Total potongan bulan lalu
        $totalPotonganBulanLalu = RekapPotongan::where('periode', $bulanLalu)
            ->sum('total_potongan_keterlambatan');

        // Trend hadir vs bulan lalu
        $hadirBulanLalu = Presensi::whereRaw("DATE_FORMAT(tgl_presensi, '%Y-%m') = ?", [$bulanLalu])
            ->whereIn('status', ['hadir', 'terlambat'])
            ->count();

        $trendHadir = $hadirBulanLalu > 0
            ? round((($hadirBulanIni - $hadirBulanLalu) / $hadirBulanLalu) * 100, 1)
            : 0;

        $trendIcon = $trendHadir >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down';
        $trendColor = $trendHadir >= 0 ? 'success' : 'danger';

        return [
            Stat::make('Total Karyawan', (string) $totalKaryawan)
                ->description('Karyawan aktif terdaftar')
                ->descriptionIcon('heroicon-m-users')
                ->color('primary'),

            Stat::make('Hadir Hari Ini', $hadirHariIni > 0 ? (string) $hadirHariIni : 'Belum ada')
                ->description($hadirHariIni > 0 ? "dari {$totalKaryawan} karyawan" : 'Presensi belum dimulai')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),

            Stat::make('Hadir Bulan Ini', (string) $hadirBulanIni)
                ->description($trendHadir != 0 ? ($trendHadir > 0 ? "+{$trendHadir}%" : "{$trendHadir}%") . ' vs bulan lalu' : 'Data bulan berjalan')
                ->descriptionIcon($trendIcon)
                ->color($trendColor),

            Stat::make('Terlambat Bulan Ini', (string) $terlambatBulanIni)
                ->description("Hari ini: {$terlambatHariIni} orang")
                ->descriptionIcon('heroicon-m-clock')
                ->color('warning'),

            Stat::make('Alpa Bulan Ini', (string) $alpaBulanIni)
                ->description('Tanpa keterangan')
                ->descriptionIcon('heroicon-m-x-circle')
                ->color('danger'),

            Stat::make('Total Potongan Bulan Lalu', 'Rp ' . number_format($totalPotonganBulanLalu, 0, ',', '.'))
                ->description("Periode {$bulanLalu}")
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('info'),
        ];
    }

    public static function canView(): bool
    {
        return in_array(Auth::user()?->role, ['admin', 'supervisor'], true);
    }
}
