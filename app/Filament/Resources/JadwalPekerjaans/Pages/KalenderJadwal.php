<?php

namespace App\Filament\Resources\JadwalPekerjaans\Pages;

use App\Filament\Resources\JadwalPekerjaans\JadwalPekerjaanResource;
use App\Models\JadwalPekerjaan;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Resources\Pages\Page;

class KalenderJadwal extends Page
{
    protected static string $resource = JadwalPekerjaanResource::class;

    protected string $view = 'filament.resources.jadwal-pekerjaans.pages.kalender-jadwal';

    protected static ?string $title = 'Kalender Jadwal';

    /** Bulan aktif (format Y-m). */
    public string $bulan = '';

    public function mount(): void
    {
        $this->bulan = now()->format('Y-m');
    }

    public function bulanSebelumnya(): void
    {
        $this->bulan = Carbon::parse($this->bulan . '-01')->subMonthNoOverflow()->format('Y-m');
    }

    public function bulanBerikutnya(): void
    {
        $this->bulan = Carbon::parse($this->bulan . '-01')->addMonthNoOverflow()->format('Y-m');
    }

    public function bulanIni(): void
    {
        $this->bulan = now()->format('Y-m');
    }

    public function getNamaBulan(): string
    {
        return Carbon::parse($this->bulan . '-01')->translatedFormat('F Y');
    }

    /**
     * Susun grid kalender: array minggu, tiap minggu 7 hari berisi jadwalnya.
     *
     * @return array<int, array<int, array<string, mixed>>>
     */
    public function getMinggu(): array
    {
        $awalBulan = Carbon::parse($this->bulan . '-01')->startOfMonth();
        $awalGrid = $awalBulan->copy()->startOfWeek(Carbon::MONDAY);
        $akhirGrid = $awalBulan->copy()->endOfMonth()->endOfWeek(Carbon::SUNDAY);

        $jadwal = JadwalPekerjaan::query()
            ->with('user')
            ->whereBetween('tanggal_kerja', [$awalGrid->toDateString(), $akhirGrid->toDateString()])
            ->orderBy('jam_masuk')
            ->get()
            ->groupBy(fn ($j) => Carbon::parse($j->tanggal_kerja)->toDateString());

        $minggu = [];
        $cursor = $awalGrid->copy();

        while ($cursor->lte($akhirGrid)) {
            $baris = [];
            for ($i = 0; $i < 7; $i++) {
                $items = $jadwal->get($cursor->toDateString(), collect());
                $baris[] = [
                    'tanggal' => $cursor->copy(),
                    'dalamBulan' => $cursor->month === $awalBulan->month,
                    'hariIni' => $cursor->isToday(),
                    'jadwal' => $items,
                    'libur' => $items->contains(fn ($j) => (bool) $j->hari_libur),
                ];
                $cursor->addDay();
            }
            $minggu[] = $baris;
        }

        return $minggu;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('tabel')
                ->label('Lihat Tabel')
                ->icon('heroicon-o-table-cells')
                ->color('gray')
                ->url(JadwalPekerjaanResource::getUrl('index')),
        ];
    }
}
