<?php

namespace App\Filament\Resources\Karyawans\Schemas;

use App\Models\User;
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
                        : 'Akun login karyawan')
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
                            ->label('Email')
                            ->email()
                            ->required()
                            ->maxLength(255)
                            ->unique(table: User::class, column: 'email', ignoreRecord: true)
                            ->placeholder('contoh@email.com'),
                        TextInput::make('password')
                            ->label('Password')
                            ->password()
                            ->revealable()
                            ->required(fn ($context) => $context === 'create')
                            ->dehydrated(fn ($state) => filled($state))
                            ->minLength(8)
                            ->helperText(fn ($context) => $context === 'edit'
                                ? 'Kosongkan jika tidak ingin mengubah password'
                                : null),
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
                            ->unique(table: User::class, column: 'nik', ignoreRecord: true)
                            ->placeholder('KRY-XXX'),
                        TextInput::make('posisi')
                            ->label('Posisi / Jabatan')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('Contoh: Tukang Batu'),
                        TextInput::make('no_hp')
                            ->label('No. HP')
                            ->required()
                            ->maxLength(30)
                            ->placeholder('08XXXXXXXXXX'),
                    ]),
            ]);
    }
}
