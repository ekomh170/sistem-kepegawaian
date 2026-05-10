<?php

namespace App\Filament\Resources\Verifikasis;

use App\Filament\Resources\Verifikasis\Pages\CreateVerifikasi;
use App\Filament\Resources\Verifikasis\Pages\EditVerifikasi;
use App\Filament\Resources\Verifikasis\Pages\ListVerifikasis;
use App\Models\Verifikasi;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class VerifikasiResource extends Resource
{
    protected static ?string $model = Verifikasi::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static ?string $navigationLabel = 'Verifikasi';

    protected static string|\UnitEnum|null $navigationGroup = 'Operasional';

    protected static ?int $navigationSort = 6;

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
                Section::make('Data Verifikasi')
                    ->columns(2)
                    ->schema([
                        Select::make('presensi_id')
                            ->label('Presensi')
                            ->relationship('presensi', 'id')
                            ->getOptionLabelFromRecordUsing(fn ($record) => "Presensi " . ($record->tanggal?->format('d M Y') ?? '') . " - " . ($record->karyawan?->user?->nama ?? 'Unknown'))
                            ->searchable()
                            ->preload()
                            ->required()
                            ->unique(ignoreRecord: true),
                        Select::make('supervisor_id')
                            ->label('Supervisor')
                            ->relationship('supervisor', 'id')
                            ->getOptionLabelFromRecordUsing(fn ($record) => ($record->user?->nama ?? 'Unknown') . " ({$record->jabatan})")
                            ->searchable()
                            ->preload()
                            ->required(),
                        Select::make('status')
                            ->options([
                                'pending' => 'Pending',
                                'disetujui' => 'Disetujui',
                                'ditolak' => 'Ditolak',
                            ])
                            ->required(),
                        DateTimePicker::make('tgl_verifikasi')
                            ->label('Tanggal Verifikasi')
                            ->seconds(false),
                        Textarea::make('catatan')
                            ->columnSpanFull(),
                        TextInput::make('alasan_tolak')
                            ->maxLength(255),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('presensi.karyawan.user.nama')
                    ->label('Karyawan')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('supervisor.user.nama')
                    ->label('Supervisor')
                    ->searchable(),
                TextColumn::make('status')
                    ->badge(),
                TextColumn::make('tgl_verifikasi')
                    ->dateTime('d M Y H:i')
                    ->sortable(),
                TextColumn::make('alasan_tolak')
                    ->limit(40),
            ])
            ->filters([])
            ->recordActions([
                EditAction::make()
                    ->visible(fn () => Auth::user()?->role === 'admin'),
                DeleteAction::make()
                    ->visible(fn () => Auth::user()?->role === 'admin'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ])->visible(fn () => Auth::user()?->role === 'admin'),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListVerifikasis::route('/'),
            'create' => CreateVerifikasi::route('/create'),
            'edit' => EditVerifikasi::route('/{record}/edit'),
        ];
    }
}
