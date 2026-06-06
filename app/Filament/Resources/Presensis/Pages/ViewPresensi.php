<?php

namespace App\Filament\Resources\Presensis\Pages;

use App\Filament\Resources\Presensis\PresensiResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\Auth;

class ViewPresensi extends ViewRecord
{
    protected static string $resource = PresensiResource::class;

    public function getTitle(): string
    {
        $nama = $this->record->user?->nama;
        $tgl = $this->record->tanggal?->translatedFormat('d M Y');

        return 'Detail Presensi' . ($nama ? ' — ' . $nama : '') . ($tgl ? ' (' . $tgl . ')' : '');
    }

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()
                ->visible(fn () => (Auth::user()?->isAdmin() || Auth::user()?->isSupervisor())),
        ];
    }
}
