<?php

namespace App\Filament\Resources\DetailPekerjaans\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TimePicker;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Http;

class DetailPekerjaanForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('karyawan_id')
                    ->relationship('karyawan', 'nik')
                    ->getOptionLabelFromRecordUsing(fn ($record) => "{$record->nik} - " . ($record->user?->nama ?? 'Unknown'))
                    ->searchable(['nik'])
                    ->preload()
                    ->required(),
                DatePicker::make('tanggal')
                    ->required(),
                TimePicker::make('jam_masuk')
                    ->required(),
                TimePicker::make('jam_pulang')
                    ->required(),
                TextInput::make('nama_lokasi')
                    ->required()
                    ->live(onBlur: true)
                    ->helperText('Otomatis terisi dari peta, tapi bisa diubah manual.'),
                Textarea::make('alamat_lokasi')
                    ->default(null)
                    ->live(onBlur: true)
                    ->helperText('Otomatis terisi dari peta, tapi bisa diubah manual.')
                    ->columnSpanFull(),
                \Dotswan\MapPicker\Fields\Map::make('location')
                    ->label('Pilih Lokasi di Peta (Klik/Geser Pin)')
                    ->columnSpanFull()
                    ->defaultLocation(
                        latitude: (float) \App\Models\Setting::get('kantor_lat', -6.2087634),
                        longitude: (float) \App\Models\Setting::get('kantor_lng', 106.8222568)
                    )
                    ->afterStateUpdated(function ($set, ?array $state): void {
                        if (!$state || !isset($state['lat'], $state['lng'])) {
                            return;
                        }

                        $lat = $state['lat'];
                        $lng = $state['lng'];

                        $set('latitude', $lat);
                        $set('longitude', $lng);

                        // Reverse geocoding via Nominatim (OpenStreetMap) — gratis, tanpa API key
                        try {
                            $response = Http::withHeaders([
                                'User-Agent' => 'SistemKepegawaian/1.0',
                            ])->get('https://nominatim.openstreetmap.org/reverse', [
                                'lat' => $lat,
                                'lon' => $lng,
                                'format' => 'json',
                                'addressdetails' => 1,
                                'accept-language' => 'id',
                            ]);

                            if ($response->successful()) {
                                $data = $response->json();
                                $address = $data['address'] ?? [];

                                // Nama lokasi: ambil yang paling spesifik
                                $namaLokasi = $address['building']
                                    ?? $address['amenity']
                                    ?? $address['office']
                                    ?? $address['shop']
                                    ?? $address['road']
                                    ?? $address['neighbourhood']
                                    ?? $address['suburb']
                                    ?? ($data['display_name'] ? explode(',', $data['display_name'])[0] : null)
                                    ?? '';

                                // Alamat lengkap
                                $alamatLokasi = $data['display_name'] ?? '';

                                $set('nama_lokasi', $namaLokasi);
                                $set('alamat_lokasi', $alamatLokasi);
                            }
                        } catch (\Throwable $e) {
                            // Jika API gagal, biarkan field kosong agar admin isi manual
                        }
                    })
                    ->afterStateHydrated(function ($set, $record): void {
                        if ($record && $record->latitude && $record->longitude) {
                            $set('location', ['lat' => $record->latitude, 'lng' => $record->longitude]);
                        }
                    })
                    ->live(onBlur: true)
                    ->showMarker()
                    ->markerColor('#22c55e')
                    ->showFullscreenControl()
                    ->showZoomControl()
                    ->draggable()
                    ->clickable(true),
                TextInput::make('latitude')
                    ->numeric()
                    ->live()
                    ->default(null),
                TextInput::make('longitude')
                    ->numeric()
                    ->live()
                    ->default(null),
                TextInput::make('radius_meter')
                    ->required()
                    ->numeric()
                    ->default(500),
                Textarea::make('keterangan_pekerjaan')
                    ->default(null)
                    ->columnSpanFull(),
                \Filament\Forms\Components\Select::make('status')
                    ->options([
                        'pending' => 'Pending (Menunggu Respon Karyawan)',
                        'disetujui' => 'Disetujui (Diterima Karyawan)',
                        'ditolak' => 'Ditolak (Ditolak Karyawan)',
                    ])
                    ->default('pending')
                    ->disabled()
                    ->dehydrated(),
                Textarea::make('alasan_tolak')
                    ->label('Alasan Penolakan (dari Karyawan)')
                    ->disabled()
                    ->columnSpanFull(),
            ]);
    }
}
