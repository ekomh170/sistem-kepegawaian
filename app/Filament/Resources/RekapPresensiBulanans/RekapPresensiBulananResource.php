<?php

namespace App\Filament\Resources\RekapPresensiBulanans;

use App\Filament\Resources\RekapPresensiBulanans\Pages\CreateRekapPresensiBulanan;
use App\Filament\Resources\RekapPresensiBulanans\Pages\EditRekapPresensiBulanan;
use App\Filament\Resources\RekapPresensiBulanans\Pages\ListRekapPresensiBulanans;
use App\Filament\Resources\RekapPresensiBulanans\Schemas\RekapPresensiBulananForm;
use App\Filament\Resources\RekapPresensiBulanans\Tables\RekapPresensiBulanansTable;
use App\Models\RekapPresensiBulanan;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class RekapPresensiBulananResource extends Resource
{
    protected static ?string $model = RekapPresensiBulanan::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;

    protected static ?string $navigationLabel = 'Rekap Presensi Bulanan';

    protected static string|\UnitEnum|null $navigationGroup = 'Laporan';

    protected static ?int $navigationSort = 7;

    public static function canViewAny(): bool
    {
        return in_array(Auth::user()?->role, ['admin', 'supervisor'], true);
    }

    public static function canCreate(): bool
    {
        return in_array(Auth::user()?->role, ['admin', 'supervisor'], true);
    }

    public static function canEdit(Model $record): bool
    {
        return in_array(Auth::user()?->role, ['admin', 'supervisor'], true);
    }

    public static function canDelete(Model $record): bool
    {
        return in_array(Auth::user()?->role, ['admin', 'supervisor'], true);
    }

    public static function canDeleteAny(): bool
    {
        return in_array(Auth::user()?->role, ['admin', 'supervisor'], true);
    }

    public static function form(Schema $schema): Schema
    {
        return RekapPresensiBulananForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return RekapPresensiBulanansTable::configure($table);
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
            'index' => ListRekapPresensiBulanans::route('/'),
            'create' => CreateRekapPresensiBulanan::route('/create'),
            'edit' => EditRekapPresensiBulanan::route('/{record}/edit'),
        ];
    }
}
