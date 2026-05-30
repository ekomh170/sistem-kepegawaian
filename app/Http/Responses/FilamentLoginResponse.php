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

        $targetUrl = match ($user?->role?->value) {
            'admin' => url('/admin'),
            'supervisor' => url('/supervisor'),
            'karyawan' => route('karyawan.beranda'),
            default => Filament::getUrl(),
        };

        if ($user?->isKaryawan()) {
            return redirect()->to($targetUrl)
                ->with('success', 'Selamat datang kembali, ' . $user->nama . '!');
        }

        $roleLabel = $user?->role?->label() ?? '';

        return redirect()->to($targetUrl)
            ->with('login_success', 'Selamat datang, ' . $roleLabel . ' ' . $user->nama . '!');
    }
}
