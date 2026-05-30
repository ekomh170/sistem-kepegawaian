<?php

namespace App\Filament\Resources\Karyawans\Schemas;

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
                        TextEntry::make('nama')
                            ->label('Nama Lengkap')
                            ->weight('bold')
                            ->size('lg'),
                        TextEntry::make('email')
                            ->label('Email')
                            ->icon('heroicon-o-envelope')
                            ->copyable(),
                        TextEntry::make('role')
                            ->label('Role')
                            ->badge(),
                    ]),

                Section::make('Identitas Karyawan')
                    ->description('Data pribadi')
                    ->columns(3)
                    ->columnSpanFull()
                    ->schema([
                        TextEntry::make('nik')
                            ->label('NIK Karyawan')
                            ->weight('bold')
                            ->copyable(),
                        TextEntry::make('posisi')
                            ->label('Posisi / Jabatan')
                            ->default('-'),
                        TextEntry::make('no_hp')
                            ->label('No. HP')
                            ->icon('heroicon-o-phone')
                            ->default('-')
                            ->copyable(),
                        TextEntry::make('created_at')
                            ->label('Terdaftar Sejak')
                            ->dateTime('d M Y H:i')
                            ->icon('heroicon-o-clock'),
                    ]),
            ]);
    }
}
