<?php

namespace App\Providers;

use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $appUrl = (string) config('app.url', '');

        if (app()->runningInConsole()) {
            if (str_starts_with($appUrl, 'https://')) {
                URL::forceScheme('https');
                URL::forceRootUrl($appUrl);
            }

            return;
        }

        $request = request();

        if (str_starts_with($appUrl, 'https://') || $request->isSecure()) {
            URL::forceScheme('https');
        }

        if (str_starts_with($appUrl, 'https://')) {
            URL::forceRootUrl($appUrl);
        }

        $host = $request->getHost();
        $isLocalHost = $host === 'localhost'
            || $host === '127.0.0.1'
            || $host === '::1'
            || str_ends_with($host, '.test');

        if (! $isLocalHost) {
            // Disable stale Vite hot reload URLs for public tunnels (Herd Share).
            Vite::useHotFile(storage_path('framework/vite.share.hot'));
        }
    }
}
