<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        // ──────────────────────────────────────────────────────────────────
        // OMWENGA WIFI SAAS - CUSTOM COMMANDS
        // ──────────────────────────────────────────────────────────────────
        Commands\CheckExpiringSessions::class,
        Commands\DailyReconciliation::class,
        Commands\RouterHealthCheck::class,
    ];

    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // ──────────────────────────────────────────────────────────────────
        // SESSION MANAGEMENT (Every 5 minutes)
        // ──────────────────────────────────────────────────────────────────
        $schedule->command('sessions:check-expiring')
            ->everyFiveMinutes()
            ->timezone('Africa/Nairobi')
            ->withoutOverlapping()
            ->sendOutputTo(storage_path('logs/sessions-check.log'));

        // ──────────────────────────────────────────────────────────────────
        // PAYMENT RECONCILIATION (Daily at 2 AM EAT)
        // ──────────────────────────────────────────────────────────────────
        $schedule->command('payments:reconcile-daily')
            ->dailyAt('02:00')
            ->timezone('Africa/Nairobi')
            ->withoutOverlapping()
            ->sendOutputTo(storage_path('logs/payment-reconciliation.log'));

        // ──────────────────────────────────────────────────────────────────
        // ROUTER HEALTH CHECK (Every 5 minutes)
        // ──────────────────────────────────────────────────────────────────
        $schedule->command('routers:health-check')
            ->everyFiveMinutes()
            ->timezone('Africa/Nairobi')
            ->withoutOverlapping()
            ->sendOutputTo(storage_path('logs/router-health.log'));
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}