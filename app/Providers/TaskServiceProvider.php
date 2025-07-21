<?php
// app/Providers/TaskServiceProvider.php

namespace App\Providers;

use App\Services\TaskService;
use App\Repositories\TaskRepository;
use Illuminate\Support\ServiceProvider;

class TaskServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        // Register Repository
        $this->app->singleton(TaskRepository::class, function ($app) {
            return new TaskRepository();
        });

        // Register Service
        $this->app->singleton(TaskService::class, function ($app) {
            return new TaskService($app->make(TaskRepository::class));
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