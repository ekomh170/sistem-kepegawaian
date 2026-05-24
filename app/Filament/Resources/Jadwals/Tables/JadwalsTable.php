<?php

namespace App\Filament\Resources\Jadwals\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Filament\Forms\Components\DatePicker;
use Illuminate\Database\Eloquent\Builder;

class JadwalsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('karyawan.user.nama')
                    ->label('Karyawan')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('tanggal_kerja')
                    ->label('Tanggal')
                    ->date('d M Y')
                    ->sortable(),
                TextColumn::make('jam_masuk')
                    ->label('Jam Masuk')
                    ->time('H:i'),
                TextColumn::make('jam_pulang')
                    ->label('Jam Pulang')
                    ->time('H:i'),
                IconColumn::make('hari_libur')
                    ->label('Libur')
                    ->boolean(),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'aktif' => 'success',
                        'dibatalkan' => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('admin.user.nama')
                    ->label('Dibuat Oleh')
                    ->placeholder('-')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('tanggal_kerja', 'desc')
            ->filters([
                Filter::make('tanggal_kerja')
                    ->label('Tanggal')
                    ->form([
                        DatePicker::make('tanggal')
                            ->label('Tanggal Kerja')
                            ->default(today()),
                    ])
                    ->query(fn (Builder $query, array $data): Builder =>
                        $query->when($data['tanggal'], fn ($q, $tgl) => $q->whereDate('tanggal_kerja', $tgl))
                    )
                    ->default(['tanggal' => today()->toDateString()])
                    ->indicateUsing(fn (array $data): ?string =>
                        $data['tanggal'] ? 'Tanggal: ' . \Carbon\Carbon::parse($data['tanggal'])->format('d M Y') : null
                    ),
                SelectFilter::make('hari_libur')
                    ->label('Status Hari')
                    ->options([
                        '0' => 'Hari Kerja',
                        '1' => 'Hari Libur',
                    ]),
                SelectFilter::make('status')
                    ->options([
                        'aktif' => 'Aktif',
                        'dibatalkan' => 'Dibatalkan',
                    ]),
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
