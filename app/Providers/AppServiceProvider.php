<?php

namespace App\Providers;

use Pgvector\Laravel\Schema;
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
        Schema::register();

        try {
            $socialite = $this->app->make(\Laravel\Socialite\Contracts\Factory::class);
            $socialite->extend('sipetra', function ($app) use ($socialite) {
                $config = $app['config']['services.sipetra'];
                return $socialite->buildProvider(\App\Providers\SipetraSocialiteProvider::class, $config);
            });
        } catch (\Exception $e) {
            // Ignore during initial setup if Socialite isn't fully loaded
        }
    }
}
