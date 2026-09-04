<?php

namespace App\Http\Middleware;

use Closure;
use Filament\Facades\Filament;
use Filament\Http\Middleware\Authenticate as FilamentAuthenticate;
use Filament\Models\Contracts\FilamentUser;

class AuthenticatePanel extends FilamentAuthenticate
{
    /**
     * Admin dan petugas berbagi guard `web`, jadi sesi salah satu role
     * tetap "terhitung login" saat membuka panel milik role lain. Bawaan
     * Filament melempar 403 buntu di situ — pengguna tidak punya jalan
     * keluar selain menebak URL /login-nya sendiri.
     *
     * Batas aksesnya tidak dilonggarkan: canAccessPanel() tetap penentu,
     * dan panel tujuan tetap tidak pernah dirender. Yang berubah hanya
     * bentuk penolakannya — diarahkan ke form login panel itu supaya bisa
     * langsung berganti akun.
     */
    public function handle($request, Closure $next, ...$guards)
    {
        $panel = Filament::getCurrentOrDefaultPanel();
        $user = Filament::auth()->user();

        if (
            $user instanceof FilamentUser
            && ! $user->canAccessPanel($panel)
            && $panel->hasLogin()
        ) {
            return redirect()->to($panel->getLoginUrl());
        }

        return parent::handle($request, $next, ...$guards);
    }
}
