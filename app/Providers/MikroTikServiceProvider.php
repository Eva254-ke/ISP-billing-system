<?php

namespace App\Providers;

use App\Services\MikroTik\MikroTikService;
use App\Services\MikroTik\SessionManager;
use Illuminate\Support\ServiceProvider;

class MikroTikServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(MikroTikService::class, function ($app) {
            return new MikroTikService();
        });
        
        $this->app->singleton(SessionManager::class, function ($app) {
            return new SessionManager(
                $app->make(MikroTikService::class)
            );
        });
    }

    public function boot(): void
    {
        // Optional: Register console commands for session management
        // if ($this->app->runningInConsole()) {
        //     $this->commands([
        //         \App\Console\Commands\SyncRouterSessions::class,
        //         \App\Console\Commands\CheckExpiringSessions::class,
        //     ]);
        // }
    }
}