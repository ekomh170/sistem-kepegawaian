<?php

namespace App\Filament\Widgets;

use App\Models\DetailPekerjaan;
use Filament\Widgets\Widget;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

class ProgressPekerjaanHariIniWidget extends Widget
{
    protected string $view = 'filament.widgets.progress-pekerjaan-hari-ini-widget';

    protected int|string|array $columnSpan = 'full';

    protected ?string $pollingInterval = '30s';

    public static function canView(): bool
    {
        return in_array(Auth::user()?->role, ['admin', 'supervisor'], true);
    }

    protected function getViewData(): array
    {
        $today = Carbon::today()->toDateString();

        $tugasHariIni = DetailPekerjaan::with(['karyawan.user', 'jadwal', 'buktiPekerjaans'])
            ->join('tb_jadwal', 'tb_jadwal.id', '=', 'tb_detail_pekerjaan.jadwal_id')
            ->where('tb_jadwal.tanggal_kerja', $today)
            ->where('tb_jadwal.hari_libur', false)
            ->select('tb_detail_pekerjaan.*')
            ->orderBy('tb_detail_pekerjaan.karyawan_id')
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
                'karyawan' => $tugas->karyawan?->user?->nama ?? $tugas->karyawan?->nik ?? '-',
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
