<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum StatusTugas: string implements HasLabel, HasColor
{
    case Pending = 'pending';
    case Disetujui = 'disetujui';
    case Ditolak = 'ditolak';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Menunggu',
            self::Disetujui => 'Diterima',
            self::Ditolak => 'Ditolak',
        };
    }

    public function getLabel(): string
    {
        return $this->label();
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Disetujui => 'success',
            self::Ditolak => 'danger',
            self::Pending => 'warning',
        };
    }
}
