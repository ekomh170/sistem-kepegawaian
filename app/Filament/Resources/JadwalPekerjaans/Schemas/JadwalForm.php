<?php

namespace App\Filament\Resources\JadwalPekerjaans\Schemas;

use App\Models\User;
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
                            ->getOptionLabelUsing(function ($value) {
                                $u = User::find($value);
                                return $u ? "{$u->nik} - {$u->nama}" : $value;
                            })
                            ->required(),

                        Select::make('dibuat_oleh')
                            ->label('Admin Pembuat')
                            ->searchable()
                            ->getSearchResultsUsing(fn (string $search) => User::query()
                                ->where('role', 'admin')
                                ->when(filled($search), fn ($q) => $q->where(fn ($inner) => $inner
                                    ->where('nik', 'like', "%{$search}%")
                                    ->orWhere('nama', 'like', "%{$search}%")
                                ))
                                ->limit(20)
                                ->get()
                                ->mapWithKeys(fn ($u) => [$u->id => "{$u->nik} - {$u->nama}"])
                                ->all()
                            )
                            ->getOptionLabelUsing(function ($value) {
                                $u = User::find($value);
                                return $u ? "{$u->nik} - {$u->nama}" : $value;
                            })
                            ->default(fn () => Auth::id()),
                    ]),

                Section::make('Tanggal & Jam Kerja')
                    ->description('Sesuai kebijakan: 6 hari kerja per minggu, 1 hari libur ditentukan admin, jam kerja default 08.00â€“16.00.')
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
