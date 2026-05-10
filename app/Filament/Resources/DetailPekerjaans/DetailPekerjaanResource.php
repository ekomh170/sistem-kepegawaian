<?php

namespace App\Filament\Resources\DetailPekerjaans;

use App\Filament\Resources\DetailPekerjaans\Pages\CreateDetailPekerjaan;
use App\Filament\Resources\DetailPekerjaans\Pages\EditDetailPekerjaan;
use App\Filament\Resources\DetailPekerjaans\Pages\ListDetailPekerjaans;
use App\Filament\Resources\DetailPekerjaans\Pages\ViewDetailPekerjaan;
use App\Filament\Resources\DetailPekerjaans\Schemas\DetailPekerjaanForm;
use App\Filament\Resources\DetailPekerjaans\Schemas\DetailPekerjaanInfolist;
use App\Filament\Resources\DetailPekerjaans\Tables\DetailPekerjaansTable;
use App\Models\DetailPekerjaan;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class DetailPekerjaanResource extends Resource
{
    protected static ?string $model = DetailPekerjaan::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $navigationLabel = 'Detail Pekerjaan';

    protected static string|\UnitEnum|null $navigationGroup = 'Operasional';

    protected static ?int $navigationSort = 4;

    protected static ?string $recordTitleAttribute = 'nama_lokasi';

    public static function canViewAny(): bool
    {
        return in_array(Auth::user()?->role, ['admin', 'supervisor'], true);
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
        return DetailPekerjaanForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return DetailPekerjaanInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return DetailPekerjaansTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDetailPekerjaans::route('/'),
            'create' => CreateDetailPekerjaan::route('/create'),
            'view' => ViewDetailPekerjaan::route('/{record}'),
            'edit' => EditDetailPekerjaan::route('/{record}/edit'),
        ];
    }
}
