<?php

namespace App\Http\Controllers\Api\MikroTik;

use App\Http\Controllers\Controller;
use App\Models\UserSession;
use App\Models\Router;
use App\Services\MikroTik\SessionManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SessionController extends Controller
{
    public function __construct(
        protected SessionManager $sessionManager
    ) {}

    /**
     * List all sessions for tenant
     */
    public function index(Request $request): JsonResponse
    {
        $tenant = $request->user()->tenant;
        UserSession::expireStaleSessions($tenant?->id);
        
        $sessions = $tenant->userSessions()
            ->with(['router', 'package', 'payment'])
            ->latest()
            ->paginate(50);

        return response()->json([
            'success' => true,
            'data' => $sessions->items(),
            'pagination' => [
                'current_page' => $sessions->currentPage(),
                'per_page' => $sessions->perPage(),
                'total' => $sessions->total(),
                'last_page' => $sessions->lastPage(),
            ],
        ]);
    }

    /**
     * Get only active sessions
     */
    public function active(Request $request): JsonResponse
    {
        $tenant = $request->user()->tenant;
        UserSession::expireStaleSessions($tenant?->id);
        
        $sessions = $tenant->userSessions()
            ->active()
            ->with(['router', 'package'])
            ->orderBy('expires_at')
            ->get()
            ->map(function ($session) {
                return [
                    'id' => $session->id,
                    'username' => $session->username,
                    'phone' => $session->phone,
                    'mac_address' => $session->mac_address,
                    'ip_address' => $session->ip_address,
                    'router' => $session->router->name ?? null,
                    'package' => $session->package->name ?? null,
                    'status' => $session->status,
                    'started_at' => $session->started_at->toIso8601String(),
                    'expires_at' => $session->expires_at->toIso8601String(),
                    'time_remaining' => $session->time_remaining_formatted,
                    'seconds_remaining' => $session->time_remaining,
                    'data_used_mb' => $session->data_used_mb,
                    'data_limit_mb' => $session->data_limit_mb,
                    'is_in_grace_period' => $session->is_in_grace_period,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $sessions,
            'total_active' => $sessions->count(),
        ]);
    }

    /**
     * Get sessions expiring soon (within 30 minutes)
     */
    public function expiring(Request $request): JsonResponse
    {
        $tenant = $request->user()->tenant;
        UserSession::expireStaleSessions($tenant?->id);
        
        $sessions = $tenant->userSessions()
            ->active()
            ->expiringSoon(30)
            ->with(['router', 'package'])
            ->orderBy('expires_at')
            ->get()
            ->map(function ($session) {
                return [
                    'id' => $session->id,
                    'username' => $session->username,
                    'phone' => $session->phone,
                    'expires_at' => $session->expires_at->toIso8601String(),
                    'minutes_remaining' => $session->expires_at->diffInMinutes(now()),
                    'router' => $session->router->name ?? null,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $sessions,
            'total_expiring' => $sessions->count(),
        ]);
    }

    /**
     * Disconnect a user session
     */
    public function disconnect(Request $request, UserSession $session): JsonResponse
    {
        $user = $request->user();
        
        // Authorization check
        if (!$user->canManageRouter($session->router)) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized to manage this session',
            ], 403);
        }

        try {
            $result = $this->sessionManager->terminateSession($session, 'admin_request');

            if ($result) {
                return response()->json([
                    'success' => true,
                    'message' => "User {$session->username} has been disconnected",
                    'data' => [
                        'session_id' => $session->id,
                        'username' => $session->username,
                        'disconnected_at' => now()->toIso8601String(),
                        'reason' => 'admin_request',
                    ],
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Failed to disconnect session',
            ], 500);

        } catch (\Exception $e) {
            Log::channel('mikrotik')->error('Failed to disconnect session', [
                'session_id' => $session->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to disconnect session',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Bulk disconnect multiple sessions
     */
    public function bulkDisconnect(Request $request): JsonResponse
    {
        $request->validate([
            'session_ids' => 'required|array|min:1',
            'session_ids.*' => 'exists:user_sessions,id',
        ]);

        $user = $request->user();
        $disconnected = 0;
        $failed = 0;

        foreach ($request->session_ids as $sessionId) {
            $session = UserSession::find($sessionId);
            
            if ($session && $user->canManageRouter($session->router)) {
                if ($this->sessionManager->terminateSession($session, 'admin_bulk_disconnect')) {
                    $disconnected++;
                } else {
                    $failed++;
                }
            }
        }

        return response()->json([
            'success' => true,
            'message' => "Disconnected {$disconnected} sessions",
            'data' => [
                'disconnected' => $disconnected,
                'failed' => $failed,
                'total_requested' => count($request->session_ids),
            ],
        ]);
    }
}
