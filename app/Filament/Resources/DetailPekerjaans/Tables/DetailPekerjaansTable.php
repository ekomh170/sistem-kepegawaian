<?php

namespace App\Filament\Resources\DetailPekerjaans\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class DetailPekerjaansTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('karyawan.user.nama')
                    ->label('Karyawan')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('jadwal.tanggal_kerja')
                    ->label('Tanggal')
                    ->date()
                    ->sortable(),
                TextColumn::make('jadwal.jam_masuk')
                    ->label('Jam Masuk')
                    ->time(),
                TextColumn::make('jadwal.jam_pulang')
                    ->label('Jam Pulang')
                    ->time(),
                TextColumn::make('nama_lokasi')
                    ->searchable(),
                TextColumn::make('latitude')
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('longitude')
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('radius_meter')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'disetujui' => 'success',
                        'ditolak' => 'danger',
                        default => 'warning',
                    }),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([])
            ->recordActions([
                ViewAction::make(),
                EditAction::make()
                    ->visible(fn () => \Illuminate\Support\Facades\Auth::user()?->role === 'admin'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ])->visible(fn () => \Illuminate\Support\Facades\Auth::user()?->role === 'admin'),
            ]);
    }
}
