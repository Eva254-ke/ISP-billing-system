<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

/*
|--------------------------------------------------------------------------
| Console Routes - Omwenga WiFi SaaS
|--------------------------------------------------------------------------
|
| This file is where you may define all of your Closure based console
| commands. Each Closure is bound to a command instance allowing a
| simple approach to interacting with each command's IO methods.
|
*/

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// ──────────────────────────────────────────────────────────────────────────
// SCHEDULED TASKS (For Production)
// ──────────────────────────────────────────────────────────────────────────

// Sync router sessions every 5 minutes
// Schedule::call(function () {
//     // Sync all active sessions with routers
// })->everyFiveMinutes();

// Check expiring sessions every minute
// Schedule::call(function () {
//     // Check and handle expiring sessions
// })->everyMinute();

// Daily reconciliation at 2 AM
// Schedule::call(function () {
//     // Run payment reconciliation
// })->dailyAt('02:00');

// Router health check every 5 minutes
// Schedule::call(function () {
//     // Ping all routers and update status
// })->everyFiveMinutes();