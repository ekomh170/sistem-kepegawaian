<?php

namespace App\Filament\Resources\Notifikasis;

use App\Filament\Resources\Notifikasis\Pages\CreateNotifikasi;
use App\Filament\Resources\Notifikasis\Pages\EditNotifikasi;
use App\Filament\Resources\Notifikasis\Pages\ListNotifikasis;
use App\Models\Notifikasi;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class NotifikasiResource extends Resource
{
    protected static ?string $model = Notifikasi::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBell;

    protected static ?string $navigationLabel = 'Notifikasi';

    public static function shouldRegisterNavigation(): bool
    {
        return false;
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
                Section::make('Data Notifikasi')
                    ->columns(2)
                    ->schema([
                        Select::make('user_id')
                            ->label('User')
                            ->relationship('user', 'nama')
                            ->searchable()
                            ->preload()
                            ->required(),
                        Select::make('tipe')
                            ->options([
                                'info' => 'Info',
                                'peringatan' => 'Peringatan',
                                'urgent' => 'Urgent',
                            ])
                            ->required(),
                        Textarea::make('pesan')
                            ->required()
                            ->columnSpanFull(),
                        Toggle::make('terbaca')
                            ->label('Terbaca')
                            ->default(false),
                        DateTimePicker::make('tgl_kirim')
                            ->label('Tanggal Kirim')
                            ->seconds(false)
                            ->required(),
                        TextInput::make('channel')
                            ->required()
                            ->maxLength(255),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.nama')
                    ->label('User')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('tipe')
                    ->badge(),
                TextColumn::make('pesan')
                    ->limit(50)
                    ->searchable(),
                IconColumn::make('terbaca')
                    ->boolean()
                    ->label('Terbaca'),
                TextColumn::make('tgl_kirim')
                    ->dateTime('d M Y H:i')
                    ->sortable(),
                TextColumn::make('channel')
                    ->searchable(),
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
            'index' => ListNotifikasis::route('/'),
            'create' => CreateNotifikasi::route('/create'),
            'edit' => EditNotifikasi::route('/{record}/edit'),
        ];
    }
}
