<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum StatusJadwal: string implements HasLabel, HasColor
{
    case Aktif = 'aktif';
    case Dibatalkan = 'dibatalkan';

    public function label(): string
    {
        return match ($this) {
            self::Aktif => 'Aktif',
            self::Dibatalkan => 'Dibatalkan',
        };
    }

    public function getLabel(): string
    {
        return $this->label();
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Aktif => 'success',
            self::Dibatalkan => 'danger',
        };
    }
}
