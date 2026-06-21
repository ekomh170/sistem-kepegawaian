<?php

namespace App\Filament\Resources\BuktiPekerjaans\Schemas;

use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class BuktiPekerjaanInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informasi Bukti')
                    ->columns(2)
                    ->columnSpanFull()
                    ->schema([
                        TextEntry::make('user.nama')->label('Karyawan'),
                        TextEntry::make('detailPekerjaan.nama_lokasi')->label('Tugas / Lokasi'),
                        TextEntry::make('status')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'disetujui' => 'success',
                                'ditolak' => 'danger',
                                default => 'warning',
                            }),
                        TextEntry::make('uploaded_at')->label('Diupload')->dateTime('d M Y H:i'),
                        TextEntry::make('keterangan')->placeholder('-')->columnSpanFull(),
                    ]),

                Section::make('Galeri Foto Bukti')
                    ->columnSpanFull()
                    ->schema([
                        ImageEntry::make('foto_before')
                            ->label('Foto Sebelum')
                            ->disk('public')
                            ->height(140)
                            ->columnSpanFull()
                            ->visible(fn ($record): bool => is_array($record->foto_before) && count($record->foto_before) > 0),
                        ImageEntry::make('foto_after')
                            ->label('Foto Sesudah')
                            ->disk('public')
                            ->height(140)
                            ->columnSpanFull()
                            ->visible(fn ($record): bool => is_array($record->foto_after) && count($record->foto_after) > 0),

                        // Fallback data lama: galeri gabungan.
                        ImageEntry::make('foto')
                            ->label('Foto Bukti (lama)')
                            ->disk('public')
                            ->height(140)
                            ->columnSpanFull()
                            ->visible(fn ($record): bool => is_array($record->foto) && count($record->foto) > 0),

                        TextEntry::make('kosong')
                            ->label('')
                            ->state('Belum ada foto bukti.')
                            ->visible(fn ($record): bool => empty($record->foto_before) && empty($record->foto_after) && empty($record->foto)),
                    ]),
            ]);
    }
}
