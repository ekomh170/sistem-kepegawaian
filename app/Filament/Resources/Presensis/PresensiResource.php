<?php

namespace App\Filament\Resources\Presensis;

use App\Filament\Resources\Presensis\Pages\CreatePresensi;
use App\Filament\Resources\Presensis\Pages\EditPresensi;
use App\Filament\Resources\Presensis\Pages\ListPresensis;
use App\Filament\Resources\Presensis\Pages\ViewPresensi;
use App\Models\Presensi;
use App\Models\User;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
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
        return (Auth::user()?->isAdmin() || Auth::user()?->isSupervisor());
    }

    public static function canCreate(): bool
    {
        return Auth::user()?->isAdmin();
    }

    public static function canEdit(Model $record): bool
    {
        // Admin: full edit. Supervisor: edit untuk verifikasi.
        return (Auth::user()?->isAdmin() || Auth::user()?->isSupervisor());
    }

    public static function canDelete(Model $record): bool
    {
        return Auth::user()?->isAdmin();
    }

    public static function canDeleteAny(): bool
    {
        return Auth::user()?->isAdmin();
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Data Presensi')
                    ->columns(2)
                    ->columnSpanFull()
                    ->schema([
                        Select::make('user_id')
                            ->label('Karyawan')
                            ->searchable()
                            ->getSearchResultsUsing(fn (string $search) => User::query()
                                ->where('role', 'karyawan')
                                ->when(filled($search), fn ($q) => $q->where(fn ($inner) => $inner
                                    ->where('nik', 'like', "%{$search}%")
                                    ->orWhere('nama', 'like', "%{$search}%")
                                ))
                                ->limit(50)
                                ->get()
                                ->mapWithKeys(fn ($u) => [$u->id => "{$u->nik} - {$u->nama}"])
                                ->all()
                            )
                            ->getOptionLabelUsing(fn ($value) => ($u = User::find($value))
                                ? "{$u->nik} - {$u->nama}"
                                : $value
                            )
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
                            ->prefix('Rp')
                            ->default(0)
                            ->disabled()
                            ->dehydrated()
                            ->formatStateUsing(fn ($state) => $state !== null ? number_format((float) $state, 0, ',', '.') : '0')
                            ->dehydrateStateUsing(fn ($state) => (float) str_replace('.', '', $state ?? '0'))
                            ->helperText('Toleransi 10 menit pertama, Rp 10.000 per 10 menit keterlambatan'),
                    ]),

                Section::make('Verifikasi')
                    ->description('Diisi supervisor/admin untuk memvalidasi presensi (V3 inline).')
                    ->columns(2)
                    ->columnSpanFull()
                    ->schema([
                        Select::make('status_verifikasi')
                            ->label('Status Verifikasi')
                            ->options([
                                'pending' => 'Menunggu',
                                'disetujui' => 'Disetujui',
                                'ditolak' => 'Ditolak',
                            ])
                            ->default('pending')
                            ->required(),
                        Textarea::make('catatan_verifikasi')
                            ->label('Catatan Verifikasi')
                            ->rows(2)
                            ->placeholder('Catatan / alasan (opsional)')
                            ->columnSpanFull(),
                    ]),

                Section::make('Foto & Lokasi Masuk')
                    ->description('Foto selfie karyawan diambil otomatis via kamera saat check-in/out. Upload di sini hanya untuk koreksi manual oleh admin.')
                    ->columns(2)
                    ->columnSpanFull()
                    ->schema([
                        \Filament\Forms\Components\FileUpload::make('foto_masuk')
                            ->label('Foto Masuk')
                            ->image()
                            ->directory('presensi/masuk')
                            ->disk('public')
                            ->maxSize(2048)
                            ->helperText('Normalnya terisi otomatis dari selfie karyawan.'),
                        \Filament\Forms\Components\FileUpload::make('foto_keluar')
                            ->label('Foto Keluar')
                            ->image()
                            ->directory('presensi/keluar')
                            ->disk('public')
                            ->maxSize(2048)
                            ->helperText('Normalnya terisi otomatis dari selfie karyawan.'),
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
                        TextEntry::make('user.nama')
                            ->label('Karyawan'),
                        TextEntry::make('tanggal')
                            ->label('Tanggal')
                            ->date('d F Y'),
                        TextEntry::make('jam_masuk')
                            ->label('Jam Masuk')
                            ->time('H:i')
                            ->placeholder('-'),
                        TextEntry::make('jam_keluar')
                            ->label('Jam Keluar')
                            ->time('H:i')
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
                        TextEntry::make('status_verifikasi')
                            ->label('Status Verifikasi')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'disetujui' => 'success',
                                'ditolak' => 'danger',
                                default => 'gray',
                            })
                            ->formatStateUsing(fn (string $state): string => match ($state) {
                                'disetujui' => 'Disetujui',
                                'ditolak' => 'Ditolak',
                                default => 'Menunggu',
                            }),
                        TextEntry::make('menit_terlambat')
                            ->label('Keterlambatan')
                            ->suffix(' menit')
                            ->placeholder('-'),
                        TextEntry::make('potongan_terlambat')
                            ->label('Potongan')
                            ->money('IDR')
                            ->placeholder('-'),
                        TextEntry::make('verifikator.nama')
                            ->label('Diverifikasi Oleh')
                            ->placeholder('-'),
                        TextEntry::make('catatan_verifikasi')
                            ->label('Catatan Verifikasi')
                            ->placeholder('-')
                            ->columnSpanFull(),
                    ]),

                Section::make('Foto Presensi')
                    ->description('Foto selfie diambil otomatis dari kamera karyawan saat check-in/check-out')
                    ->columns(2)
                    ->columnSpanFull()
                    ->schema([
                        ImageEntry::make('foto_masuk')
                            ->label('Foto Masuk')
                            ->disk('public')
                            ->height(200)
                            ->placeholder('Belum ada foto masuk')
                            ->visible(fn ($record) => $record?->jam_keluar !== null),
                        ImageEntry::make('foto_keluar')
                            ->label('Foto Keluar')
                            ->disk('public')
                            ->height(200)
                            ->placeholder('Belum ada foto keluar'),
                    ]),

                Section::make('Koordinat GPS')
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

    /**
     * Kolom tabel presensi — dipakai PresensiResource & VerifikasiResource (antrian).
     *
     * @return array<int, TextColumn>
     */
    public static function presensiColumns(): array
    {
        return [
            TextColumn::make('user.nama')
                ->label('Karyawan')
                ->searchable()
                ->sortable(),

            TextColumn::make('tanggal')
                ->label('Tanggal')
                ->date('d M Y')
                ->sortable(),
            TextColumn::make('jam_masuk')
                ->label('Masuk')
                ->time('H:i')
                ->placeholder('-'),
            TextColumn::make('jam_keluar')
                ->label('Keluar')
                ->time('H:i')
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
            TextColumn::make('status_verifikasi')
                ->label('Verifikasi')
                ->badge()
                ->color(fn (string $state): string => match ($state) {
                    'disetujui' => 'success',
                    'ditolak' => 'danger',
                    default => 'gray',
                })
                ->formatStateUsing(fn (string $state): string => match ($state) {
                    'disetujui' => 'Disetujui',
                    'ditolak' => 'Ditolak',
                    default => 'Menunggu',
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
        ];
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns(self::presensiColumns())
            ->defaultSort('tanggal', 'desc')
            ->filters([])
            ->recordActions([
                ViewAction::make(),
                ...self::verifikasiRecordActions(),
                EditAction::make()
                    ->visible(fn () => (Auth::user()?->isAdmin() || Auth::user()?->isSupervisor())),
                DeleteAction::make()
                    ->visible(fn () => Auth::user()?->isAdmin()),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ])->visible(fn () => Auth::user()?->isAdmin()),
            ]);
    }

    /**
     * Aksi cepat verifikasi (Setujui/Tolak) untuk supervisor/admin.
     * Dipakai di tabel Presensi & antrian VerifikasiResource.
     * Hanya muncul saat presensi belum diverifikasi (status_verifikasi = pending/null).
     *
     * @return array<int, Action>
     */
    public static function verifikasiRecordActions(): array
    {
        // Verifikasi = tugas SUPERVISOR saja (RBAC: admin ❌). Lihat docs/v3/04-hak-akses-rbac.md.
        // Hanya untuk presensi dengan check-in nyata (Alpa/Izin tidak punya jam_masuk).
        $bisaVerifikasi = fn (Presensi $record): bool => (Auth::user()?->isSupervisor() ?? false)
            && $record->jam_masuk !== null
            && ! $record->sudahDiverifikasi();

        return [
            Action::make('setujui')
                ->label('Setujui')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->visible($bisaVerifikasi)
                ->requiresConfirmation()
                ->modalHeading('Setujui Presensi')
                ->modalDescription('Tandai presensi ini sebagai DISETUJUI?')
                ->action(function (Presensi $record): void {
                    $record->verifikasi(Auth::user(), 'disetujui');
                    Notification::make()->title('Presensi disetujui')->success()->send();
                }),
            Action::make('tolak')
                ->label('Tolak')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->visible($bisaVerifikasi)
                ->modalHeading('Tolak Presensi')
                ->schema([
                    Textarea::make('catatan_verifikasi')
                        ->label('Alasan Penolakan')
                        ->required()
                        ->minLength(5)
                        ->maxLength(500)
                        ->rows(3),
                ])
                ->action(function (array $data, Presensi $record): void {
                    $record->verifikasi(Auth::user(), 'ditolak', $data['catatan_verifikasi']);
                    Notification::make()->title('Presensi ditolak')->danger()->send();
                }),
        ];
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
