<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum StatusPresensi: string implements HasLabel, HasColor
{
    case Hadir = 'hadir';
    case Terlambat = 'terlambat';
    case TidakHadir = 'tidak_hadir';
    case Izin = 'izin';

    public function label(): string
    {
        return match ($this) {
            self::Hadir => 'Hadir',
            self::Terlambat => 'Terlambat',
            self::TidakHadir => 'Alpa',
            self::Izin => 'Izin',
        };
    }

    public function getLabel(): string
    {
        return $this->label();
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Hadir => 'success',
            self::Terlambat => 'warning',
            self::TidakHadir => 'danger',
            self::Izin => 'info',
        };
    }
}
