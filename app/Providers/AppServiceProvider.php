<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Schema;
use App\Models\Setting;

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
        // Force HTTPS untuk mobile camera access
        if (app()->environment('local')) {
            URL::forceScheme('http');
        }

        if (! Schema::hasTable('settings')) {
            return;
        }

        $timezone = Setting::getString('app.timezone');
        if (! $timezone) {
            return;
        }

        config(['app.timezone' => $timezone]);
        date_default_timezone_set($timezone);
    }
}
