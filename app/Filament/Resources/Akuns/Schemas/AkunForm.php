<?php

namespace App\Filament\Resources\Akuns\Schemas;

use App\Models\Admin;
use App\Models\Karyawan;
use App\Models\User;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

class AkunForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Data Akun')
                    ->columns(2)
                    ->schema([
                        TextInput::make('nama')
                            ->label('Nama')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('email')
                            ->email()
                            ->required()
                            ->maxLength(255)
                            ->unique(table: User::class, column: 'email'),
                        TextInput::make('password')
                            ->password()
                            ->revealable()
                            ->required()
                            ->minLength(8)
                            ->maxLength(255),
                        Select::make('role')
                            ->options([
                                'karyawan' => 'Karyawan',
                                'admin' => 'Admin',
                                'supervisor' => 'Supervisor',
                            ])
                            ->required()
                            ->live()
                            ->afterStateUpdated(function (Set $set): void {
                                $set('admin_nip', null);
                                $set('admin_divisi', null);
                                $set('admin_level_akses', null);
                                $set('supervisor_jabatan', null);
                                $set('supervisor_level_akses', null);
                                $set('karyawan_nik', null);
                                $set('karyawan_posisi_karyawan', null);
                                $set('karyawan_tgl_masuk', null);
                                $set('karyawan_status_kontrak', null);
                                $set('karyawan_no_hp', null);
                                $set('karyawan_bidang_tugas', null);
                            }),
                    ]),
                Section::make('Data Admin')
                    ->columns(2)
                    ->visible(fn (Get $get): bool => $get('role') === 'admin')
                    ->schema([
                        TextInput::make('admin_nip')
                            ->label('NIP')
                            ->required(fn (Get $get): bool => $get('role') === 'admin')
                            ->maxLength(255)
                            ->unique(table: Admin::class, column: 'nip'),
                        TextInput::make('admin_divisi')
                            ->label('Divisi')
                            ->required(fn (Get $get): bool => $get('role') === 'admin')
                            ->maxLength(255),
                        Select::make('admin_level_akses')
                            ->label('Level Akses')
                            ->options([
                                'dasar' => 'Dasar',
                                'menengah' => 'Menengah',
                                'penuh' => 'Penuh',
                            ])
                            ->required(fn (Get $get): bool => $get('role') === 'admin'),
                    ]),
                Section::make('Data Supervisor')
                    ->columns(2)
                    ->visible(fn (Get $get): bool => $get('role') === 'supervisor')
                    ->schema([
                        TextInput::make('supervisor_jabatan')
                            ->label('Jabatan')
                            ->required(fn (Get $get): bool => $get('role') === 'supervisor')
                            ->maxLength(255),
                        Select::make('supervisor_level_akses')
                            ->label('Level Akses')
                            ->options([
                                'dasar' => 'Dasar',
                                'menengah' => 'Menengah',
                                'penuh' => 'Penuh',
                            ])
                            ->required(fn (Get $get): bool => $get('role') === 'supervisor'),
                    ]),
                Section::make('Data Karyawan')
                    ->columns(2)
                    ->visible(fn (Get $get): bool => $get('role') === 'karyawan')
                    ->schema([
                        TextInput::make('karyawan_nik')
                            ->label('NIK')
                            ->required(fn (Get $get): bool => $get('role') === 'karyawan')
                            ->maxLength(255)
                            ->unique(table: Karyawan::class, column: 'nik'),
                        TextInput::make('karyawan_posisi_karyawan')
                            ->label('Posisi Karyawan')
                            ->required(fn (Get $get): bool => $get('role') === 'karyawan')
                            ->maxLength(255),
                        DatePicker::make('karyawan_tgl_masuk')
                            ->label('Tanggal Masuk')
                            ->required(fn (Get $get): bool => $get('role') === 'karyawan'),
                        Select::make('karyawan_status_kontrak')
                            ->label('Status Kontrak')
                            ->options([
                                'kontrak' => 'Kontrak',
                                'tetap' => 'Tetap',
                            ])
                            ->required(fn (Get $get): bool => $get('role') === 'karyawan'),
                        TextInput::make('karyawan_no_hp')
                            ->label('No. HP')
                            ->required(fn (Get $get): bool => $get('role') === 'karyawan')
                            ->maxLength(30),
                        TextInput::make('karyawan_bidang_tugas')
                            ->label('Bidang Tugas')
                            ->required(fn (Get $get): bool => $get('role') === 'karyawan')
                            ->maxLength(255),
                    ]),
            ]);
    }
}
