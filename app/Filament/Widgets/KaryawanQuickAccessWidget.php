<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Auth;

class KaryawanQuickAccessWidget extends Widget
{
    protected string $view = 'filament.widgets.karyawan-quick-access-widget';

    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        return Auth::user()?->role === 'karyawan';
    }
}
