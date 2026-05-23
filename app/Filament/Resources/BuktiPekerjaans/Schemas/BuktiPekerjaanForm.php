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
                    ->getOptionLabelFromRecordUsing(fn ($record) => "{$record->jadwal?->tanggal_kerja?->format('d M Y')} - {$record->nama_lokasi} ({$record->karyawan?->user?->nama})")
                    ->searchable()
                    ->preload()
                    ->required(),
                \Filament\Forms\Components\Select::make('karyawan_id')
                    ->label('Karyawan')
                    ->searchable()
                    ->getSearchResultsUsing(fn (string $search) => \App\Models\Karyawan::with('user')
                        ->when(filled($search), fn ($q) => $q->where(fn ($inner) => $inner
                            ->where('nik', 'like', "%{$search}%")
                            ->orWhereHas('user', fn ($u) => $u->where('nama', 'like', "%{$search}%"))
                        ))
                        ->limit(50)
                        ->get()
                        ->mapWithKeys(fn ($k) => [$k->id => "{$k->nik} - " . ($k->user?->nama ?? '-')])
                        ->all()
                    )
                    ->getOptionLabelUsing(fn ($value) => ($k = \App\Models\Karyawan::with('user')->find($value))
                        ? "{$k->nik} - " . ($k->user?->nama ?? '-')
                        : $value
                    )
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
