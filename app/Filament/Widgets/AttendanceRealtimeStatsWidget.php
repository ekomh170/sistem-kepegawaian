<?php

namespace App\Filament\Widgets;

use App\Models\Karyawan;
use App\Models\Presensi;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

class AttendanceRealtimeStatsWidget extends StatsOverviewWidget
{
    protected ?string $pollingInterval = '15s';

    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $today = Carbon::today()->toDateString();
        $bulanIni = Carbon::now()->format('Y-m');

        $hadirHariIni = Presensi::where('tanggal', $today)
            ->whereIn('status_presensi', ['hadir', 'terlambat'])
            ->count();

        $terlambatHariIni = Presensi::where('tanggal', $today)
            ->where('status_presensi', 'terlambat')
            ->count();

        $tidakHadirHariIni = Presensi::where('tanggal', $today)
            ->where('status_presensi', 'tidak_hadir')
            ->count();

        $hadirBulanIni = Presensi::whereYear('tanggal', Carbon::now()->year)
            ->whereMonth('tanggal', Carbon::now()->month)
            ->whereIn('status_presensi', ['hadir', 'terlambat'])
            ->count();

        $terlambatBulanIni = Presensi::whereYear('tanggal', Carbon::now()->year)
            ->whereMonth('tanggal', Carbon::now()->month)
            ->where('status_presensi', 'terlambat')
            ->count();

        $alpaBulanIni = Presensi::whereYear('tanggal', Carbon::now()->year)
            ->whereMonth('tanggal', Carbon::now()->month)
            ->where('status_presensi', 'tidak_hadir')
            ->count();

        $totalKaryawan = Karyawan::count();

        return [
            Stat::make('Total Karyawan', (string) $totalKaryawan)
                ->description('Karyawan aktif terdaftar')
                ->descriptionIcon('heroicon-m-users')
                ->color('primary'),

            Stat::make('Hadir Hari Ini', $hadirHariIni > 0 ? (string) $hadirHariIni : 'Belum ada')
                ->description($hadirHariIni > 0 ? "dari {$totalKaryawan} karyawan" : 'Presensi belum dimulai')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),

            Stat::make('Terlambat Hari Ini', (string) $terlambatHariIni)
                ->description("Alpa: {$tidakHadirHariIni} orang")
                ->descriptionIcon('heroicon-m-clock')
                ->color('warning'),

            Stat::make('Hadir Bulan Ini', (string) $hadirBulanIni)
                ->description(Carbon::now()->translatedFormat('F Y'))
                ->descriptionIcon('heroicon-m-calendar-days')
                ->color('success'),

            Stat::make('Terlambat Bulan Ini', (string) $terlambatBulanIni)
                ->description("Alpa bulan ini: {$alpaBulanIni}")
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color('warning'),
        ];
    }

    public static function canView(): bool
    {
        return in_array(Auth::user()?->role, ['admin', 'supervisor'], true);
    }
}
