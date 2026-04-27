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
                            ->label('User'),
                        TextEntry::make('nip'),
                        TextEntry::make('divisi'),
                        TextEntry::make('level_akses')
                            ->badge(),
                        TextEntry::make('created_at')
                            ->dateTime('d M Y H:i')
                            ->label('Dibuat Pada'),
                    ]),
            ]);
    }
}
