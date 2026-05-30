<?php

namespace App\Filament\Resources\LaporanPresensis\Pages;

use App\Filament\Resources\LaporanPresensis\LaporanPresensiResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditLaporan extends EditRecord
{
    protected static string $resource = LaporanPresensiResource::class;

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
            'rekap_presensi_bulanan' => 'Laporan Rekap Presensi Bulanan',
            'rekap_pekerjaan'        => 'Laporan Rekap Pekerjaan',
            default                  => 'Laporan Presensi',
        };
        $jenis = $data['jenis'] ?? '';
        $periode = $data['periode'] ?? '';

        return trim("{$tipeName} {$jenis} {$periode}");
    }
}
