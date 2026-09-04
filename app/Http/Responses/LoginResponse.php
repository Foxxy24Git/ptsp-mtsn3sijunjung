<?php

namespace App\Http\Responses;

use Filament\Auth\Http\Responses\Contracts\LoginResponse as Contract;
use Filament\Facades\Filament;
use Illuminate\Http\RedirectResponse;
use Livewire\Features\SupportRedirects\Redirector;

class LoginResponse implements Contract
{
    /**
     * Bawaan Filament: redirect()->intended(Filament::getUrl()) — menuruti
     * url.intended apa pun yang tersimpan di sesi. Karena admin dan petugas
     * berbagi guard `web`, url.intended itu sering menunjuk panel yang lain
     * (mis. tamu sempat membuka /petugas, lalu login sebagai admin). Admin
     * pun dilempar ke /petugas, ditolak di sana, dan berakhir di form login
     * lagi — tampak seperti "login berhasil tapi malah error".
     *
     * Jadi url.intended hanya dituruti kalau memang berada di dalam panel
     * yang barusan dimasuki; selain itu langsung ke beranda panel tersebut.
     */
    public function toResponse($request): RedirectResponse|Redirector
    {
        $panelUrl = Filament::getUrl();
        $intended = session()->pull('url.intended');

        if (is_string($intended) && $this->isInsidePanel($intended, $panelUrl)) {
            return redirect()->to($intended);
        }

        return redirect()->to($panelUrl);
    }

    private function isInsidePanel(string $url, string $panelUrl): bool
    {
        $host = parse_url($url, PHP_URL_HOST);

        // Tolak URL ke host lain: url.intended tidak boleh jadi celah
        // open redirect ke luar aplikasi.
        if ($host !== null && $host !== parse_url($panelUrl, PHP_URL_HOST)) {
            return false;
        }

        $path = '/'.ltrim((string) parse_url($url, PHP_URL_PATH), '/');
        $prefix = '/'.ltrim((string) parse_url($panelUrl, PHP_URL_PATH), '/');

        if ($prefix === '/') {
            return true;
        }

        $prefix = rtrim($prefix, '/');

        return $path === $prefix || str_starts_with($path, $prefix.'/');
    }
}
