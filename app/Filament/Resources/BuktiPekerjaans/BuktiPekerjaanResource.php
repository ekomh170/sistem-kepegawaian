<?php

namespace App\Filament\Resources\BuktiPekerjaans;

use App\Filament\Resources\BuktiPekerjaans\Pages\CreateBuktiPekerjaan;
use App\Filament\Resources\BuktiPekerjaans\Pages\EditBuktiPekerjaan;
use App\Filament\Resources\BuktiPekerjaans\Pages\ListBuktiPekerjaans;
use App\Filament\Resources\BuktiPekerjaans\Pages\ViewBuktiPekerjaan;
use App\Filament\Resources\BuktiPekerjaans\Schemas\BuktiPekerjaanForm;
use App\Filament\Resources\BuktiPekerjaans\Schemas\BuktiPekerjaanInfolist;
use App\Filament\Resources\BuktiPekerjaans\Tables\BuktiPekerjaansTable;
use App\Models\BuktiPekerjaan;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class BuktiPekerjaanResource extends Resource
{
    protected static ?string $model = BuktiPekerjaan::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $navigationLabel = 'Bukti Pekerjaan';

    protected static string|\UnitEnum|null $navigationGroup = 'Operasional';

    protected static ?int $navigationSort = 5;

    protected static ?string $recordTitleAttribute = 'id';

    public static function canViewAny(): bool
    {
        return Auth::user()?->isAdmin();
    }

    public static function canCreate(): bool
    {
        return Auth::user()?->isAdmin();
    }

    public static function canEdit(\Illuminate\Database\Eloquent\Model $record): bool
    {
        return Auth::user()?->isAdmin();
    }

    public static function canDelete(\Illuminate\Database\Eloquent\Model $record): bool
    {
        return Auth::user()?->isAdmin();
    }

    public static function canDeleteAny(): bool
    {
        return Auth::user()?->isAdmin();
    }

    public static function form(Schema $schema): Schema
    {
        return BuktiPekerjaanForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return BuktiPekerjaanInfolist::configure($schema);
    }

    /**
     * Judul record (breadcrumb/aksi) — hindari menampilkan "1" (id);
     * pakai nama lokasi tugas.
     */
    public static function getRecordTitle(?\Illuminate\Database\Eloquent\Model $record): ?string
    {
        return $record?->detailPekerjaan?->nama_lokasi ?? 'Bukti Pekerjaan';
    }

    public static function table(Table $table): Table
    {
        return BuktiPekerjaansTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListBuktiPekerjaans::route('/'),
            'create' => CreateBuktiPekerjaan::route('/create'),
            'view' => ViewBuktiPekerjaan::route('/{record}'),
            'edit' => EditBuktiPekerjaan::route('/{record}/edit'),
        ];
    }
}
