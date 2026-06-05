<?php

namespace App\Filament\Resources\Verifikasis;

use App\Filament\Resources\Presensis\PresensiResource;
use App\Filament\Resources\Verifikasis\Pages\ListVerifikasis;
use App\Models\Presensi;
use Filament\Actions\ViewAction;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

/**
 * Menu "Antrian Verifikasi" — view atas model Presensi yang difilter ke
 * status_verifikasi = pending. Verifikasi V3 inline; resource ini hanya
 * mempercepat alur supervisor (lihat antrian + aksi Setujui/Tolak cepat).
 * Kolom & aksi di-reuse dari PresensiResource agar satu sumber kebenaran.
 */
class VerifikasiResource extends Resource
{
    protected static ?string $model = Presensi::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-shield-check';

    protected static ?string $navigationLabel = 'Verifikasi';

    protected static ?string $modelLabel = 'Verifikasi Presensi';

    protected static ?string $pluralModelLabel = 'Verifikasi Presensi';

    protected static string|\UnitEnum|null $navigationGroup = 'Operasional';

    protected static ?int $navigationSort = 4;

    public static function canViewAny(): bool
    {
        // Verifikasi = tugas SUPERVISOR saja (RBAC: admin ❌). Lihat docs/v3/04-hak-akses-rbac.md.
        return (bool) (Auth::user()?->isSupervisor());
    }

    public static function canCreate(): bool
    {
        return false;
    }

    /**
     * Antrian: presensi dengan CHECK-IN nyata (jam_masuk ada) yang masih pending.
     * Alpa & Izin tidak punya jam_masuk → bukan objek verifikasi, tidak masuk antrian.
     */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('status_verifikasi', 'pending')
            ->whereNotNull('jam_masuk');
    }

    public static function getNavigationBadge(): ?string
    {
        if (! Auth::user()?->isSupervisor()) {
            return null;
        }

        $count = Presensi::where('status_verifikasi', 'pending')->whereNotNull('jam_masuk')->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    /** Reuse detail presensi untuk modal View. */
    public static function infolist(Schema $schema): Schema
    {
        return PresensiResource::infolist($schema);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns(PresensiResource::presensiColumns())
            ->defaultSort('tanggal', 'asc')
            ->recordActions([
                ViewAction::make(),
                ...PresensiResource::verifikasiRecordActions(),
            ])
            ->emptyStateHeading('Tidak ada presensi menunggu verifikasi')
            ->emptyStateDescription('Semua presensi sudah diverifikasi.');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListVerifikasis::route('/'),
        ];
    }
}
