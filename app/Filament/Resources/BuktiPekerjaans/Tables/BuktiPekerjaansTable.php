<?php

namespace App\Filament\Resources\BuktiPekerjaans\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Table;

class BuktiPekerjaansTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                \Filament\Tables\Columns\TextColumn::make('karyawan.user.nama')->label('Karyawan')->searchable()->sortable(),
                \Filament\Tables\Columns\TextColumn::make('detailPekerjaan.nama_lokasi')->label('Tugas / Lokasi')->searchable(),
                \Filament\Tables\Columns\TextColumn::make('keterangan')->limit(50),
                \Filament\Tables\Columns\TextColumn::make('status')->badge(),
                \Filament\Tables\Columns\TextColumn::make('uploaded_at')->dateTime()->sortable(),
            ])
            ->filters([
                //
            ])
            ->recordActions([
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
