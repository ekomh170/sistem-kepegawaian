<?php

namespace App\Filament\Resources\Karyawans\Schemas;

use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class KaryawanInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Data Akun')
                    ->description('Informasi login')
                    ->columns(2)
                    ->columnSpanFull()
                    ->schema([
                        TextEntry::make('user.nama')
                            ->label('Nama Lengkap')
                            ->weight('bold')
                            ->size('lg'),
                        TextEntry::make('user.email')
                            ->label('Email')
                            ->icon('heroicon-o-envelope')
                            ->copyable(),
                        TextEntry::make('user.role')
                            ->label('Role')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'admin' => 'danger',
                                'supervisor' => 'warning',
                                'karyawan' => 'success',
                                default => 'gray',
                            })
                            ->formatStateUsing(fn (string $state): string => ucfirst($state)),
                    ]),

                Section::make('Identitas Karyawan')
                    ->description('Data pribadi')
                    ->columns(3)
                    ->columnSpanFull()
                    ->schema([
                        ImageEntry::make('foto')
                            ->label('Foto')
                            ->circular()
                            ->defaultImageUrl(fn ($record) => 'https://ui-avatars.com/api/?name=' . urlencode($record->user?->nama ?? 'K') . '&background=6366f1&color=fff'),
                        TextEntry::make('nik')
                            ->label('NIK Karyawan')
                            ->weight('bold')
                            ->copyable(),
                        TextEntry::make('no_ktp')
                            ->label('No. KTP')
                            ->default('-')
                            ->copyable(),
                        TextEntry::make('posisi_karyawan')
                            ->label('Posisi / Jabatan'),
                        TextEntry::make('bidang_tugas')
                            ->label('Bidang Tugas'),
                        TextEntry::make('no_hp')
                            ->label('No. HP')
                            ->icon('heroicon-o-phone')
                            ->copyable(),
                        TextEntry::make('alamat')
                            ->label('Alamat')
                            ->default('-')
                            ->columnSpanFull(),
                    ]),

                Section::make('Kepegawaian')
                    ->description('Status kontrak')
                    ->columns(3)
                    ->columnSpanFull()
                    ->schema([
                        TextEntry::make('tgl_masuk')
                            ->label('Tanggal Masuk')
                            ->date('d F Y')
                            ->icon('heroicon-o-calendar'),
                        TextEntry::make('status_kontrak')
                            ->label('Status Kontrak')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'tetap' => 'success',
                                'kontrak' => 'warning',
                                default => 'gray',
                            })
                            ->formatStateUsing(fn (string $state): string => ucfirst($state)),
                        TextEntry::make('created_at')
                            ->label('Terdaftar Sejak')
                            ->dateTime('d M Y H:i')
                            ->icon('heroicon-o-clock'),
                    ]),
            ]);
    }
}
