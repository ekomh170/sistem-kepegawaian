<?php

namespace App\Filament\Resources\Presensis;

use App\Filament\Resources\Presensis\Pages\CreatePresensi;
use App\Filament\Resources\Presensis\Pages\EditPresensi;
use App\Filament\Resources\Presensis\Pages\ListPresensis;
use App\Filament\Resources\Presensis\Pages\ViewPresensi;
use App\Models\Presensi;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
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

    protected static string|\UnitEnum|null $navigationGroup = 'Operasional';

    protected static ?int $navigationSort = 5;

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
                Section::make('Data Presensi')
                    ->columns(2)
                    ->columnSpanFull()
                    ->schema([
                        Select::make('karyawan_id')
                            ->label('Karyawan')
                            ->relationship('karyawan', 'nik')
                            ->getOptionLabelFromRecordUsing(fn ($record) => "{$record->nik} - " . ($record->user?->nama ?? 'Unknown'))
                            ->searchable(['nik'])
                            ->preload()
                            ->required()
                            ->columnSpanFull(),

                        Select::make('jadwal_id')
                            ->label('Jadwal Terkait')
                            ->relationship('jadwal', 'tanggal_kerja')
                            ->getOptionLabelFromRecordUsing(fn ($record) => $record->tanggal_kerja->format('d M Y') . ' (' . substr($record->jam_masuk, 0, 5) . '-' . substr($record->jam_pulang, 0, 5) . ')')
                            ->searchable()
                            ->preload()
                            ->placeholder('Auto-link by tanggal & karyawan')
                            ->columnSpanFull(),

                        DatePicker::make('tanggal')
                            ->label('Tanggal Presensi')
                            ->required()
                            ->default(now()),

                        Select::make('status_presensi')
                            ->label('Status Presensi')
                            ->options([
                                'hadir' => '✅ Hadir',
                                'terlambat' => '⏰ Terlambat',
                                'tidak_hadir' => '❌ Tidak Hadir (Alpa)',
                                'izin' => '📋 Izin',
                            ])
                            ->required()
                            ->live()
                            ->default('hadir'),

                        Select::make('status_valid')
                            ->label('Status Validasi')
                            ->options([
                                'pending' => 'Pending',
                                'valid' => 'Valid',
                                'tidak_valid' => 'Tidak Valid',
                            ])
                            ->default('pending'),
                    ]),

                Section::make('Waktu & Keterlambatan')
                    ->columns(2)
                    ->columnSpanFull()
                    ->schema([
                        DateTimePicker::make('jam_masuk')
                            ->label('Jam Masuk')
                            ->seconds(false)
                            ->live(onBlur: true)
                            ->afterStateUpdated(function ($set, $get) {
                                self::hitungOtomatis($set, $get);
                            }),

                        DateTimePicker::make('jam_keluar')
                            ->label('Jam Keluar')
                            ->seconds(false)
                            ->live(onBlur: true),

                        TextInput::make('menit_terlambat')
                            ->label('Keterlambatan (menit)')
                            ->numeric()
                            ->default(0)
                            ->disabled()
                            ->dehydrated()
                            ->helperText('Otomatis jika masuk setelah 08:00'),

                        TextInput::make('potongan_terlambat')
                            ->label('Potongan Keterlambatan (Rp)')
                            ->numeric()
                            ->prefix('Rp')
                            ->default(0)
                            ->disabled()
                            ->dehydrated()
                            ->helperText('Toleransi 10 menit, Rp 10.000 per blok 10 menit, Rp 20.000 jika >30 menit'),
                    ]),

                Section::make('Foto & Lokasi Masuk')
                    ->columns(2)
                    ->columnSpanFull()
                    ->schema([
                        \Filament\Forms\Components\FileUpload::make('foto_masuk')
                            ->label('Foto Masuk')
                            ->image()
                            ->directory('presensi')
                            ->maxSize(2048),
                        \Filament\Forms\Components\FileUpload::make('foto_keluar')
                            ->label('Foto Keluar')
                            ->image()
                            ->directory('presensi')
                            ->maxSize(2048),
                        \Dotswan\MapPicker\Fields\Map::make('location_masuk')
                            ->label('Lokasi Masuk')
                            ->columnSpanFull()
                            ->defaultLocation(
                                latitude: (float) \App\Models\Setting::get('kantor_lat', -6.2087634),
                                longitude: (float) \App\Models\Setting::get('kantor_lng', 106.8222568)
                            )
                            ->afterStateUpdated(function ($set, ?array $state): void {
                                if (!$state || !isset($state['lat'], $state['lng'])) {
                                    return;
                                }
                                $set('latitude_masuk', $state['lat']);
                                $set('longitude_masuk', $state['lng']);
                            })
                            ->afterStateHydrated(function ($set, $record): void {
                                if ($record && $record->latitude_masuk && $record->longitude_masuk) {
                                    $set('location_masuk', ['lat' => $record->latitude_masuk, 'lng' => $record->longitude_masuk]);
                                }
                            })
                            ->live(onBlur: true)
                            ->showMarker()
                            ->markerColor('#3b82f6')
                            ->draggable()
                            ->clickable(true),
                        TextInput::make('latitude_masuk')
                            ->label('Latitude Masuk')
                            ->numeric()
                            ->live()
                            ->default(null),
                        TextInput::make('longitude_masuk')
                            ->label('Longitude Masuk')
                            ->numeric()
                            ->live()
                            ->default(null),
                        TextInput::make('latitude_keluar')
                            ->label('Latitude Keluar')
                            ->numeric()
                            ->default(null),
                        TextInput::make('longitude_keluar')
                            ->label('Longitude Keluar')
                            ->numeric()
                            ->default(null),
                    ]),
            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Data Presensi')
                    ->columns(2)
                    ->columnSpanFull()
                    ->schema([
                        TextEntry::make('karyawan.user.nama')
                            ->label('Karyawan'),
                        TextEntry::make('tanggal')
                            ->label('Tanggal')
                            ->date('d F Y'),
                        TextEntry::make('jam_masuk')
                            ->label('Jam Masuk')
                            ->dateTime('H:i')
                            ->placeholder('-'),
                        TextEntry::make('jam_keluar')
                            ->label('Jam Keluar')
                            ->dateTime('H:i')
                            ->placeholder('-'),
                        TextEntry::make('status_presensi')
                            ->label('Status Presensi')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'hadir' => 'success',
                                'terlambat' => 'warning',
                                'tidak_hadir' => 'danger',
                                'izin' => 'info',
                                default => 'gray',
                            })
                            ->formatStateUsing(fn (string $state): string => match ($state) {
                                'hadir' => 'Hadir',
                                'terlambat' => 'Terlambat',
                                'tidak_hadir' => 'Alpa',
                                'izin' => 'Izin',
                                default => $state,
                            }),
                        TextEntry::make('status_valid')
                            ->label('Status Validasi')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'valid' => 'success',
                                'tidak_valid' => 'danger',
                                default => 'gray',
                            })
                            ->formatStateUsing(fn (string $state): string => match ($state) {
                                'valid' => 'Valid',
                                'tidak_valid' => 'Tidak Valid',
                                default => 'Pending',
                            }),
                        TextEntry::make('menit_terlambat')
                            ->label('Keterlambatan')
                            ->suffix(' menit')
                            ->placeholder('-'),
                        TextEntry::make('potongan_terlambat')
                            ->label('Potongan')
                            ->money('IDR')
                            ->placeholder('-'),
                    ]),

                Section::make('Lokasi & Foto')
                    ->columns(2)
                    ->columnSpanFull()
                    ->schema([
                        TextEntry::make('latitude_masuk')
                            ->label('Koordinat Masuk')
                            ->formatStateUsing(fn ($record) => $record->latitude_masuk
                                ? "{$record->latitude_masuk}, {$record->longitude_masuk}"
                                : '-'),
                        TextEntry::make('latitude_keluar')
                            ->label('Koordinat Keluar')
                            ->formatStateUsing(fn ($record) => $record->latitude_keluar
                                ? "{$record->latitude_keluar}, {$record->longitude_keluar}"
                                : '-'),
                    ]),
            ]);
    }

    /**
     * Auto-hitung keterlambatan dan potongan
     */
    private static function hitungOtomatis($set, $get): void
    {
        $jamMasuk = $get('jam_masuk');

        if (!$jamMasuk) {
            return;
        }

        $masuk = \Carbon\Carbon::parse($jamMasuk);

        $batasMasuk = $masuk->copy()->setTime(8, 0, 0);
        $toleransi = (int) \App\Models\Setting::get('toleransi_menit', 10);
        $batasToleransi = $batasMasuk->copy()->addMinutes($toleransi);

        if ($masuk->gt($batasToleransi)) {
            $terlambatMenit = (int) $batasMasuk->diffInMinutes($masuk);
            $set('menit_terlambat', $terlambatMenit);
            $set('potongan_terlambat', \App\Models\Presensi::hitungPotongan($terlambatMenit));

            $set('status_presensi', 'terlambat');
        } else {
            $set('menit_terlambat', 0);
            $set('potongan_terlambat', 0);
            if ($get('status_presensi') === 'terlambat') {
                $set('status_presensi', 'hadir');
            }
        }
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('karyawan.user.nama')
                    ->label('Karyawan')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('tanggal')
                    ->label('Tanggal')
                    ->date('d M Y')
                    ->sortable(),
                TextColumn::make('jam_masuk')
                    ->label('Masuk')
                    ->dateTime('H:i')
                    ->placeholder('-'),
                TextColumn::make('jam_keluar')
                    ->label('Keluar')
                    ->dateTime('H:i')
                    ->placeholder('-'),
                TextColumn::make('status_presensi')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'hadir' => 'success',
                        'terlambat' => 'warning',
                        'tidak_hadir' => 'danger',
                        'izin' => 'info',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'hadir' => 'Hadir',
                        'terlambat' => 'Terlambat',
                        'tidak_hadir' => 'Alpa',
                        'izin' => 'Izin',
                        default => $state,
                    }),
                TextColumn::make('status_valid')
                    ->label('Validasi')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'valid' => 'success',
                        'tidak_valid' => 'danger',
                        default => 'gray',
                    })
                    ->toggleable(),
                TextColumn::make('menit_terlambat')
                    ->label('Telat')
                    ->suffix(' mnt')
                    ->color('warning')
                    ->placeholder('-'),
                TextColumn::make('potongan_terlambat')
                    ->label('Potongan')
                    ->money('idr')
                    ->color('danger')
                    ->placeholder('-'),
            ])
            ->defaultSort('tanggal', 'desc')
            ->filters([])
            ->recordActions([
                ViewAction::make(),
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
            'index' => ListPresensis::route('/'),
            'create' => CreatePresensi::route('/create'),
            'view' => ViewPresensi::route('/{record}'),
            'edit' => EditPresensi::route('/{record}/edit'),
        ];
    }
}
