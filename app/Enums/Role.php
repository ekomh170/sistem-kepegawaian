<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum Role: string implements HasLabel, HasColor
{
    case Karyawan = 'karyawan';
    case Admin = 'admin';
    case Supervisor = 'supervisor';

    public function label(): string
    {
        return match ($this) {
            self::Karyawan => 'Karyawan',
            self::Admin => 'Admin',
            self::Supervisor => 'Supervisor',
        };
    }

    public function getLabel(): string
    {
        return $this->label();
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Admin => 'danger',
            self::Supervisor => 'warning',
            self::Karyawan => 'success',
        };
    }

    /**
     * @return array<string, string> value => label
     */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $role) => [$role->value => $role->label()])
            ->all();
    }
}
