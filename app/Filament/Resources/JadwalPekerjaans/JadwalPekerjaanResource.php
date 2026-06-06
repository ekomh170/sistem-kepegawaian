<?php

namespace App\Filament\Resources\JadwalPekerjaans;

use App\Filament\Resources\JadwalPekerjaans\Pages\CreateJadwal;
use App\Filament\Resources\JadwalPekerjaans\Pages\EditJadwal;
use App\Filament\Resources\JadwalPekerjaans\Pages\KalenderJadwal;
use App\Filament\Resources\JadwalPekerjaans\Pages\ListJadwals;
use App\Filament\Resources\JadwalPekerjaans\Schemas\JadwalForm;
use App\Filament\Resources\JadwalPekerjaans\Tables\JadwalsTable;
use App\Models\JadwalPekerjaan;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class JadwalPekerjaanResource extends Resource
{
    protected static ?string $model = JadwalPekerjaan::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDays;

    protected static ?string $navigationLabel = 'Jadwal Pekerjaan';

    protected static ?string $modelLabel = 'Jadwal Pekerjaan';

    protected static ?string $pluralModelLabel = 'Jadwal Pekerjaan';

    protected static string|\UnitEnum|null $navigationGroup = 'Operasional';

    protected static ?int $navigationSort = 3;

    protected static ?string $recordTitleAttribute = 'tanggal_kerja';

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
        return JadwalForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return JadwalsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListJadwals::route('/'),
            'kalender' => KalenderJadwal::route('/kalender'),
            'create' => CreateJadwal::route('/create'),
            'edit' => EditJadwal::route('/{record}/edit'),
        ];
    }
}
