<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\PosApiService;

class PosServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(PosApiService::class, function ($app) {
            return new PosApiService();
        });
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }
}