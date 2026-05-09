<?php

namespace App\Filament\Resources\Karyawans\Schemas;

use App\Models\Karyawan;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class KaryawanForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Data Akun')
                    ->description(fn ($context) => $context === 'create'
                        ? 'Buat akun login baru untuk karyawan'
                        : 'Akun user yang terhubung')
                    ->columns(2)
                    ->columnSpanFull()
                    ->schema([
                        // === SAAT CREATE: isi data akun baru ===
                        TextInput::make('user_nama')
                            ->label('Nama Lengkap')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('Masukkan nama lengkap')
                            ->columnSpanFull()
                            ->visible(fn ($context) => $context === 'create'),
                        TextInput::make('user_email')
                            ->label('Email')
                            ->email()
                            ->required()
                            ->maxLength(255)
                            ->placeholder('contoh@email.com')
                            ->visible(fn ($context) => $context === 'create'),
                        TextInput::make('user_password')
                            ->label('Password')
                            ->password()
                            ->revealable()
                            ->required(fn ($context) => $context === 'create')
                            ->dehydrated(fn ($state) => filled($state))
                            ->minLength(8)
                            ->visible(fn ($context) => $context === 'create'),

                        // === SAAT EDIT: tampilkan select user ===
                        Select::make('user_id')
                            ->label('Akun User')
                            ->relationship('user', 'nama')
                            ->getOptionLabelFromRecordUsing(fn ($record) => "{$record->nama} ({$record->email})")
                            ->searchable(['nama', 'email'])
                            ->preload()
                            ->required()
                            ->disabled()
                            ->dehydrated()
                            ->columnSpanFull()
                            ->visible(fn ($context) => $context === 'edit'),
                    ]),

                Section::make('Identitas Karyawan')
                    ->description('Informasi identitas dan kepegawaian')
                    ->columns(2)
                    ->columnSpanFull()
                    ->schema([
                        TextInput::make('nik')
                            ->label('NIK Karyawan')
                            ->required()
                            ->maxLength(255)
                            ->unique(table: Karyawan::class, column: 'nik', ignoreRecord: true)
                            ->placeholder('KRY-XXX'),
                        TextInput::make('no_ktp')
                            ->label('No. KTP')
                            ->maxLength(20)
                            ->placeholder('3XXXXXXXXXXXXXXX'),
                        TextInput::make('posisi_karyawan')
                            ->label('Posisi / Jabatan')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('Contoh: Tukang Batu'),
                        TextInput::make('bidang_tugas')
                            ->label('Bidang Tugas')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('Contoh: Konstruksi'),
                        TextInput::make('no_hp')
                            ->label('No. HP')
                            ->required()
                            ->maxLength(30)
                            ->placeholder('08XXXXXXXXXX'),
                        FileUpload::make('foto')
                            ->label('Foto Karyawan')
                            ->image()
                            ->directory('karyawan')
                            ->maxSize(2048),
                        Textarea::make('alamat')
                            ->label('Alamat Lengkap')
                            ->rows(2)
                            ->placeholder('Masukkan alamat lengkap')
                            ->columnSpanFull(),
                    ]),

                Section::make('Kepegawaian')
                    ->description('Status kontrak dan informasi gaji')
                    ->columns(2)
                    ->columnSpanFull()
                    ->schema([
                        DatePicker::make('tgl_masuk')
                            ->label('Tanggal Masuk')
                            ->required()
                            ->default(now()),
                        Select::make('status_kontrak')
                            ->label('Status Kontrak')
                            ->options([
                                'kontrak' => '📋 Kontrak',
                                'tetap' => '✅ Tetap',
                            ])
                            ->required(),
                        TextInput::make('gaji_pokok')
                            ->label('Gaji Pokok (Rp)')
                            ->numeric()
                            ->prefix('Rp')
                            ->required()
                            ->default(0)
                            ->placeholder('3000000'),
                    ]),
            ]);
    }
}
