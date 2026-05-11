<?php

namespace App\Filament\Resources\Jadwals\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;

class JadwalForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Penugasan Jadwal')
                    ->columns(2)
                    ->columnSpanFull()
                    ->schema([
                        Select::make('karyawan_id')
                            ->label('Karyawan')
                            ->relationship('karyawan', 'nik')
                            ->getOptionLabelFromRecordUsing(fn ($record) => "{$record->nik} - " . ($record->user?->nama ?? 'Unknown'))
                            ->searchable(['nik'])
                            ->preload()
                            ->required(),
                        Select::make('admin_id')
                            ->label('Admin Pembuat')
                            ->relationship('admin', 'nik')
                            ->getOptionLabelFromRecordUsing(fn ($record) => "{$record->nik} - " . ($record->user?->nama ?? 'Unknown'))
                            ->searchable(['nik'])
                            ->preload()
                            ->default(fn () => \App\Models\Admin::where('user_id', Auth::id())->first()?->id),
                    ]),

                Section::make('Tanggal & Jam Kerja')
                    ->description('Sesuai kebijakan: 6 hari kerja per minggu, 1 hari libur ditentukan admin, jam kerja default 08.00–16.00.')
                    ->columns(3)
                    ->columnSpanFull()
                    ->schema([
                        DatePicker::make('tanggal_kerja')
                            ->required()
                            ->native(false),
                        TimePicker::make('jam_masuk')
                            ->required()
                            ->seconds(false)
                            ->default('08:00'),
                        TimePicker::make('jam_pulang')
                            ->required()
                            ->seconds(false)
                            ->default('16:00'),
                    ]),

                Section::make('Status')
                    ->columns(2)
                    ->columnSpanFull()
                    ->schema([
                        Toggle::make('hari_libur')
                            ->label('Tandai Hari Libur')
                            ->helperText('Jika aktif, presensi tidak diharapkan pada tanggal ini.')
                            ->default(false),
                        Select::make('status')
                            ->options([
                                'aktif' => 'Aktif',
                                'dibatalkan' => 'Dibatalkan',
                            ])
                            ->default('aktif')
                            ->required(),
                    ]),
            ]);
    }
}
