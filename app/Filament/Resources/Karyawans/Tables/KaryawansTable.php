<?php

namespace App\Filament\Resources\Karyawans\Tables;

use App\Models\Karyawan;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class KaryawansTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.nama')
                    ->label('User')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('nik')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('posisi_karyawan')
                    ->label('Posisi')
                    ->searchable(),
                TextColumn::make('status_kontrak')
                    ->badge(),
                TextColumn::make('tgl_masuk')
                    ->date()
                    ->sortable(),
                TextColumn::make('no_hp')
                    ->label('No HP')
                    ->searchable(),
                TextColumn::make('bidang_tugas')
                    ->label('Bidang Tugas')
                    ->searchable(),
            ])
            ->filters([
                SelectFilter::make('status_kontrak')
                    ->label('Status Kontrak')
                    ->options([
                        'kontrak' => 'Kontrak',
                        'tetap' => 'Tetap',
                    ]),
                SelectFilter::make('bidang_tugas')
                    ->label('Bidang Tugas')
                    ->options(fn (): array => Karyawan::query()
                        ->select('bidang_tugas')
                        ->distinct()
                        ->orderBy('bidang_tugas')
                        ->pluck('bidang_tugas', 'bidang_tugas')
                        ->all()),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
