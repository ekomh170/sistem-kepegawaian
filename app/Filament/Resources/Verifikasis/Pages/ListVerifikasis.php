<?php

namespace App\Filament\Resources\Verifikasis\Pages;

use App\Filament\Resources\Verifikasis\VerifikasiResource;
use Filament\Resources\Pages\ListRecords;

class ListVerifikasis extends ListRecords
{
    protected static string $resource = VerifikasiResource::class;

    /** Tidak ada tombol "Create" — antrian verifikasi bukan untuk membuat presensi. */
    protected function getHeaderActions(): array
    {
        return [];
    }
}
