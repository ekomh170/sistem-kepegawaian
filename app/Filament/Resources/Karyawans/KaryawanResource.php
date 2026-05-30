<?php

namespace App\Filament\Resources\Karyawans;

use App\Filament\Resources\Karyawans\Pages\CreateKaryawan;
use App\Filament\Resources\Karyawans\Pages\EditKaryawan;
use App\Filament\Resources\Karyawans\Pages\ListKaryawans;
use App\Filament\Resources\Karyawans\Pages\ViewKaryawan;
use App\Filament\Resources\Karyawans\Schemas\KaryawanForm;
use App\Filament\Resources\Karyawans\Schemas\KaryawanInfolist;
use App\Filament\Resources\Karyawans\Tables\KaryawansTable;
use App\Models\User;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class KaryawanResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $navigationLabel = 'Karyawan';

    protected static ?string $modelLabel = 'Karyawan';

    protected static ?string $pluralModelLabel = 'Karyawan';

    protected static string|\UnitEnum|null $navigationGroup = 'Manajemen Akun';

    protected static ?int $navigationSort = 2;

    protected static ?string $recordTitleAttribute = 'nama';

    /**
     * V3: KaryawanResource memakai model User yang di-scope ke role karyawan.
     */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('role', 'karyawan');
    }

    public static function getGlobalSearchResultDetails(Model $record): array
    {
        return [
            'Nama' => $record->nama ?? '-',
            'Posisi' => $record->posisi ?? '-',
        ];
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['nik', 'nama'];
    }

    public static function canViewAny(): bool
    {
        return Auth::user()?->isAdmin();
    }

    public static function canCreate(): bool
    {
        return Auth::user()?->isAdmin();
    }

    public static function canEdit(Model $record): bool
    {
        return Auth::user()?->isAdmin();
    }

    public static function canDelete(Model $record): bool
    {
        return Auth::user()?->isAdmin();
    }

    public static function canDeleteAny(): bool
    {
        return Auth::user()?->isAdmin();
    }

    public static function form(Schema $schema): Schema
    {
        return KaryawanForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return KaryawanInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return KaryawansTable::configure($table);
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
            'index' => ListKaryawans::route('/'),
            'create' => CreateKaryawan::route('/create'),
            'view' => ViewKaryawan::route('/{record}'),
            'edit' => EditKaryawan::route('/{record}/edit'),
        ];
    }
}
