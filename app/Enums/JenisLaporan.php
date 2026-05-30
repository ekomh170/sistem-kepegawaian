<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum JenisLaporan: string implements HasLabel, HasColor
{
    case Harian = 'Harian';
    case Mingguan = 'Mingguan';
    case Bulanan = 'Bulanan';
    case Tahunan = 'Tahunan';

    public function label(): string
    {
        return $this->value;
    }

    public function getLabel(): string
    {
        return $this->label();
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Harian => 'info',
            self::Mingguan => 'primary',
            self::Bulanan => 'success',
            self::Tahunan => 'warning',
        };
    }
}
