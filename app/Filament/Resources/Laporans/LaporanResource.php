<?php

namespace App\Filament\Resources\Laporans;

use App\Filament\Resources\Laporans\Pages\CreateLaporan;
use App\Filament\Resources\Laporans\Pages\EditLaporan;
use App\Filament\Resources\Laporans\Pages\ListLaporans;
use App\Models\Laporan;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class LaporanResource extends Resource
{
    protected static ?string $model = Laporan::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static ?string $navigationLabel = 'Laporan';

    public static function canViewAny(): bool
    {
        return in_array(Auth::user()?->role, ['admin', 'supervisor'], true);
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
                Section::make('Data Laporan')
                    ->columns(2)
                    ->schema([
                        Select::make('karyawan_id')
                            ->label('Karyawan')
                            ->relationship('karyawan', 'nik')
                            ->searchable()
                            ->preload()
                            ->required(),
                        Select::make('admin_id')
                            ->label('Admin')
                            ->relationship('admin', 'nip')
                            ->searchable()
                            ->preload()
                            ->required(),
                        TextInput::make('periode')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('total_hadir')
                            ->numeric()
                            ->default(0)
                            ->required(),
                        TextInput::make('total_terlambat')
                            ->numeric()
                            ->default(0)
                            ->required(),
                        TextInput::make('total_tidak_hadir')
                            ->numeric()
                            ->default(0)
                            ->required(),
                        TextInput::make('estimasi_gaji')
                            ->numeric()
                            ->prefix('Rp')
                            ->required(),
                        DateTimePicker::make('tgl_generate')
                            ->label('Tanggal Generate')
                            ->seconds(false)
                            ->required(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('periode')
                    ->label('Periode')
                    ->searchable(),
                TextColumn::make('karyawan.nik')
                    ->label('Karyawan')
                    ->searchable(),
                TextColumn::make('admin.nip')
                    ->label('Admin')
                    ->searchable(),
                TextColumn::make('total_hadir')
                    ->sortable(),
                TextColumn::make('total_terlambat')
                    ->sortable(),
                TextColumn::make('total_tidak_hadir')
                    ->sortable(),
                TextColumn::make('estimasi_gaji')
                    ->money('IDR')
                    ->sortable(),
                TextColumn::make('tgl_generate')
                    ->dateTime('d M Y H:i')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('periode')
                    ->label('Periode')
                    ->options(fn (): array => Laporan::query()
                        ->select('periode')
                        ->distinct()
                        ->orderByDesc('periode')
                        ->pluck('periode', 'periode')
                        ->all()),
                SelectFilter::make('karyawan_id')
                    ->label('Karyawan')
                    ->relationship('karyawan', 'nik'),
            ])
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
            'index' => ListLaporans::route('/'),
            'create' => CreateLaporan::route('/create'),
            'edit' => EditLaporan::route('/{record}/edit'),
        ];
    }
}
