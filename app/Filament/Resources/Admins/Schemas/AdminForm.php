<?php

namespace App\Filament\Resources\Admins\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class AdminForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Data Admin')
                    ->columns(2)
                    ->schema([
                        Select::make('user_id')
                            ->label('User')
                            ->relationship('user', 'nama')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->unique(ignoreRecord: true),
                        TextInput::make('nip')
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true),
                        TextInput::make('divisi')
                            ->required()
                            ->maxLength(255),
                        Select::make('level_akses')
                            ->options([
                                'dasar' => 'Dasar',
                                'menengah' => 'Menengah',
                                'penuh' => 'Penuh',
                            ])
                            ->required(),
                    ]),
            ]);
    }
}
