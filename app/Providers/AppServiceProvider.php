<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;

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
        if (config('app.env') === 'production' || env('FORCE_HTTPS', false)) {
            URL::forceScheme('https');
        }
        
        // مشاركة مفتاح Google Maps مع جميع صفحات Inertia
        \Inertia\Inertia::share([
            'googleMapsApiKey' => config('services.google.maps_api_key')
        ]);
    }
}
