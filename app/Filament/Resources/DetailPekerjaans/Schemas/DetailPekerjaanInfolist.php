<?php

namespace App\Filament\Resources\DetailPekerjaans\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class DetailPekerjaanInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informasi Karyawan')
                    ->columns(2)
                    ->columnSpanFull()
                    ->schema([
                        TextEntry::make('karyawan.user.nama')
                            ->label('Nama Karyawan')
                            ->weight('bold')
                            ->size('lg'),
                        TextEntry::make('karyawan.nik')
                            ->label('NIK')
                            ->copyable(),
                        TextEntry::make('karyawan.posisi_karyawan')
                            ->label('Posisi'),
                        TextEntry::make('status')
                            ->label('Status Tugas')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'disetujui' => 'success',
                                'ditolak' => 'danger',
                                default => 'warning',
                            })
                            ->formatStateUsing(fn (string $state): string => match ($state) {
                                'disetujui' => 'Diterima',
                                'ditolak' => 'Ditolak',
                                'pending' => 'Menunggu',
                                default => ucfirst($state),
                            }),
                    ]),

                Section::make('Jadwal Kerja')
                    ->description('Tanggal dan jam penugasan')
                    ->columns(3)
                    ->columnSpanFull()
                    ->schema([
                        TextEntry::make('tanggal')
                            ->label('Tanggal')
                            ->date('d F Y')
                            ->icon('heroicon-o-calendar'),
                        TextEntry::make('jam_masuk')
                            ->label('Jam Masuk')
                            ->time('H:i')
                            ->icon('heroicon-o-clock'),
                        TextEntry::make('jam_pulang')
                            ->label('Jam Pulang')
                            ->time('H:i')
                            ->icon('heroicon-o-clock'),
                    ]),

                Section::make('Lokasi Penugasan')
                    ->description('Detail lokasi kerja')
                    ->columns(2)
                    ->columnSpanFull()
                    ->schema([
                        TextEntry::make('nama_lokasi')
                            ->label('Nama Lokasi')
                            ->weight('bold')
                            ->icon('heroicon-o-map-pin'),
                        TextEntry::make('radius_meter')
                            ->label('Radius Geofence')
                            ->suffix(' meter')
                            ->icon('heroicon-o-signal'),
                        TextEntry::make('alamat_lokasi')
                            ->label('Alamat Lengkap')
                            ->placeholder('-')
                            ->columnSpanFull(),
                        TextEntry::make('latitude')
                            ->label('Latitude')
                            ->numeric(7)
                            ->placeholder('-')
                            ->copyable(),
                        TextEntry::make('longitude')
                            ->label('Longitude')
                            ->numeric(7)
                            ->placeholder('-')
                            ->copyable(),
                    ]),

                Section::make('Detail Tugas')
                    ->columns(2)
                    ->columnSpanFull()
                    ->schema([
                        TextEntry::make('keterangan_pekerjaan')
                            ->label('Keterangan Pekerjaan')
                            ->placeholder('Tidak ada keterangan')
                            ->columnSpanFull(),
                        TextEntry::make('alasan_tolak')
                            ->label('Alasan Penolakan')
                            ->placeholder('-')
                            ->visible(fn ($record) => $record->status === 'ditolak')
                            ->color('danger')
                            ->columnSpanFull(),
                        TextEntry::make('created_at')
                            ->label('Dibuat Pada')
                            ->dateTime('d M Y H:i')
                            ->icon('heroicon-o-clock'),
                    ]),
            ]);
    }
}
