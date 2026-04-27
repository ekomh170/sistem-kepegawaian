<?php

namespace App\Filament\Resources\Presensis;

use App\Filament\Resources\Presensis\Pages\CreatePresensi;
use App\Filament\Resources\Presensis\Pages\EditPresensi;
use App\Filament\Resources\Presensis\Pages\ListPresensis;
use App\Models\Presensi;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class PresensiResource extends Resource
{
    protected static ?string $model = Presensi::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentCheck;

    protected static ?string $navigationLabel = 'Presensi';

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
                Section::make('Data Presensi')
                    ->columns(2)
                    ->schema([
                        Select::make('karyawan_id')
                            ->label('Karyawan')
                            ->relationship('karyawan', 'nik')
                            ->searchable()
                            ->preload()
                            ->required(),
                        Select::make('jadwal_kerja_id')
                            ->label('Jadwal Kerja')
                            ->relationship('jadwalKerja', 'lokasi_kerja')
                            ->searchable()
                            ->preload()
                            ->required(),
                        Select::make('lokasi_gps_id')
                            ->label('Lokasi GPS')
                            ->relationship('lokasiGps', 'nama_lokasi')
                            ->searchable()
                            ->preload()
                            ->required(),
                        DatePicker::make('tgl_presensi')
                            ->required(),
                        DateTimePicker::make('jam_masuk')
                            ->seconds(false),
                        DateTimePicker::make('jam_keluar')
                            ->seconds(false),
                        Select::make('status')
                            ->options([
                                'hadir' => 'Hadir',
                                'terlambat' => 'Terlambat',
                                'tidak_hadir' => 'Tidak Hadir',
                                'izin' => 'Izin',
                            ])
                            ->required(),
                        TextInput::make('durasi_menit')
                            ->numeric()
                            ->default(0)
                            ->required(),
                        TextInput::make('foto_masuk')
                            ->maxLength(255),
                        TextInput::make('foto_keluar')
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
                    ->searchable(),
                TextColumn::make('jadwalKerja.lokasi_kerja')
                    ->label('Jadwal')
                    ->searchable(),
                TextColumn::make('lokasiGps.nama_lokasi')
                    ->label('Lokasi')
                    ->searchable(),
                TextColumn::make('tgl_presensi')
                    ->date()
                    ->sortable(),
                TextColumn::make('jam_masuk')
                    ->dateTime('d M Y H:i'),
                TextColumn::make('jam_keluar')
                    ->dateTime('d M Y H:i'),
                TextColumn::make('status')
                    ->badge(),
                TextColumn::make('durasi_menit')
                    ->label('Durasi (menit)'),
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
            'index' => ListPresensis::route('/'),
            'create' => CreatePresensi::route('/create'),
            'edit' => EditPresensi::route('/{record}/edit'),
        ];
    }
}
