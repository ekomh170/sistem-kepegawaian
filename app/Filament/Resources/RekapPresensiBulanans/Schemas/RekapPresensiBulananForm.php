<?php

namespace App\Filament\Resources\RekapPresensiBulanans\Schemas;

use App\Models\Karyawan;
use App\Models\Presensi;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;

class RekapPresensiBulananForm
{
    /**
     * Hitung rekap dari data presensi berdasarkan karyawan_id dan periode
     */
    private static function hitungRekap($set, $get): void
    {
        $karyawanId = $get('karyawan_id');
        $periode = $get('periode');

        if (!$karyawanId || !$periode || strlen($periode) < 7) {
            return;
        }

        // Ambil data presensi karyawan di periode tersebut
        $presensis = Presensi::where('karyawan_id', $karyawanId)
            ->whereRaw("DATE_FORMAT(tanggal, '%Y-%m') = ?", [$periode])
            ->get();

        $jumlahHadir = $presensis->whereIn('status_presensi', ['hadir', 'terlambat'])->count();
        $jumlahTidakHadir = $presensis->where('status_presensi', 'tidak_hadir')->count();
        $jumlahTerlambat = $presensis->where('status_presensi', 'terlambat')->count();
        $totalPotongan = (float) $presensis->where('status_presensi', 'terlambat')->sum('potongan_terlambat');

        // Ambil gaji pokok dari data karyawan
        $karyawan = Karyawan::find($karyawanId);
        $gajiPokok = (float) ($karyawan?->gaji_pokok ?? 0);
        $gajiBersih = max(0, $gajiPokok - $totalPotongan);

        $set('jumlah_hadir', $jumlahHadir);
        $set('jumlah_tidak_hadir', $jumlahTidakHadir);
        $set('jumlah_terlambat', $jumlahTerlambat);
        $set('total_potongan_keterlambatan', $totalPotongan);
        $set('gaji_pokok', $gajiPokok);
        $set('gaji_bersih', $gajiBersih);
    }

    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('karyawan_id')
                    ->relationship('karyawan', 'nik')
                    ->getOptionLabelFromRecordUsing(fn ($record) => "{$record->nik} - " . ($record->user?->nama ?? 'Unknown'))
                    ->searchable(['nik'])
                    ->preload()
                    ->required()
                    ->live()
                    ->afterStateUpdated(function ($set, $get) {
                        self::hitungRekap($set, $get);
                    }),
                Select::make('admin_id')
                    ->relationship('admin', 'nik')
                    ->getOptionLabelFromRecordUsing(fn ($record) => "{$record->nik} - " . ($record->user?->nama ?? 'Unknown'))
                    ->searchable(['nik'])
                    ->preload()
                    ->label('Admin Pembuat')
                    ->default(fn () => \App\Models\Admin::where('user_id', Auth::id())->first()?->id),
                TextInput::make('periode')
                    ->required()
                    ->placeholder('Contoh: 2026-05')
                    ->maxLength(7)
                    ->live(onBlur: true)
                    ->afterStateUpdated(function ($set, $get) {
                        self::hitungRekap($set, $get);
                    }),
                TextInput::make('jumlah_hadir')
                    ->required()
                    ->numeric()
                    ->default(0)
                    ->disabled()
                    ->dehydrated()
                    ->helperText('Otomatis dari data presensi'),
                TextInput::make('jumlah_tidak_hadir')
                    ->required()
                    ->numeric()
                    ->default(0)
                    ->disabled()
                    ->dehydrated()
                    ->helperText('Otomatis dari data presensi'),
                TextInput::make('jumlah_terlambat')
                    ->required()
                    ->numeric()
                    ->default(0)
                    ->disabled()
                    ->dehydrated()
                    ->helperText('Otomatis dari data presensi'),
                TextInput::make('total_potongan_keterlambatan')
                    ->label('Total Potongan Keterlambatan (Rp)')
                    ->required()
                    ->prefix('Rp')
                    ->default(0)
                    ->disabled()
                    ->dehydrated()
                    ->formatStateUsing(fn ($state) => $state !== null ? number_format((float) $state, 0, ',', '.') : '0')
                    ->dehydrateStateUsing(fn ($state) => (float) str_replace('.', '', $state ?? '0'))
                    ->helperText('Otomatis dari data presensi'),
                TextInput::make('gaji_pokok')
                    ->label('Gaji Pokok (Rp)')
                    ->required()
                    ->prefix('Rp')
                    ->default(0)
                    ->disabled()
                    ->dehydrated()
                    ->formatStateUsing(fn ($state) => $state !== null ? number_format((float) $state, 0, ',', '.') : '0')
                    ->dehydrateStateUsing(fn ($state) => (float) str_replace('.', '', $state ?? '0'))
                    ->helperText('Otomatis dari data karyawan'),
                TextInput::make('gaji_bersih')
                    ->label('Gaji Bersih (Rp)')
                    ->required()
                    ->prefix('Rp')
                    ->default(0)
                    ->disabled()
                    ->dehydrated()
                    ->formatStateUsing(fn ($state) => $state !== null ? number_format((float) $state, 0, ',', '.') : '0')
                    ->dehydrateStateUsing(fn ($state) => (float) str_replace('.', '', $state ?? '0'))
                    ->helperText('Gaji Pokok − Total Potongan (otomatis)'),
                Textarea::make('catatan')
                    ->columnSpanFull()
                    ->default(null),
                Select::make('status')
                    ->options([
                        'draft' => 'Draft',
                        'final' => 'Final',
                        'dibatalkan' => 'Dibatalkan',
                    ])
                    ->default('draft')
                    ->required(),
            ]);
    }
}
