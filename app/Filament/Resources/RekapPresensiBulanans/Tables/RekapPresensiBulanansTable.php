<?php

namespace App\Filament\Resources\RekapPresensiBulanans\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class RekapPresensiBulanansTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('karyawan.user.nama')
                    ->label('Karyawan')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('periode')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('jumlah_hadir')
                    ->label('Hadir'),
                TextColumn::make('jumlah_tidak_hadir')
                    ->label('Absen'),
                TextColumn::make('jumlah_terlambat')
                    ->label('Telat'),
                TextColumn::make('total_potongan_keterlambatan')
                    ->label('Potongan')
                    ->money('idr')
                    ->color('danger'),
                TextColumn::make('gaji_pokok')
                    ->label('Gaji Pokok')
                    ->money('idr'),
                TextColumn::make('gaji_bersih')
                    ->label('Gaji Bersih')
                    ->money('idr')
                    ->color('success'),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'draft' => 'warning',
                        'final' => 'success',
                        'dibatalkan' => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('admin.user.nama')
                    ->label('Oleh Admin')
                    ->placeholder('-'),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
