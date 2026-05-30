<?php

namespace App\Filament\Resources\Akuns\Schemas;

use App\Models\User;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
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
                            ->live(),
                    ]),

                Section::make('Data Tambahan')
                    ->description('Identitas pengguna')
                    ->columns(2)
                    ->columnSpanFull()
                    ->schema([
                        TextInput::make('nik')
                            ->label('NIK')
                            ->required()
                            ->maxLength(255)
                            ->unique(table: User::class, column: 'nik', ignoreRecord: true)
                            ->placeholder('ADM-XXX / SPV-XXX / KRY-XXX'),
                        TextInput::make('no_hp')
                            ->label('No. HP')
                            ->required()
                            ->maxLength(30)
                            ->placeholder('08XXXXXXXXXX'),
                        TextInput::make('posisi')
                            ->label('Posisi / Jabatan')
                            ->maxLength(255)
                            ->placeholder('Contoh: Tukang Batu')
                            ->required(fn (Get $get): bool => $get('role') === 'karyawan')
                            ->visible(fn (Get $get): bool => $get('role') === 'karyawan')
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
