<?php

namespace App\Filament\Auth;

use Filament\Auth\Pages\Login as BaseLogin;
use Filament\Facades\Filament;
use Filament\Models\Contracts\FilamentUser;
use Illuminate\Contracts\Support\Htmlable;

class Login extends BaseLogin
{
    /**
     * Admin dan petugas berbagi guard `web`. Kalau user yang sedang login
     * di guard itu bukan pemilik sah panel ini (mis. petugas membuka
     * /admin/login), mount() bawaan Filament menganggapnya "sudah login"
     * lalu redirect ke dashboard panel ini — padahal di sana dia ditolak,
     * dan dilempar balik ke form login ini lagi (loop).
     *
     * Sesi salah-panel itu diakhiri di sini, bukan sekadar diabaikan.
     * Sempat dicoba membiarkannya hidup supaya salah klik tidak bikin
     * kehilangan sesi, tapi itu justru merusak login berikutnya:
     * `password_hash_web` di sesi masih milik user lama, sehingga
     * middleware AuthenticateSession menganggapnya sesi bajakan di request
     * setelahnya lalu melogout semuanya — login tampak gagal tanpa pesan.
     * Satu guard hanya boleh memegang satu identitas.
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

    public function getTitle(): string|Htmlable
    {
        return match (Filament::getCurrentOrDefaultPanel()->getId()) {
            'admin' => 'Login Administrator',
            'petugas' => 'Login Petugas',
            default => parent::getTitle(),
        };
    }

    public function getHeading(): string|Htmlable|null
    {
        if (filled($this->userUndertakingMultiFactorAuthentication)) {
            return parent::getHeading();
        }

        return match (Filament::getCurrentOrDefaultPanel()->getId()) {
            'admin' => 'Masuk sebagai Administrator',
            'petugas' => 'Masuk sebagai Petugas',
            default => parent::getHeading(),
        };
    }
}
