<?php

namespace App\Filament\Auth;

use Filament\Auth\Pages\Login as BaseLogin;
use Filament\Facades\Filament;
use Filament\Models\Contracts\FilamentUser;

class Login extends BaseLogin
{
    /**
     * Admin dan petugas berbagi guard `web`. Kalau user yang sedang login
     * di guard itu bukan pemilik sah panel ini (mis. admin membuka
     * /petugas/login), mount() bawaan Filament tetap redirect ke dashboard
     * panel ini — yang lalu ditolak 403 oleh middleware Authenticate karena
     * canAccessPanel() gagal. Sesi salah-panel itu di-logout dulu di sini
     * supaya form login tetap tampil, bukan malah 403.
     */
    public function mount(): void
    {
        $user = Filament::auth()->user();

        if ($user instanceof FilamentUser && ! $user->canAccessPanel(Filament::getCurrentOrDefaultPanel())) {
            Filament::auth()->logout();

            request()->session()->invalidate();
            request()->session()->regenerateToken();
        }

        parent::mount();
    }
}
