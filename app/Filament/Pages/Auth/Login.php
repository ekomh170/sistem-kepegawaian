<?php

namespace App\Filament\Pages\Auth;

use App\Models\Setting;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Component;
use Illuminate\Contracts\Support\Htmlable;

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

    public function getHeading(): string | Htmlable | null
    {
        return Setting::get('nama_perusahaan', config('app.name'));
    }

    public function getSubheading(): string | Htmlable | null
    {
        return 'Masuk ke akun Anda';
    }

    protected function getEmailFormComponent(): Component
    {
        return TextInput::make('email')
            ->label('Email')
            ->email()
            ->required()
            ->autocomplete()
            ->autofocus();
    }

    protected function getPasswordFormComponent(): Component
    {
        return TextInput::make('password')
            ->label('Kata Sandi')
            ->password()
            ->revealable(filament()->arePasswordsRevealable())
            ->autocomplete('current-password')
            ->required();
    }

    protected function getRememberFormComponent(): Component
    {
        return Checkbox::make('remember')
            ->label('Ingat saya');
    }
}
