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

    protected static string|\UnitEnum|null $navigationGroup = 'Operasional';

    protected static ?int $navigationSort = 4;

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

                        DatePicker::make('tgl_presensi')
                            ->label('Tanggal Presensi')
                            ->required()
                            ->default(now()),

                        Select::make('status')
                            ->options([
                                'hadir' => '✅ Hadir',
                                'terlambat' => '⏰ Terlambat',
                                'tidak_hadir' => '❌ Tidak Hadir (Alpa)',
                                'izin' => '📋 Izin',
                            ])
                            ->required()
                            ->live()
                            ->default('hadir'),
                    ]),

                Section::make('Waktu & Durasi')
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

                        DateTimePicker::make('jam_pulang')
                            ->label('Jam Pulang')
                            ->seconds(false)
                            ->live(onBlur: true)
                            ->afterStateUpdated(function ($set, $get) {
                                self::hitungOtomatis($set, $get);
                            }),

                        TextInput::make('durasi_menit')
                            ->label('Durasi Kerja (menit)')
                            ->numeric()
                            ->default(0)
                            ->disabled()
                            ->dehydrated()
                            ->helperText('Otomatis dari jam masuk & pulang'),

                        TextInput::make('keterlambatan_menit')
                            ->label('Keterlambatan (menit)')
                            ->numeric()
                            ->default(0)
                            ->disabled()
                            ->dehydrated()
                            ->helperText('Otomatis jika masuk setelah 08:00'),

                        TextInput::make('potongan')
                            ->label('Potongan (Rp)')
                            ->numeric()
                            ->prefix('Rp')
                            ->default(0)
                            ->disabled()
                            ->dehydrated()
                            ->helperText('Per 10 menit terlambat = Rp 10.000')
                            ->columnSpanFull(),
                    ]),

                Section::make('Foto & Lokasi Presensi')
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
                        \Dotswan\MapPicker\Fields\Map::make('location')
                            ->label('Pilih Lokasi di Peta')
                            ->columnSpanFull()
                            ->defaultLocation(
                                latitude: (float) \App\Models\Setting::get('kantor_lat', -6.2087634),
                                longitude: (float) \App\Models\Setting::get('kantor_lng', 106.8222568)
                            )
                            ->afterStateUpdated(function ($set, ?array $state): void {
                                if (!$state || !isset($state['lat'], $state['lng'])) {
                                    return;
                                }
                                $set('lat_masuk', $state['lat']);
                                $set('long_masuk', $state['lng']);
                            })
                            ->afterStateHydrated(function ($set, $record): void {
                                if ($record && $record->lat_masuk && $record->long_masuk) {
                                    $set('location', ['lat' => $record->lat_masuk, 'lng' => $record->long_masuk]);
                                }
                            })
                            ->live(onBlur: true)
                            ->showMarker()
                            ->markerColor('#3b82f6')
                            ->showFullscreenControl()
                            ->showZoomControl()
                            ->draggable()
                            ->clickable(true),
                        TextInput::make('lat_masuk')
                            ->label('Latitude')
                            ->numeric()
                            ->live()
                            ->default(null),
                        TextInput::make('long_masuk')
                            ->label('Longitude')
                            ->numeric()
                            ->live()
                            ->default(null),
                    ]),
            ]);
    }

    /**
     * Auto-hitung durasi, keterlambatan, dan potongan
     */
    private static function hitungOtomatis($set, $get): void
    {
        $jamMasuk = $get('jam_masuk');
        $jamPulang = $get('jam_pulang');

        if (!$jamMasuk || !$jamPulang) {
            return;
        }

        $masuk = \Carbon\Carbon::parse($jamMasuk);
        $pulang = \Carbon\Carbon::parse($jamPulang);

        // Hitung durasi
        $durasi = $masuk->diffInMinutes($pulang);
        $set('durasi_menit', max(0, $durasi));

        // Hitung keterlambatan (batas masuk 08:00)
        $batasMasuk = $masuk->copy()->setTime(8, 0, 0);
        $toleransi = (int) \App\Models\Setting::get('toleransi_menit', 10);
        $batasToleransi = $batasMasuk->copy()->addMinutes($toleransi);

        if ($masuk->gt($batasToleransi)) {
            $terlambatMenit = $batasMasuk->diffInMinutes($masuk);
            $set('keterlambatan_menit', $terlambatMenit);

            // Hitung potongan per 10 menit
            $potonganPer10 = (int) \App\Models\Setting::get('potongan_terlambat', 10000);
            $potongan = ceil($terlambatMenit / 10) * $potonganPer10;
            $set('potongan', $potongan);

            // Auto-set status terlambat
            $set('status', 'terlambat');
        } else {
            $set('keterlambatan_menit', 0);
            $set('potongan', 0);
            if ($get('status') === 'terlambat') {
                $set('status', 'hadir');
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

                TextColumn::make('tgl_presensi')
                    ->label('Tanggal')
                    ->date('d M Y')
                    ->sortable(),
                TextColumn::make('jam_masuk')
                    ->label('Masuk')
                    ->dateTime('H:i')
                    ->placeholder('-'),
                TextColumn::make('jam_pulang')
                    ->label('Pulang')
                    ->dateTime('H:i')
                    ->placeholder('-'),
                TextColumn::make('status')
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
                TextColumn::make('durasi_menit')
                    ->label('Durasi')
                    ->suffix(' mnt')
                    ->placeholder('-'),
                TextColumn::make('keterlambatan_menit')
                    ->label('Telat')
                    ->suffix(' mnt')
                    ->color('warning')
                    ->placeholder('-'),
                TextColumn::make('potongan')
                    ->label('Potongan')
                    ->money('idr')
                    ->color('danger')
                    ->placeholder('-'),
            ])
            ->defaultSort('tgl_presensi', 'desc')
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
            'index' => ListPresensis::route('/'),
            'create' => CreatePresensi::route('/create'),
            'edit' => EditPresensi::route('/{record}/edit'),
        ];
    }
}
