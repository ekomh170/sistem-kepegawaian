<?php

namespace App\Filament\Resources\JadwalKerjas;

use App\Filament\Resources\JadwalKerjas\Pages\CreateJadwalKerja;
use App\Filament\Resources\JadwalKerjas\Pages\EditJadwalKerja;
use App\Filament\Resources\JadwalKerjas\Pages\ListJadwalKerjas;
use App\Models\Jadwal_kerja;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class JadwalKerjaResource extends Resource
{
    protected static ?string $model = Jadwal_kerja::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDateRange;

    protected static ?string $navigationLabel = 'Jadwal Kerja';

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
                Section::make('Data Jadwal Kerja')
                    ->columns(2)
                    ->schema([
                        Select::make('karyawan_id')
                            ->label('Karyawan')
                            ->relationship('karyawan', 'nik')
                            ->searchable()
                            ->preload()
                            ->required(),
                        DatePicker::make('tanggal')
                            ->required(),
                        Select::make('hari')
                            ->options([
                                'Senin' => 'Senin',
                                'Selasa' => 'Selasa',
                                'Rabu' => 'Rabu',
                                'Kamis' => 'Kamis',
                                'Jumat' => 'Jumat',
                                'Sabtu' => 'Sabtu',
                                'Minggu' => 'Minggu',
                            ])
                            ->required(),
                        TimePicker::make('jam_masuk')
                            ->seconds(false)
                            ->required(),
                        TimePicker::make('jam_keluar')
                            ->seconds(false)
                            ->required(),
                        TextInput::make('lokasi_kerja')
                            ->required()
                            ->maxLength(255),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('karyawan.nik')
                    ->label('Karyawan')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('tanggal')
                    ->date()
                    ->sortable(),
                TextColumn::make('hari')
                    ->badge(),
                TextColumn::make('jam_masuk')
                    ->time('H:i'),
                TextColumn::make('jam_keluar')
                    ->time('H:i'),
                TextColumn::make('lokasi_kerja')
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
            'index' => ListJadwalKerjas::route('/'),
            'create' => CreateJadwalKerja::route('/create'),
            'edit' => EditJadwalKerja::route('/{record}/edit'),
        ];
    }
}
