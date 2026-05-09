<?php

namespace App\Filament\Resources\RekapPotongans;

use App\Filament\Resources\RekapPotongans\Pages\CreateRekapPotongan;
use App\Filament\Resources\RekapPotongans\Pages\EditRekapPotongan;
use App\Filament\Resources\RekapPotongans\Pages\ListRekapPotongans;
use App\Filament\Resources\RekapPotongans\Schemas\RekapPotonganForm;
use App\Filament\Resources\RekapPotongans\Tables\RekapPotongansTable;
use App\Models\RekapPotongan;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class RekapPotonganResource extends Resource
{
    protected static ?string $model = RekapPotongan::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;

    protected static ?string $navigationLabel = 'Rekap Potongan';

    protected static string|\UnitEnum|null $navigationGroup = 'Keuangan & Laporan';

    protected static ?int $navigationSort = 7;

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
        return RekapPotonganForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return RekapPotongansTable::configure($table);
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
            'index' => ListRekapPotongans::route('/'),
            'create' => CreateRekapPotongan::route('/create'),
            'edit' => EditRekapPotongan::route('/{record}/edit'),
        ];
    }
}
