<?php

namespace App\Http\Responses;

use Filament\Auth\Http\Responses\Contracts\LoginResponse as LoginResponseContract;
use Filament\Facades\Filament;
use Illuminate\Http\RedirectResponse;
use Livewire\Features\SupportRedirects\Redirector;

class FilamentLoginResponse implements LoginResponseContract
{
    public function toResponse($request): RedirectResponse | Redirector
    {
        $user = Filament::auth()->user();

        $targetUrl = match ($user?->role) {
            'admin' => url('/admin'),
            'supervisor' => url('/supervisor'),
            'karyawan' => route('karyawan.beranda'),
            default => Filament::getUrl(),
        };

        return redirect()->to($targetUrl);
    }
}
