<?php

namespace App\Filament\Resources\Laporans\Pages;

use App\Filament\Resources\Laporans\LaporanResource;
use Filament\Resources\Pages\CreateRecord;

class CreateLaporan extends CreateRecord
{
    protected static string $resource = LaporanResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (empty($data['judul'])) {
            $data['judul'] = self::buildJudul($data);
        }

        if (empty($data['file_path'])) {
            $data['file_path'] = 'laporan/' . ($data['periode'] ?? 'unknown') . '.pdf';
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
