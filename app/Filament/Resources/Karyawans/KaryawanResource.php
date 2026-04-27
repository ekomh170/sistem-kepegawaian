<?php

namespace App\Filament\Resources\Karyawans;

use App\Filament\Resources\Karyawans\Pages\CreateKaryawan;
use App\Filament\Resources\Karyawans\Pages\EditKaryawan;
use App\Filament\Resources\Karyawans\Pages\ListKaryawans;
use App\Filament\Resources\Karyawans\Pages\ViewKaryawan;
use App\Filament\Resources\Karyawans\Schemas\KaryawanForm;
use App\Filament\Resources\Karyawans\Schemas\KaryawanInfolist;
use App\Filament\Resources\Karyawans\Tables\KaryawansTable;
use App\Models\Karyawan;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class KaryawanResource extends Resource
{
    protected static ?string $model = Karyawan::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'nik';

    public static function canViewAny(): bool
    {
        return Auth::user()?->role === 'admin';
    }

    public static function canCreate(): bool
    {
        return Auth::user()?->role === 'admin';
    }

    public static function canEdit(Model $record): bool
    {
        return Auth::user()?->role === 'admin';
    }

    public static function canDelete(Model $record): bool
    {
        return Auth::user()?->role === 'admin';
    }

    public static function canDeleteAny(): bool
    {
        return Auth::user()?->role === 'admin';
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
