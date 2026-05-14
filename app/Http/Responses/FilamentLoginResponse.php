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

        if ($user?->role === 'karyawan') {
            return redirect()->to($targetUrl)
                ->with('success', 'Selamat datang kembali, ' . $user->nama . '!');
        }

        $roleLabel = match ($user?->role) {
            'admin' => 'Admin',
            'supervisor' => 'Supervisor',
            default => ucfirst($user?->role ?? ''),
        };

        return redirect()->to($targetUrl)
            ->with('login_success', 'Selamat datang, ' . $roleLabel . ' ' . $user->nama . '!');
    }
}
