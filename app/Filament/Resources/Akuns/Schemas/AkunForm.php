<?php

namespace App\Filament\Resources\Akuns\Schemas;

use App\Models\Admin;
use App\Models\Karyawan;
use App\Models\User;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
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
                    ->description('Informasi login dan peran pengguna')
                    ->columns(2)
                    ->columnSpanFull()
                    ->schema([
                        TextInput::make('nama')
                            ->label('Nama Lengkap')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('Masukkan nama lengkap')
                            ->columnSpanFull(),
                        TextInput::make('email')
                            ->email()
                            ->required()
                            ->maxLength(255)
                            ->unique(table: User::class, column: 'email', ignoreRecord: true)
                            ->placeholder('contoh@email.com'),
                        TextInput::make('password')
                            ->password()
                            ->revealable()
                            ->required(fn ($context) => $context === 'create')
                            ->dehydrated(fn ($state) => filled($state))
                            ->minLength(8)
                            ->maxLength(255)
                            ->helperText(fn ($context) => $context === 'edit' ? 'Kosongkan jika tidak ingin mengubah password' : null),
                        Select::make('role')
                            ->label('Role / Peran')
                            ->options([
                                'admin' => '🛡️ Admin',
                                'supervisor' => '👔 Supervisor',
                                'karyawan' => '👷 Karyawan',
                            ])
                            ->required()
                            ->live()
                            ->afterStateUpdated(function (Set $set): void {
                                // Reset semua field role
                                $set('admin_nip', null);
                                $set('admin_divisi', null);
                                $set('supervisor_jabatan', null);
                                $set('karyawan_nik', null);
                                $set('karyawan_posisi_karyawan', null);
                                $set('karyawan_tgl_masuk', null);
                                $set('karyawan_status_kontrak', null);
                                $set('karyawan_no_hp', null);
                                $set('karyawan_no_ktp', null);
                                $set('karyawan_bidang_tugas', null);
                                $set('karyawan_alamat', null);
                                $set('karyawan_gaji_pokok', null);
                            }),
                    ]),

                // === ADMIN SECTION ===
                Section::make('Data Admin')
                    ->description('Informasi tambahan untuk akun Admin')
                    ->columns(2)
                    ->columnSpanFull()
                    ->visible(fn (Get $get): bool => $get('role') === 'admin')
                    ->schema([
                        TextInput::make('admin_nip')
                            ->label('NIP')
                            ->required(fn (Get $get): bool => $get('role') === 'admin')
                            ->maxLength(255)
                            ->unique(table: Admin::class, column: 'nip', ignoreRecord: true)
                            ->placeholder('ADM-XXX'),
                        TextInput::make('admin_divisi')
                            ->label('Divisi')
                            ->required(fn (Get $get): bool => $get('role') === 'admin')
                            ->maxLength(255)
                            ->placeholder('Contoh: IT, HRD, Keuangan'),
                    ]),

                // === SUPERVISOR SECTION ===
                Section::make('Data Supervisor')
                    ->description('Informasi tambahan untuk akun Supervisor')
                    ->columns(2)
                    ->columnSpanFull()
                    ->visible(fn (Get $get): bool => $get('role') === 'supervisor')
                    ->schema([
                        TextInput::make('supervisor_jabatan')
                            ->label('Jabatan')
                            ->required(fn (Get $get): bool => $get('role') === 'supervisor')
                            ->maxLength(255)
                            ->placeholder('Contoh: Kepala Proyek'),
                    ]),

                // === KARYAWAN SECTION ===
                Section::make('Data Karyawan')
                    ->description('Informasi identitas dan kepegawaian')
                    ->columns(2)
                    ->columnSpanFull()
                    ->visible(fn (Get $get): bool => $get('role') === 'karyawan')
                    ->schema([
                        TextInput::make('karyawan_nik')
                            ->label('NIK Karyawan')
                            ->required(fn (Get $get): bool => $get('role') === 'karyawan')
                            ->maxLength(255)
                            ->unique(table: Karyawan::class, column: 'nik', ignoreRecord: true)
                            ->placeholder('KRY-XXX'),
                        TextInput::make('karyawan_no_ktp')
                            ->label('No. KTP')
                            ->maxLength(20)
                            ->placeholder('3XXXXXXXXXXXXXXX'),
                        TextInput::make('karyawan_posisi_karyawan')
                            ->label('Posisi / Jabatan')
                            ->required(fn (Get $get): bool => $get('role') === 'karyawan')
                            ->maxLength(255)
                            ->placeholder('Contoh: Tukang Batu'),
                        TextInput::make('karyawan_bidang_tugas')
                            ->label('Bidang Tugas')
                            ->required(fn (Get $get): bool => $get('role') === 'karyawan')
                            ->maxLength(255)
                            ->placeholder('Contoh: Konstruksi'),
                        DatePicker::make('karyawan_tgl_masuk')
                            ->label('Tanggal Masuk')
                            ->required(fn (Get $get): bool => $get('role') === 'karyawan')
                            ->default(now()),
                        Select::make('karyawan_status_kontrak')
                            ->label('Status Kontrak')
                            ->options([
                                'kontrak' => '📋 Kontrak',
                                'tetap' => '✅ Tetap',
                            ])
                            ->required(fn (Get $get): bool => $get('role') === 'karyawan'),
                        TextInput::make('karyawan_no_hp')
                            ->label('No. HP')
                            ->required(fn (Get $get): bool => $get('role') === 'karyawan')
                            ->maxLength(30)
                            ->placeholder('08XXXXXXXXXX'),
                        TextInput::make('karyawan_gaji_pokok')
                            ->label('Gaji Pokok (Rp)')
                            ->numeric()
                            ->prefix('Rp')
                            ->required(fn (Get $get): bool => $get('role') === 'karyawan')
                            ->placeholder('3000000'),
                        Textarea::make('karyawan_alamat')
                            ->label('Alamat')
                            ->rows(2)
                            ->placeholder('Alamat lengkap karyawan')
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
