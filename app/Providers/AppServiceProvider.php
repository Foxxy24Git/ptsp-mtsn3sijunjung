<?php

namespace App\Providers;

use App\Http\Responses\LoginResponse;
use App\Models\Menu;
use App\Settings\GeneralSettings;
use Filament\Auth\Http\Responses\Contracts\LoginResponse as LoginResponseContract;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Cegah login melempar pengguna ke panel yang bukan miliknya karena
        // url.intended basi dari kunjungan sebelumnya. Lihat LoginResponse.
        $this->app->bind(LoginResponseContract::class, LoginResponse::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Identitas situs + menu navigasi dibaca dari DB dan dibagikan ke
        // seluruh view publik (layout, home, page, post, 404). Di-memoize per
        // request agar menu hanya di-query sekali walau beberapa view cocok.
        $shared = null;

        View::composer([
            'layouts.app', 'home', 'pages.show', 'posts.index', 'posts.show', 'galeri.index', 'errors.404',
        ], function ($view) use (&$shared) {
            if ($shared === null) {
                $settings = app(GeneralSettings::class);

                $shared = [
                    'settings' => $settings,
                    'logoUrl' => $settings->logo ? Storage::disk('public')->url($settings->logo) : null,
                    'navMenus' => Menu::query()
                        ->whereNull('parent_id')
                        ->with('children')
                        ->orderBy('sort_order')
                        ->get(),
                ];
            }

            $view->with($shared);
        });
    }
}
