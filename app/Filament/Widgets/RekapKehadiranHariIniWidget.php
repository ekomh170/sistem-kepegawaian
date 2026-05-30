<?php

namespace App\Filament\Widgets;

use App\Models\Presensi;
use App\Models\User;
use Filament\Widgets\Widget;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

class RekapKehadiranHariIniWidget extends Widget
{
    protected string $view = 'filament.widgets.rekap-kehadiran-hari-ini-widget';

    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    protected ?string $pollingInterval = '30s';

    public static function canView(): bool
    {
        return (Auth::user()?->isAdmin() || Auth::user()?->isSupervisor());
    }

    protected function getViewData(): array
    {
        $today = Carbon::today()->toDateString();

        $karyawans = User::where('role', 'karyawan')->get();

        $presensiMap = Presensi::where('tanggal', $today)
            ->get()
            ->keyBy('user_id');

        $list = $karyawans->map(function ($karyawan) use ($presensiMap) {
            $presensi = $presensiMap->get($karyawan->id);
            $nama = $karyawan->nama ?? '-';
            $inisial = collect(explode(' ', $nama))
                ->take(2)
                ->map(fn ($w) => strtoupper(substr($w, 0, 1)))
                ->join('');

            $status = 'belum';
            $jamMasuk = null;
            $menitTerlambat = null;

            if ($presensi) {
                $status = $presensi->status_presensi;
                $jamMasuk = $presensi->jam_masuk
                    ? Carbon::parse($presensi->jam_masuk)->format('H:i')
                    : null;
                $menitTerlambat = $presensi->menit_terlambat > 0 ? $presensi->menit_terlambat : null;
            }

            return [
                'nama' => $nama,
                'inisial' => $inisial,
                'posisi' => $karyawan->posisi ?? '-',
                'status' => $status,
                'jam_masuk' => $jamMasuk,
                'menit_terlambat' => $menitTerlambat,
            ];
        })->sortBy(fn ($item) => match ($item['status']) {
            'terlambat' => 0,
            'hadir' => 1,
            'izin' => 2,
            default => 3,
        })->values();

        $totalHadir = $list->whereIn('status', ['hadir', 'terlambat'])->count();

        return [
            'list' => $list,
            'total_hadir' => $totalHadir,
            'total_karyawan' => $karyawans->count(),
        ];
    }
}
