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
                        TextInput::make('nik')
                            ->label('NIK')
                            ->required()
                            ->maxLength(100),
                        TextInput::make('no_hp')
                            ->label('No. HP')
                            ->required()
                            ->maxLength(20),
                    ]),
            ]);
    }
}
