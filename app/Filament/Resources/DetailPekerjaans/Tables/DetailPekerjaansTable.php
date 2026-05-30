<?php

namespace App\Filament\Resources\DetailPekerjaans\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class DetailPekerjaansTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.nama')
                    ->label('Karyawan')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('jadwal.tanggal_kerja')
                    ->label('Tanggal')
                    ->date()
                    ->sortable(),
                TextColumn::make('jadwal.jam_masuk')
                    ->label('Jam Dimulai')
                    ->time(),
                TextColumn::make('jadwal.jam_pulang')
                    ->label('Jam Selesai')
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
            ->modifyQueryUsing(fn (Builder $query) => $query
                ->leftJoin('tb_jadwal_pekerjaan', 'tb_jadwal_pekerjaan.id', '=', 'tb_detail_pekerjaan.jadwal_id')
                ->select('tb_detail_pekerjaan.*')
                ->orderByDesc('tb_jadwal_pekerjaan.tanggal_kerja')
                ->orderByDesc('tb_detail_pekerjaan.id')
            )
            ->filters([
                Filter::make('tanggal_jadwal')
                    ->label('Tanggal Jadwal')
                    ->form([
                        DatePicker::make('tanggal')
                            ->label('Tanggal Jadwal')
                            ->default(today()),
                    ])
                    ->query(fn (Builder $query, array $data): Builder =>
                        $query->when($data['tanggal'], fn ($q, $tgl) =>
                            $q->whereHas('jadwal', fn ($j) => $j->whereDate('tanggal_kerja', $tgl))
                        )
                    )
                    ->default(['tanggal' => today()->toDateString()])
                    ->indicateUsing(fn (array $data): ?string =>
                        $data['tanggal'] ? 'Tanggal: ' . \Carbon\Carbon::parse($data['tanggal'])->format('d M Y') : null
                    ),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make()
                    ->visible(fn () => \Illuminate\Support\Facades\Auth::user()?->isAdmin()),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ])->visible(fn () => \Illuminate\Support\Facades\Auth::user()?->isAdmin()),
            ]);
    }
}
