<?php

namespace App\Filament\Resources\Supervisors;

use App\Filament\Resources\Supervisors\Pages\CreateSupervisor;
use App\Filament\Resources\Supervisors\Pages\EditSupervisor;
use App\Filament\Resources\Supervisors\Pages\ListSupervisors;
use App\Models\Supervisor;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class SupervisorResource extends Resource
{
    protected static ?string $model = Supervisor::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserCircle;

    protected static ?string $navigationLabel = 'Data Supervisor';

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    public static function canViewAny(): bool
    {
        return false;
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSupervisors::route('/'),
            'create' => CreateSupervisor::route('/create'),
            'edit' => EditSupervisor::route('/{record}/edit'),
        ];
    }
}
