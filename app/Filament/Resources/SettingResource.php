<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SettingResource\Pages;
use App\Models\Setting;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class SettingResource extends Resource
{
    protected static ?string $model = Setting::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-cog-6-tooth';
    protected static string|\UnitEnum|null $navigationGroup = 'Sistem';
    protected static ?string $pluralLabel = 'Pengaturan';
    protected static ?int $navigationSort = 9;

    public static function canViewAny(): bool
    {
        // Supervisor boleh MELIHAT pengaturan (read-only); ubah/hapus tetap admin-only.
        return (Auth::user()?->isAdmin() || Auth::user()?->isSupervisor());
    }

    public static function canCreate(): bool
    {
        return Auth::user()?->isAdmin();
    }

    public static function canEdit(Model $record): bool
    {
        return Auth::user()?->isAdmin();
    }

    public static function canDelete(Model $record): bool
    {
        return Auth::user()?->isAdmin();
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('label')
                    ->label('Nama Pengaturan')
                    ->disabled()
                    ->required(),
                TextInput::make('key')
                    ->disabled()
                    ->required(),
                Textarea::make('value')
                    ->label('Keterangan')
                    ->required()
                    ->columnSpanFull(),
                TextInput::make('group')
                    ->disabled(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('label')
                    ->label('Pengaturan')
                    ->searchable(),
                TextColumn::make('value')
                    ->label('Keterangan')
                    ->limit(50),
                TextColumn::make('group')
                    ->badge()
                    ->color('info'),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make()
                    ->visible(fn () => Auth::user()?->isAdmin())
                    ->after(fn () => Setting::clearCache()),
            ])
            ->bulkActions([
                //
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageSettings::route('/'),
        ];
    }
}

