<?php

namespace App\Filament\Resources\LaporanPresensis;

use App\Filament\Resources\LaporanPresensis\Pages\CreateLaporan;
use App\Filament\Resources\LaporanPresensis\Pages\EditLaporan;
use App\Filament\Resources\LaporanPresensis\Pages\ListLaporans;
use App\Models\LaporanPresensi;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class LaporanPresensiResource extends Resource
{
    protected static ?string $model = LaporanPresensi::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static ?string $navigationLabel = 'Laporan';

    protected static ?string $modelLabel = 'Laporan';

    protected static ?string $pluralModelLabel = 'Laporan';

    protected static string|\UnitEnum|null $navigationGroup = 'Laporan';

    protected static ?int $navigationSort = 8;

    /**
     * V3: hanya tampilkan baris laporan agregat (user_id NULL). Baris rekap
     * per-karyawan (user_id terisi) tidak dipakai di resource ini.
     */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->whereNull('user_id');
    }

    public static function canViewAny(): bool
    {
        return (Auth::user()?->isAdmin() || Auth::user()?->isSupervisor());
    }

    public static function canCreate(): bool
    {
        return (Auth::user()?->isAdmin() || Auth::user()?->isSupervisor());
    }

    public static function canEdit(Model $record): bool
    {
        return Auth::user()?->isAdmin();
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
                Section::make('Buat Laporan')
                    ->description('Pilih laporan yang ingin dibuat lalu tentukan periodenya. Nama pembuat & waktu pembuatan dicatat otomatis. Setelah tersimpan, laporan bisa diunduh (CSV/Excel/PDF) dari daftar.')
                    ->columns(2)
                    ->columnSpanFull()
                    ->schema([
                        Select::make('tipe_laporan')
                            ->label('Laporan yang dibuat')
                            ->helperText('Pilih isi laporannya.')
                            ->options([
                                'presensi' => 'Presensi Harian — rincian jam masuk & keluar tiap karyawan',
                                'rekap_presensi_bulanan' => 'Rekap Kehadiran — jumlah hadir, terlambat & potongan per karyawan',
                                'rekap_pekerjaan' => 'Rekap Pekerjaan — daftar tugas & bukti per karyawan',
                            ])
                            ->required()
                            ->default('presensi')
                            ->dehydrated(false)
                            ->live()
                            ->afterStateHydrated(function ($set, $record) {
                                if ($record && $record->judul) {
                                    $judul = strtolower($record->judul);
                                    if (str_contains($judul, 'pekerjaan')) {
                                        $set('tipe_laporan', 'rekap_pekerjaan');
                                    } elseif (
                                        str_contains($judul, 'jumlah presensi') ||
                                        str_contains($judul, 'rekap') ||
                                        str_contains($judul, 'bulanan') ||
                                        str_contains($judul, 'potongan')
                                    ) {
                                        $set('tipe_laporan', 'rekap_presensi_bulanan');
                                    } else {
                                        $set('tipe_laporan', 'presensi');
                                    }
                                }
                            })
                            ->afterStateUpdated(function ($set, $get) {
                                self::generateJudul($set, $get);
                            })
                            ->columnSpanFull(),

                        Select::make('jenis')
                            ->label('Rentang Waktu')
                            ->helperText('Periode data yang dirangkum.')
                            ->options([
                                'Harian' => 'Harian (satu tanggal)',
                                'Mingguan' => 'Mingguan (satu minggu)',
                                'Bulanan' => 'Bulanan (satu bulan)',
                                'Tahunan' => 'Tahunan (satu tahun)',
                            ])
                            ->required()
                            ->default('Bulanan')
                            ->live()
                            ->afterStateUpdated(function ($set, $get) {
                                self::generateJudul($set, $get);
                            }),

                        TextInput::make('periode')
                            ->label(fn ($get) => match ($get('jenis')) {
                                'Harian'   => 'Pilih Tanggal',
                                'Mingguan' => 'Pilih Tanggal Awal Minggu',
                                'Tahunan'  => 'Masukkan Tahun',
                                default    => 'Pilih Bulan',
                            })
                            // Input native sesuai rentang waktu (month/date/number) → ramah untuk awam.
                            ->type(fn ($get) => match ($get('jenis')) {
                                'Harian'   => 'date',
                                'Mingguan' => 'date',
                                'Tahunan'  => 'number',
                                default    => 'month',
                            })
                            ->required()
                            ->maxLength(20)
                            ->placeholder(fn ($get) => $get('jenis') === 'Tahunan' ? '2026' : null)
                            ->helperText(fn ($get) => match ($get('jenis')) {
                                'Mingguan' => 'Data diambil 7 hari sejak tanggal yang dipilih.',
                                'Tahunan'  => 'Cukup tahun, contoh: 2026.',
                                default    => 'Gunakan pemilih tanggal yang muncul.',
                            })
                            ->live(onBlur: true)
                            ->afterStateUpdated(function ($set, $get) {
                                self::generateJudul($set, $get);
                            }),

                        \Filament\Forms\Components\Hidden::make('judul')
                            ->dehydrated(),

                        // Pembuat & waktu pembuatan otomatis — tidak perlu diisi user.
                        \Filament\Forms\Components\Hidden::make('generated_by')
                            ->default(fn () => Auth::id())
                            ->dehydrated(),

                        \Filament\Forms\Components\Hidden::make('tgl_generate')
                            ->default(fn () => now())
                            ->dehydrated(),
                    ]),
            ]);
    }

    private static function resolveTipeLabel(string $judul): string
    {
        $judul = strtolower($judul);
        if (str_contains($judul, 'pekerjaan')) {
            return 'Rekap Pekerjaan';
        }
        if (str_contains($judul, 'jumlah presensi') || str_contains($judul, 'rekap') || str_contains($judul, 'bulanan')) {
            return 'Jumlah Presensi Per Karyawan';
        }
        return 'Presensi Harian';
    }

    private static function resolveExportRoute(LaporanPresensi $record, string $format): string
    {
        $judul = strtolower($record->judul);
        $params = ['periode' => $record->periode, 'jenis' => $record->jenis];

        if (str_contains($judul, 'pekerjaan')) {
            return route("laporan.export-pekerjaan.{$format}", $params);
        }

        if (str_contains($judul, 'presensi')) {
            $isRekapBulanan = str_contains($judul, 'jumlah presensi') ||
                              str_contains($judul, 'rekap') ||
                              str_contains($judul, 'bulanan');
            return $isRekapBulanan
                ? route("laporan.export.{$format}", $params)
                : route("laporan.export-presensi.{$format}", $params);
        }

        return route("laporan.export.{$format}", $params);
    }

    private static function generateJudul($set, $get): void
    {
        $tipe = $get('tipe_laporan');
        $jenis = $get('jenis');
        $periode = $get('periode');

        if (!$tipe || !$periode) {
            return;
        }

        $tipeName = match ($tipe) {
            'presensi' => 'Laporan Presensi',
            'rekap_presensi_bulanan' => 'Laporan Jumlah Presensi Per Karyawan',
            'rekap_pekerjaan' => 'Laporan Rekap Pekerjaan',
            default => 'Laporan',
        };

        $jenisName = $jenis ? " {$jenis}" : '';
        $set('judul', "{$tipeName}{$jenisName} {$periode}");
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('judul')
                    ->label('Judul')
                    ->searchable()
                    ->weight('bold')
                    ->limit(40),
                TextColumn::make('tipe')
                    ->label('Tipe')
                    ->badge()
                    ->state(fn (LaporanPresensi $record): string => self::resolveTipeLabel($record->judul))
                    ->color(fn (string $state): string => match ($state) {
                        'Presensi Harian'                => 'info',
                        'Jumlah Presensi Per Karyawan'   => 'success',
                        'Rekap Pekerjaan'                => 'warning',
                        default                          => 'gray',
                    }),
                TextColumn::make('jenis')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Harian' => 'info',
                        'Mingguan' => 'primary',
                        'Bulanan' => 'success',
                        'Tahunan' => 'warning',
                        default => 'gray',
                    }),
                TextColumn::make('periode')
                    ->label('Periode')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('generator.nama')
                    ->label('Dibuat Oleh')
                    ->placeholder('-'),
                TextColumn::make('tgl_generate')
                    ->label('Tanggal Generate')
                    ->dateTime('d M Y H:i')
                    ->sortable(),
            ])
            ->defaultSort('tgl_generate', 'desc')
            ->filters([
                SelectFilter::make('tipe_laporan')
                    ->label('Tipe Laporan')
                    ->options([
                        'presensi'               => 'Presensi Harian',
                        'rekap_presensi_bulanan' => 'Laporan Jumlah Presensi Per Karyawan',
                        'rekap_pekerjaan'        => 'Rekap Pekerjaan',
                    ])
                    ->query(fn (Builder $query, array $data): Builder => match ($data['value'] ?? null) {
                        'rekap_pekerjaan'        => $query->where('judul', 'like', '%pekerjaan%'),
                        'rekap_presensi_bulanan' => $query->where(fn ($q) => $q
                            ->where('judul', 'like', '%jumlah presensi%')
                            ->orWhere('judul', 'like', '%rekap%')
                            ->orWhere('judul', 'like', '%potongan%')
                        )->where('judul', 'not like', '%pekerjaan%'),
                        'presensi'               => $query->where('judul', 'like', '%presensi%')
                            ->where('judul', 'not like', '%jumlah presensi%')
                            ->where('judul', 'not like', '%rekap%')
                            ->where('judul', 'not like', '%pekerjaan%'),
                        default                  => $query,
                    }),
                SelectFilter::make('jenis')
                    ->options([
                        'Harian' => 'Harian',
                        'Mingguan' => 'Mingguan',
                        'Bulanan' => 'Bulanan',
                        'Tahunan' => 'Tahunan',
                    ]),
                SelectFilter::make('periode')
                    ->label('Periode')
                    ->options(fn (): array => LaporanPresensi::query()
                        ->whereNull('user_id')
                        ->select('periode')
                        ->distinct()
                        ->orderByDesc('periode')
                        ->pluck('periode', 'periode')
                        ->all()),
            ])
            ->recordActions([
                \Filament\Actions\Action::make('export_csv')
                    ->label('CSV')
                    ->icon('heroicon-o-document-text')
                    ->color('success')
                    ->url(fn (LaporanPresensi $record): string => self::resolveExportRoute($record, 'csv'),
                        shouldOpenInNewTab: true),
                \Filament\Actions\Action::make('export_excel')
                    ->label('Excel')
                    ->icon('heroicon-o-table-cells')
                    ->color('primary')
                    ->url(fn (LaporanPresensi $record): string => self::resolveExportRoute($record, 'excel'),
                        shouldOpenInNewTab: true),
                \Filament\Actions\Action::make('export_pdf')
                    ->label('PDF')
                    ->icon('heroicon-o-document-arrow-down')
                    ->color('danger')
                    ->url(fn (LaporanPresensi $record): string => self::resolveExportRoute($record, 'pdf'),
                        shouldOpenInNewTab: true),
                EditAction::make()
                    ->visible(fn () => Auth::user()?->isAdmin()),
                DeleteAction::make()
                    ->visible(fn () => Auth::user()?->isAdmin()),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ])->visible(fn () => Auth::user()?->isAdmin()),
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
