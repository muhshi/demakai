<?php

namespace App\Providers;

use App\Http\Responses\SsoLogoutResponse;
use Filament\Auth\Http\Responses\Contracts\LogoutResponse;
use Illuminate\Support\ServiceProvider;
use Laravel\Socialite\Contracts\Factory;
use Pgvector\Laravel\Schema;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        if (interface_exists(LogoutResponse::class)) {
            $this->app->singleton(LogoutResponse::class, SsoLogoutResponse::class);
        }
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Schema::register();

        try {
            $socialite = $this->app->make(Factory::class);
            $socialite->extend('sipetra', function ($app) use ($socialite) {
                $config = $app['config']['services.sipetra'];

                return $socialite->buildProvider(SipetraSocialiteProvider::class, $config);
            });
        } catch (\Exception $e) {
            // Ignore during initial setup if Socialite isn't fully loaded
        }
    }
}
