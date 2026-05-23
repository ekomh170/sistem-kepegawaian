<?php

namespace App\Filament\Resources\Jadwals\Schemas;

use App\Models\Admin;
use App\Models\Karyawan;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;

class JadwalForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Penugasan Jadwal')
                    ->columns(2)
                    ->columnSpanFull()
                    ->schema([
                        Select::make('karyawan_id')
                            ->label('Karyawan')
                            ->searchable()
                            ->getSearchResultsUsing(fn (string $search) => Karyawan::with('user')
                                ->when(filled($search), fn ($q) => $q->where(fn ($inner) => $inner
                                    ->where('nik', 'like', "%{$search}%")
                                    ->orWhereHas('user', fn ($u) => $u->where('nama', 'like', "%{$search}%"))
                                ))
                                ->limit(50)
                                ->get()
                                ->mapWithKeys(fn ($k) => [$k->id => "{$k->nik} - " . ($k->user?->nama ?? '-')])
                                ->all()
                            )
                            ->getOptionLabelUsing(function ($value) {
                                $k = Karyawan::with('user')->find($value);
                                return $k ? "{$k->nik} - " . ($k->user?->nama ?? '-') : $value;
                            })
                            ->required(),

                        Select::make('admin_id')
                            ->label('Admin Pembuat')
                            ->searchable()
                            ->getSearchResultsUsing(fn (string $search) => Admin::with('user')
                                ->when(filled($search), fn ($q) => $q->where(fn ($inner) => $inner
                                    ->where('nik', 'like', "%{$search}%")
                                    ->orWhereHas('user', fn ($u) => $u->where('nama', 'like', "%{$search}%"))
                                ))
                                ->limit(20)
                                ->get()
                                ->mapWithKeys(fn ($a) => [$a->id => "{$a->nik} - " . ($a->user?->nama ?? '-')])
                                ->all()
                            )
                            ->getOptionLabelUsing(function ($value) {
                                $a = Admin::with('user')->find($value);
                                return $a ? "{$a->nik} - " . ($a->user?->nama ?? '-') : $value;
                            })
                            ->default(fn () => Admin::where('user_id', Auth::id())->first()?->id),
                    ]),

                Section::make('Tanggal & Jam Kerja')
                    ->description('Sesuai kebijakan: 6 hari kerja per minggu, 1 hari libur ditentukan admin, jam kerja default 08.00–16.00.')
                    ->columns(3)
                    ->columnSpanFull()
                    ->schema([
                        DatePicker::make('tanggal_kerja')
                            ->label('Tanggal Mulai')
                            ->required()
                            ->native(false),
                        DatePicker::make('tanggal_akhir')
                            ->label('Tanggal Akhir (opsional)')
                            ->helperText('Isi jika ingin membuat jadwal untuk rentang tanggal sekaligus.')
                            ->visible(fn ($context) => $context === 'create')
                            ->native(false)
                            ->afterOrEqual('tanggal_kerja'),
                        TimePicker::make('jam_masuk')
                            ->required()
                            ->seconds(false)
                            ->default('08:00'),
                        TimePicker::make('jam_pulang')
                            ->required()
                            ->seconds(false)
                            ->default('16:00'),
                    ]),

                Section::make('Status')
                    ->columns(2)
                    ->columnSpanFull()
                    ->schema([
                        Toggle::make('hari_libur')
                            ->label('Tandai Hari Libur')
                            ->helperText('Jika aktif, presensi tidak diharapkan pada tanggal ini.')
                            ->default(false),
                        Select::make('status')
                            ->options([
                                'aktif' => 'Aktif',
                                'dibatalkan' => 'Dibatalkan',
                            ])
                            ->default('aktif')
                            ->required(),
                    ]),
            ]);
    }
}
