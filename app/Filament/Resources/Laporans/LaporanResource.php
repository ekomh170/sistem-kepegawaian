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
use Filament\Forms\Components\Textarea;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class LaporanResource extends Resource
{
    protected static ?string $model = Laporan::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static ?string $navigationLabel = 'Laporan';

    protected static string|\UnitEnum|null $navigationGroup = 'Keuangan & Laporan';

    protected static ?int $navigationSort = 8;

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
                Section::make('Informasi Laporan')
                    ->columns(2)
                    ->columnSpanFull()
                    ->schema([
                        Select::make('tipe_laporan')
                            ->label('Tipe Laporan')
                            ->options([
                                'presensi' => '📋 Laporan Presensi (Detail Harian)',
                                'rekap_presensi_bulanan' => '💰 Laporan Rekap Presensi Bulanan',
                                'rekap_pekerjaan' => '📦 Rekap Pekerjaan',
                            ])
                            ->required()
                            ->default('presensi')
                            ->live()
                            ->afterStateHydrated(function ($set, $record) {
                                if ($record && $record->judul) {
                                    $judul = strtolower($record->judul);
                                    if (str_contains($judul, 'pekerjaan')) {
                                        $set('tipe_laporan', 'rekap_pekerjaan');
                                    } elseif (str_contains($judul, 'rekap') || str_contains($judul, 'bulanan') || str_contains($judul, 'potongan')) {
                                        $set('tipe_laporan', 'rekap_presensi_bulanan');
                                    } else {
                                        $set('tipe_laporan', 'presensi');
                                    }
                                }
                            })
                            ->afterStateUpdated(function ($set, $get) {
                                self::generateJudul($set, $get);
                            })
                            ->dehydrated(false)
                            ->columnSpanFull(),

                        Select::make('jenis')
                            ->label('Jenis Laporan')
                            ->options([
                                'Harian' => '📅 Harian',
                                'Mingguan' => '📆 Mingguan',
                                'Bulanan' => '📊 Bulanan',
                                'Tahunan' => '📈 Tahunan',
                            ])
                            ->required()
                            ->default('Bulanan')
                            ->live()
                            ->afterStateUpdated(function ($set, $get) {
                                self::generateJudul($set, $get);
                            }),

                        // Periode - format berubah sesuai jenis
                        TextInput::make('periode')
                            ->label(fn ($get) => match ($get('jenis')) {
                                'Harian' => 'Periode (Tanggal)',
                                'Tahunan' => 'Periode (Tahun)',
                                default => 'Periode (Bulan)',
                            })
                            ->required()
                            ->maxLength(20)
                            ->placeholder(fn ($get) => match ($get('jenis')) {
                                'Harian' => '2026-05-09',
                                'Tahunan' => '2026',
                                default => '2026-04',
                            })
                            ->helperText(fn ($get) => match ($get('jenis')) {
                                'Harian' => 'Format: YYYY-MM-DD (contoh: 2026-05-09)',
                                'Tahunan' => 'Format: YYYY (contoh: 2026)',
                                default => 'Format: YYYY-MM (contoh: 2026-04)',
                            })
                            ->live(onBlur: true)
                            ->afterStateUpdated(function ($set, $get) {
                                self::generateJudul($set, $get);
                            }),

                        \Filament\Forms\Components\Hidden::make('judul')
                            ->dehydrated(),

                        Select::make('generated_by')
                            ->label('Dibuat Oleh')
                            ->relationship('user', 'nama')
                            ->default(fn () => Auth::id())
                            ->required()
                            ->disabled()
                            ->dehydrated(),

                        DateTimePicker::make('tgl_generate')
                            ->label('Tanggal Generate')
                            ->default(now())
                            ->required(),

                        Textarea::make('filter')
                            ->label('Catatan')
                            ->columnSpanFull()
                            ->rows(2)
                            ->placeholder('Catatan tambahan tentang laporan ini')
                            ->default(null),
                    ]),
            ]);
    }

    private static function resolveTipeLabel(string $judul): string
    {
        $judul = strtolower($judul);
        if (str_contains($judul, 'pekerjaan')) {
            return 'Rekap Pekerjaan';
        }
        if (str_contains($judul, 'rekap') || str_contains($judul, 'bulanan')) {
            return 'Rekap Presensi Bulanan';
        }
        return 'Presensi Harian';
    }

    private static function resolveExportRoute(Laporan $record, string $format): string
    {
        $judul = strtolower($record->judul);
        $params = ['periode' => $record->periode];

        if (str_contains($judul, 'pekerjaan')) {
            return route("laporan.export-pekerjaan.{$format}", $params);
        }

        if (str_contains($judul, 'presensi')) {
            $isBulanan = str_contains($judul, 'rekap') || str_contains($judul, 'bulanan');
            return $isBulanan
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
            'rekap_presensi_bulanan' => 'Laporan Rekap Presensi Bulanan',
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
                    ->state(fn (Laporan $record): string => self::resolveTipeLabel($record->judul))
                    ->color(fn (string $state): string => match ($state) {
                        'Presensi Harian'          => 'info',
                        'Rekap Presensi Bulanan'   => 'success',
                        'Rekap Pekerjaan'          => 'warning',
                        default                    => 'gray',
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
                TextColumn::make('user.nama')
                    ->label('Dibuat Oleh'),
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
                        'rekap_presensi_bulanan' => 'Rekap Presensi Bulanan',
                        'rekap_pekerjaan'        => 'Rekap Pekerjaan',
                    ])
                    ->query(fn (Builder $query, array $data): Builder => match ($data['value'] ?? null) {
                        'rekap_pekerjaan'        => $query->where('judul', 'like', '%pekerjaan%'),
                        'rekap_presensi_bulanan' => $query->where(fn ($q) => $q
                            ->where('judul', 'like', '%rekap%')
                            ->orWhere('judul', 'like', '%bulanan%')
                        )->where('judul', 'not like', '%pekerjaan%'),
                        'presensi'               => $query->where('judul', 'like', '%presensi%')
                            ->where('judul', 'not like', '%rekap%')
                            ->where('judul', 'not like', '%bulanan%')
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
                    ->options(fn (): array => Laporan::query()
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
                    ->url(fn (Laporan $record): string => self::resolveExportRoute($record, 'csv'),
                        shouldOpenInNewTab: true),
                \Filament\Actions\Action::make('export_excel')
                    ->label('Excel')
                    ->icon('heroicon-o-table-cells')
                    ->color('primary')
                    ->url(fn (Laporan $record): string => self::resolveExportRoute($record, 'excel'),
                        shouldOpenInNewTab: true),
                \Filament\Actions\Action::make('export_pdf')
                    ->label('PDF')
                    ->icon('heroicon-o-document-arrow-down')
                    ->color('danger')
                    ->url(fn (Laporan $record): string => self::resolveExportRoute($record, 'pdf'),
                        shouldOpenInNewTab: true),
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
            'index' => ListLaporans::route('/'),
            'create' => CreateLaporan::route('/create'),
            'edit' => EditLaporan::route('/{record}/edit'),
        ];
    }
}
