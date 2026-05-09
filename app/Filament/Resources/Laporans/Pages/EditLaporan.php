<?php

namespace App\Filament\Resources\Laporans\Pages;

use App\Filament\Resources\Laporans\LaporanResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditLaporan extends EditRecord
{
    protected static string $resource = LaporanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (empty($data['judul'])) {
            $data['judul'] = self::buildJudul($data);
        }

        return $data;
    }

    private static function buildJudul(array $data): string
    {
        $tipe = $data['tipe_laporan'] ?? 'presensi';
        $tipeName = match ($tipe) {
            'rekap_potongan' => 'Laporan Rekap Potongan',
            default => 'Laporan Presensi',
        };
        $jenis = $data['jenis'] ?? '';
        $periode = $data['periode'] ?? '';

        return "{$tipeName} {$jenis} {$periode}";
    }
}
