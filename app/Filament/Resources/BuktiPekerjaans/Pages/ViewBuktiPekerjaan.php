<?php

namespace App\Filament\Resources\BuktiPekerjaans\Pages;

use App\Filament\Resources\BuktiPekerjaans\BuktiPekerjaanResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\Auth;

class ViewBuktiPekerjaan extends ViewRecord
{
    protected static string $resource = BuktiPekerjaanResource::class;

    public function getTitle(): string
    {
        $lokasi = $this->record->detailPekerjaan?->nama_lokasi;

        return 'Detail Bukti' . ($lokasi ? ' — ' . $lokasi : ' #' . $this->record->id);
    }

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()->visible(fn () => Auth::user()?->isAdmin()),
        ];
    }
}
