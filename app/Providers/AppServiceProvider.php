<?php

namespace App\Providers;

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
        // Bypass SSL cURL error 77 di localhost (Laragon/XAMPP)
        \Illuminate\Support\Facades\Http::globalOptions([
            'verify' => false,
        ]);
    }
}
