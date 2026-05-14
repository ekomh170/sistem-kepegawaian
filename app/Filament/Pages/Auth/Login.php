<?php

namespace App\Filament\Pages\Auth;

use Filament\Notifications\Notification;

class Login extends \Filament\Auth\Pages\Login
{
    public function mount(): void
    {
        parent::mount();

        if (session()->has('logout_success')) {
            Notification::make()
                ->title(session('logout_success'))
                ->info()
                ->seconds(5)
                ->send();

            session()->forget('logout_success');
        }
    }
}
