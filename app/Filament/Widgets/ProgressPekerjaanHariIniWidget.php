<?php

namespace App\Filament\Widgets;

use App\Models\DetailPekerjaan;
use Filament\Widgets\Widget;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

class ProgressPekerjaanHariIniWidget extends Widget
{
    protected string $view = 'filament.widgets.progress-pekerjaan-hari-ini-widget';

    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = 'full';

    protected ?string $pollingInterval = '30s';

    public static function canView(): bool
    {
        return (Auth::user()?->isAdmin() || Auth::user()?->isSupervisor());
    }

    protected function getViewData(): array
    {
        $today = Carbon::today()->toDateString();

        $tugasHariIni = DetailPekerjaan::with(['user', 'jadwal', 'buktiPekerjaans'])
            ->join('tb_jadwal_pekerjaan', 'tb_jadwal_pekerjaan.id', '=', 'tb_detail_pekerjaan.jadwal_id')
            ->whereDate('tb_jadwal_pekerjaan.tanggal_kerja', $today)
            ->where('tb_jadwal_pekerjaan.hari_libur', false)
            ->select('tb_detail_pekerjaan.*')
            ->orderBy('tb_detail_pekerjaan.user_id')
            ->get();

        $list = $tugasHariIni->map(function ($tugas) {
            $adaBukti = $tugas->buktiPekerjaans->isNotEmpty();

            $labelStatus = match (true) {
                $tugas->status === 'disetujui'                => 'Selesai',
                $tugas->status === 'ditolak'                  => 'Ditolak',
                $adaBukti && $tugas->status === 'pending'     => 'Menunggu Verifikasi',
                $adaBukti                                      => 'Bukti Dikirim',
                default                                        => 'Belum dikerjakan',
            };

            $badgeWarna = match (true) {
                $tugas->status === 'disetujui'                => ['bg' => '#dcfce7', 'color' => '#15803d'],
                $tugas->status === 'ditolak'                  => ['bg' => '#fee2e2', 'color' => '#991b1b'],
                $adaBukti                                      => ['bg' => '#dbeafe', 'color' => '#1d4ed8'],
                default                                        => ['bg' => '#f3f4f6', 'color' => '#6b7280'],
            };

            return [
                'nama_lokasi' => $tugas->nama_lokasi ?? '-',
                'alamat_lokasi' => $tugas->alamat_lokasi ?? '',
                'karyawan' => $tugas->user?->nama ?? $tugas->user?->nik ?? '-',
                'label_status' => $labelStatus,
                'badge_warna' => $badgeWarna,
                'alasan_tolak' => $tugas->alasan_tolak,
                'jam_mulai' => $tugas->jadwal?->jam_masuk
                    ? Carbon::parse($tugas->jadwal->jam_masuk)->format('H:i')
                    : null,
            ];
        });

        $selesai = $list->where('label_status', 'Selesai')->count();
        $total = $list->count();

        return [
            'list' => $list,
            'selesai' => $selesai,
            'total' => $total,
        ];
    }
}
