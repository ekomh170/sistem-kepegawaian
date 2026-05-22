<?php

namespace App\Filament\Resources\RekapPresensiBulanans\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

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
                EditAction::make()
                    ->visible(fn () => Auth::user()?->role === 'admin'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ])->visible(fn () => Auth::user()?->role === 'admin'),
            ]);
    }
}
