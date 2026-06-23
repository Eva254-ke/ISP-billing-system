<?php

namespace App\Http\Controllers\Api\MikroTik;

use App\Http\Controllers\Controller;
use App\Models\Router;
use App\Services\MikroTik\MikroTikService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class RouterController extends Controller
{
    public function __construct(
        protected MikroTikService $mikrotikService
    ) {}

    /**
     * List all routers for authenticated tenant
     */
    public function index(Request $request): JsonResponse
    {
        $tenant = $request->user()->tenant;
        
        $routers = $tenant->routers()
            ->select(['id', 'name', 'model', 'ip_address', 'status', 'last_seen_at', 'cpu_usage', 'memory_usage', 'active_sessions'])
            ->withCount('userSessions')
            ->get()
            ->map(function ($router) {
                return [
                    'id' => $router->id,
                    'name' => $router->name,
                    'model' => $router->model,
                    'ip_address' => $router->ip_address,
                    'status' => $router->status,
                    'is_online' => $router->status === 'online',
                    'last_seen' => $router->last_seen_at?->diffForHumans(),
                    'cpu_usage' => $router->cpu_usage,
                    'memory_usage' => $router->memory_usage,
                    'active_sessions' => $router->active_sessions,
                    'uptime' => $router->uptime_formatted,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $routers,
            'total' => $routers->count(),
        ]);
    }

    /**
     * Ping/test router connectivity
     */
    public function ping(Request $request, Router $router): JsonResponse
    {
        // Authorization check
        if (!$request->user()->canManageRouter($router)) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized to manage this router',
            ], 403);
        }

        try {
            $isOnline = $this->mikrotikService->pingRouter($router);
            
            if ($isOnline) {
                // Get system info
                $systemInfo = $this->mikrotikService->getRouterSystemInfo($router);
                
                return response()->json([
                    'success' => true,
                    'message' => 'Router is online',
                    'data' => [
                        'is_online' => true,
                        'cpu_load' => $systemInfo['cpu_load'] ?? null,
                        'memory_usage' => $systemInfo['memory_usage'] ?? null,
                        'uptime' => $systemInfo['uptime'] ?? null,
                        'version' => $systemInfo['version'] ?? null,
                        'response_time_ms' => rand(5, 50), // Mock for now
                    ],
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Router is offline or unreachable',
                'data' => ['is_online' => false],
            ], 503);

        } catch (\Exception $e) {
            Log::channel('mikrotik')->error('Router ping failed', [
                'router_id' => $router->id,
                'router_name' => $router->name,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to connect to router',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Get active sessions on a router
     */
    public function sessions(Request $request, Router $router): JsonResponse
    {
        // Authorization check
        if (!$request->user()->canManageRouter($router)) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized to manage this router',
            ], 403);
        }

        try {
            $activeSessions = $this->mikrotikService->getActiveSessions($router);

            return response()->json([
                'success' => true,
                'data' => [
                    'router' => [
                        'id' => $router->id,
                        'name' => $router->name,
                        'ip_address' => $router->ip_address,
                    ],
                    'sessions' => $activeSessions,
                    'total_active' => count($activeSessions),
                ],
            ]);

        } catch (\Exception $e) {
            Log::channel('mikrotik')->error('Failed to get router sessions', [
                'router_id' => $router->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch sessions from router',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }
}