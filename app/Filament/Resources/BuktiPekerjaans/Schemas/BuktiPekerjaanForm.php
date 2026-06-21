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
                    ->getOptionLabelFromRecordUsing(fn ($record) => "{$record->jadwal?->tanggal_kerja?->format('d M Y')} - {$record->nama_lokasi} ({$record->user?->nama})")
                    ->searchable()
                    ->preload()
                    ->required(),
                \Filament\Forms\Components\Select::make('user_id')
                    ->label('Karyawan')
                    ->searchable()
                    ->getSearchResultsUsing(fn (string $search) => \App\Models\User::query()
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
                    ->getOptionLabelUsing(fn ($value) => ($u = \App\Models\User::find($value))
                        ? "{$u->nik} - {$u->nama}"
                        : $value
                    )
                    ->required(),
                \Filament\Forms\Components\FileUpload::make('foto_before')
                    ->label('Foto Sebelum (galeri, bisa banyak)')
                    ->multiple()
                    ->image()
                    ->reorderable()
                    ->panelLayout('grid')
                    ->directory('bukti_pekerjaan')
                    ->disk('public')
                    ->columnSpanFull(),
                \Filament\Forms\Components\FileUpload::make('foto_after')
                    ->label('Foto Sesudah (galeri, bisa banyak)')
                    ->multiple()
                    ->image()
                    ->reorderable()
                    ->panelLayout('grid')
                    ->directory('bukti_pekerjaan')
                    ->disk('public')
                    ->columnSpanFull(),
                \Filament\Forms\Components\FileUpload::make('foto')
                    ->label('Foto Bukti Gabungan (lama)')
                    ->multiple()
                    ->image()
                    ->reorderable()
                    ->panelLayout('grid')
                    ->directory('bukti_pekerjaan')
                    ->disk('public')
                    ->columnSpanFull(),
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
