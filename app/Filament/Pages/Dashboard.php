<?php

namespace App\Filament\Pages;

use Filament\Notifications\Notification;

class Dashboard extends \Filament\Pages\Dashboard
{
    public function mount(): void
    {
        if (session()->has('login_success')) {
            Notification::make()
                ->title(session('login_success'))
                ->success()
                ->seconds(5)
                ->send();

            session()->forget('login_success');
        }
    }
}
