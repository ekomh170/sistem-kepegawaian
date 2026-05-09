<?php

namespace App\Filament\Resources\BuktiPekerjaans\Schemas;

use Filament\Schemas\Schema;

class BuktiPekerjaanForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                \Filament\Forms\Components\Select::make('detail_pekerjaan_id')
                    ->label('Detail Pekerjaan')
                    ->relationship('detailPekerjaan', 'nama_lokasi')
                    ->getOptionLabelFromRecordUsing(fn ($record) => "{$record->tanggal?->format('d M Y')} - {$record->nama_lokasi} ({$record->karyawan?->user?->nama})")
                    ->searchable()
                    ->preload()
                    ->required(),
                \Filament\Forms\Components\Select::make('karyawan_id')
                    ->label('Karyawan')
                    ->relationship('karyawan', 'nik')
                    ->getOptionLabelFromRecordUsing(fn ($record) => "{$record->nik} - " . ($record->user?->nama ?? 'Unknown'))
                    ->searchable(['nik'])
                    ->preload()
                    ->required(),
                \Filament\Forms\Components\FileUpload::make('foto_before')
                    ->image()
                    ->required(),
                \Filament\Forms\Components\FileUpload::make('foto_after')
                    ->image()
                    ->required(),
                \Filament\Forms\Components\Textarea::make('keterangan')
                    ->maxLength(65535)
                    ->columnSpanFull(),
                \Filament\Forms\Components\Select::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'disetujui' => 'Disetujui',
                        'ditolak' => 'Ditolak',
                    ])
                    ->required(),
                \Filament\Forms\Components\DateTimePicker::make('uploaded_at')
                    ->required(),
            ]);
    }
}
