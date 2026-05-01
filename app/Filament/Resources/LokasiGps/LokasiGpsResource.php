<?php

namespace App\Filament\Resources\LokasiGps;

use App\Filament\Resources\LokasiGps\Pages\CreateLokasiGps;
use App\Filament\Resources\LokasiGps\Pages\EditLokasiGps;
use App\Filament\Resources\LokasiGps\Pages\ListLokasiGps;
use App\Models\Lokasi_gps;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class LokasiGpsResource extends Resource
{
    protected static ?string $model = Lokasi_gps::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMapPin;

    protected static ?string $navigationLabel = 'Lokasi GPS';

    public static function shouldRegisterNavigation(): bool
    {
        return Auth::user()?->role === 'admin';
    }

    public static function canViewAny(): bool
    {
        return Auth::user()?->role === 'admin';
    }

    public static function canCreate(): bool
    {
        return Auth::user()?->role === 'admin';
    }

    public static function canEdit(Model $record): bool
    {
        return Auth::user()?->role === 'admin';
    }

    public static function canDelete(Model $record): bool
    {
        return Auth::user()?->role === 'admin';
    }

    public static function canDeleteAny(): bool
    {
        return Auth::user()?->role === 'admin';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Data Lokasi GPS')
                    ->columns(2)
                    ->schema([
                        TextInput::make('nama_lokasi')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('radius_meter')
                            ->numeric()
                            ->required(),
                        TextInput::make('latitude')
                            ->numeric()
                            ->required(),
                        TextInput::make('longitude')
                            ->numeric()
                            ->required(),
                        TextInput::make('akurasi')
                            ->numeric()
                            ->required(),
                        DateTimePicker::make('timestamp')
                            ->label('Timestamp')
                            ->seconds(false)
                            ->required(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('nama_lokasi')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('latitude')
                    ->label('Latitude'),
                TextColumn::make('longitude')
                    ->label('Longitude'),
                TextColumn::make('radius_meter')
                    ->suffix(' m'),
                TextColumn::make('akurasi'),
                TextColumn::make('timestamp')
                    ->dateTime('d M Y H:i')
                    ->sortable(),
            ])
            ->filters([])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListLokasiGps::route('/'),
            'create' => CreateLokasiGps::route('/create'),
            'edit' => EditLokasiGps::route('/{record}/edit'),
        ];
    }
}
