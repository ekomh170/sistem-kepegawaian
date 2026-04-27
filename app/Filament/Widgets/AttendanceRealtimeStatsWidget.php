<?php

namespace App\Filament\Widgets;

use App\Models\Presensi;
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

        $hadir = Presensi::query()
            ->where('tgl_presensi', $today)
            ->where('status', 'hadir')
            ->count();

        $terlambat = Presensi::query()
            ->where('tgl_presensi', $today)
            ->where('status', 'terlambat')
            ->count();

        $tidakHadir = Presensi::query()
            ->where('tgl_presensi', $today)
            ->where('status', 'tidak_hadir')
            ->count();

        $pendingVerifikasi = Presensi::query()
            ->where('tgl_presensi', $today)
            ->whereDoesntHave('verifikasi')
            ->count();

        return [
            Stat::make('Hadir Hari Ini', (string) $hadir)
                ->description('Data presensi real-time')
                ->color('success'),
            Stat::make('Terlambat', (string) $terlambat)
                ->description('Perlu perhatian supervisor')
                ->color('warning'),
            Stat::make('Tidak Hadir', (string) $tidakHadir)
                ->description('Tanpa check-in')
                ->color('danger'),
            Stat::make('Menunggu Verifikasi', (string) $pendingVerifikasi)
                ->description('Belum ada keputusan verifikasi')
                ->color('info'),
        ];
    }

    public static function canView(): bool
    {
        return in_array(Auth::user()?->role, ['admin', 'supervisor'], true);
    }
}
