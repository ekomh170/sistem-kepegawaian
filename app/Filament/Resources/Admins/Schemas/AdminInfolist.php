<?php

namespace App\Filament\Resources\Admins\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class AdminInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Detail Admin')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('user.nama')
                            ->label('Nama'),
                        TextEntry::make('user.email')
                            ->label('Email'),
                        TextEntry::make('nik')
                            ->label('NIK'),
                        TextEntry::make('no_hp')
                            ->label('No. HP'),
                        TextEntry::make('created_at')
                            ->dateTime('d M Y H:i')
                            ->label('Dibuat Pada'),
                    ]),
            ]);
    }
}
