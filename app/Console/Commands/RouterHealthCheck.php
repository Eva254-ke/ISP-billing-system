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
        $this->info('Starting router health check...');

        $routers = Router::all();
        $online = 0;
        $offline = 0;
        $warning = 0;

        $this->table(
            ['Router', 'IP Address', 'Status', 'CPU', 'Memory', 'Uptime'],
            $routers->map(function (Router $router) {
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
                $router->refresh();

                if ($isOnline) {
                    $systemInfo = $this->mikrotikService->getRouterSystemInfo($router);
                    $cpu = (int) ($systemInfo['cpu_load'] ?? 0);
                    $memory = (int) ($systemInfo['memory_usage'] ?? 0);

                    if ($cpu > 80 || $memory > 80) {
                        $router->update(['status' => Router::STATUS_WARNING]);
                        $warning++;

                        Log::channel('mikrotik')->warning('Router resource warning', [
                            'router' => $router->name,
                            'cpu' => $cpu,
                            'memory' => $memory,
                        ]);

                        $this->warn("   WARNING {$router->name} (CPU: {$cpu}%, RAM: {$memory}%)");
                    } else {
                        $router->update(['status' => Router::STATUS_ONLINE]);
                        $online++;

                        $this->info("   OK {$router->name} (CPU: {$cpu}%, RAM: {$memory}%)");
                    }

                    continue;
                }

                if ($router->status === Router::STATUS_WARNING) {
                    $warning++;
                    $diagnostics = $this->mikrotikService->getConnectivityDiagnostics($router);

                    Log::channel('mikrotik')->warning('Router reachable but MikroTik API check failed', [
                        'router' => $router->name,
                        'ip' => $router->ip_address,
                        'diagnostics' => $diagnostics,
                    ]);

                    $message = (string) ($diagnostics['message'] ?? 'Router reachable but API check failed');
                    $this->warn("   WARNING {$router->name} ({$message})");
                    continue;
                }

                $router->update(['status' => Router::STATUS_OFFLINE]);
                $offline++;

                Log::channel('mikrotik')->error('Router offline', [
                    'router' => $router->name,
                    'ip' => $router->ip_address,
                    'model' => $router->model,
                ]);

                $this->error("   OFFLINE {$router->name}");
            } catch (\Exception $e) {
                $router->update(['status' => Router::STATUS_OFFLINE]);
                $offline++;

                Log::channel('mikrotik')->error('Router health check failed', [
                    'router' => $router->name,
                    'error' => $e->getMessage(),
                ]);

                $this->error("   ERROR {$router->name}: {$e->getMessage()}");
            }
        }

        Log::channel('mikrotik')->info('Router health check completed', [
            'total' => $routers->count(),
            'online' => $online,
            'offline' => $offline,
            'warning' => $warning,
        ]);

        $this->newLine();
        $this->info('Health Check Summary:');
        $this->info("   Total routers: {$routers->count()}");
        $this->info("   Online: {$online}");
        $this->warn("   Warning: {$warning}");
        $this->error("   Offline: {$offline}");

        if ($offline > 0) {
            $this->newLine();
            $this->error("Action required: {$offline} router(s) offline - check immediately.");
        }

        return Command::SUCCESS;
    }
}
