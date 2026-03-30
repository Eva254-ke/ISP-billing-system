<?php

namespace App\Console\Commands;

use App\Models\Router;
use App\Services\MikroTik\MikroTikService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class RouterHealthCheck extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'routers:health-check';

    /**
     * The console command description.
     */
    protected $description = 'Check health status of all routers (online/offline, CPU, memory)';

    public function __construct(
        protected MikroTikService $mikrotikService
    ) {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        Log::channel('mikrotik')->info('Starting router health check');

        $this->info('🖥️ Starting router health check...');

        $routers = Router::all();
        $online = 0;
        $offline = 0;
        $warning = 0;

        $this->table(
            ['Router', 'IP Address', 'Status', 'CPU', 'Memory', 'Uptime'],
            $routers->map(function ($router) {
                return [
                    $router->name,
                    $router->ip_address,
                    $router->status,
                    $router->cpu_usage . '%',
                    $router->memory_usage . '%',
                    $router->uptime_formatted,
                ];
            })
        );

        $this->newLine();

        foreach ($routers as $router) {
            $this->line("Checking: {$router->name} ({$router->ip_address})...");

            try {
                $isOnline = $this->mikrotikService->pingRouter($router);

                if ($isOnline) {
                    // Get detailed system info
                    $systemInfo = $this->mikrotikService->getRouterSystemInfo($router);

                    $cpu = (int) ($systemInfo['cpu_load'] ?? 0);
                    $memory = (int) ($systemInfo['memory_usage'] ?? 0);

                    // Determine status based on resource usage
                    if ($cpu > 80 || $memory > 80) {
                        $router->update(['status' => 'warning']);
                        $warning++;

                        Log::channel('mikrotik')->warning('Router resource warning', [
                            'router' => $router->name,
                            'cpu' => $cpu,
                            'memory' => $memory,
                        ]);

                        $this->warn("   ⚠️ {$router->name} - WARNING (CPU: {$cpu}%, RAM: {$memory}%)");

                        // TODO: Alert admin if resources critical
                        // if ($cpu > 90 || $memory > 90) {
                        //     Notification::send(...);
                        // }

                    } else {
                        $router->update(['status' => 'online']);
                        $online++;

                        $this->info("   ✅ {$router->name} - Online (CPU: {$cpu}%, RAM: {$memory}%)");
                    }

                } else {
                    $router->update(['status' => 'offline']);
                    $offline++;

                    Log::channel('mikrotik')->error('Router offline', [
                        'router' => $router->name,
                        'ip' => $router->ip_address,
                        'model' => $router->model,
                    ]);

                    $this->error("   ❌ {$router->name} - OFFLINE");

                    // TODO: Alert admin immediately for offline router
                    // Notification::send(...);
                }

            } catch (\Exception $e) {
                $router->update(['status' => 'offline']);
                $offline++;

                Log::channel('mikrotik')->error('Router health check failed', [
                    'router' => $router->name,
                    'error' => $e->getMessage(),
                ]);

                $this->error("   ❌ {$router->name} - ERROR: {$e->getMessage()}");
            }
        }

        // ──────────────────────────────────────────────────────────────────
        // SUMMARY
        // ──────────────────────────────────────────────────────────────────
        Log::channel('mikrotik')->info('Router health check completed', [
            'total' => $routers->count(),
            'online' => $online,
            'offline' => $offline,
            'warning' => $warning,
        ]);

        $this->newLine();
        $this->info('✅ Health Check Summary:');
        $this->info("   Total routers: {$routers->count()}");
        $this->info("   🟢 Online: {$online}");
        $this->warn("   🟡 Warning: {$warning}");
        $this->error("   🔴 Offline: {$offline}");

        // Alert if any routers offline
        if ($offline > 0) {
            $this->newLine();
            $this->error('⚠️ Action required: {$offline} router(s) offline - check immediately!');
            // TODO: Send urgent alert to admin
        }

        return Command::SUCCESS;
    }
}