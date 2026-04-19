<?php

namespace App\Services\MikroTik;

use App\Models\Router;
use App\Models\UserSession;
use App\Models\Package;
use Illuminate\Support\Facades\Log;

class SessionManager
{
    public function __construct(
        protected MikroTikService $mikrotikService
    ) {}

    /**
     * Activate a new user session (after payment/voucher redemption)
     */
    public function activateSession(UserSession $session, Package $package): array
    {
        $router = $session->router;
        
        // Calculate duration in minutes
        $durationMinutes = $package->duration_in_minutes;

        // Non-RADIUS mode: make sure a local hotspot user exists before triggering login.
        if (!(bool) config('radius.enabled', false)) {
            $userProvisioned = $router->addHotspotUser(
                $session->username,
                $session->username,
                $package
            );

            if (!$userProvisioned) {
                Log::channel('mikrotik')->warning('Hotspot user provisioning did not complete before login attempt', [
                    'session_id' => $session->id,
                    'router' => $router->name,
                    'username' => $session->username,
                ]);
            }
        }
        
        // Create session on MikroTik
        $result = $this->mikrotikService->createHotspotSession(
            $router,
            $session->username,
            $session->username, // Password = username for simplicity
            $durationMinutes,
            $session->mac_address,
            $session->ip_address
        );
        
        if (!is_array($result) || !($result['success'] ?? false)) {
            // Refresh router connectivity state for more accurate retry decisions.
            $this->mikrotikService->pingRouter($router);
            $router = $router->fresh();

            $error = is_array($result)
                ? (string) ($result['error'] ?? 'Router activation failed.')
                : 'No response from router during activation.';

            $queued = (string) ($router?->status ?? '') === Router::STATUS_OFFLINE;

            if ((string) ($router?->status ?? '') === Router::STATUS_WARNING) {
                $error = 'Router reachable but MikroTik API login/command failed. Verify API username/password and permissions.';
            }

            Log::channel('mikrotik')->warning('Session activation failed', [
                'session_id' => $session->id,
                'router' => $router?->name,
                'router_status' => $router?->status,
                'queued' => $queued,
                'error' => $error,
            ]);

            return [
                'success' => false,
                'error' => $error,
                'queued' => $queued,
            ];
        }
        
        // Update session record
        $session->update([
            'status' => 'active',
            'started_at' => now(),
            'expires_at' => $result['expires_at'],
            'grace_period_active' => false,
            'last_synced_at' => now(),
        ]);
        
        Log::channel('mikrotik')->info('Session activated', [
            'session_id' => $session->id,
            'username' => $session->username,
            'router' => $router->name,
            'expires_at' => $result['expires_at']->toIso8601String(),
        ]);
        
        return [
            'success' => true,
            'session_id' => $session->id,
            'expires_at' => $result['expires_at'],
        ];
    }

    /**
     * Check and handle session expiry (with grace period)
     */
    public function checkAndHandleExpiry(UserSession $session): void
    {
        // Don't process already terminated sessions
        if (!in_array($session->status, ['active', 'idle'])) {
            return;
        }
        
        // Check if session should be disconnected
        if ($session->shouldDisconnect()) {
            $this->terminateSession($session, 'expired');
            return;
        }
        
        // Check if session is expiring soon (send warning)
        if ($session->expires_at->diffInMinutes(now()) <= 10 && !$session->grace_period_active) {
            $this->sendExpiryWarning($session);
        }
        
        // Activate grace period if expired but within grace window
        if ($session->expires_at->isPast() && !$session->grace_period_active) {
            $session->activateGracePeriod();
            
            Log::channel('mikrotik')->info('Grace period activated', [
                'session_id' => $session->id,
                'username' => $session->username,
                'grace_ends_at' => $session->grace_period_ends_at->toIso8601String(),
            ]);
        }
        
        // Sync usage data
        $this->mikrotikService->syncSessionUsage($session);
    }

    /**
     * Terminate a session (user request, expiry, admin action)
     */
    public function terminateSession(UserSession $session, string $reason): bool
    {
        $router = $session->router;
        
        // Disconnect on MikroTik
        $disconnected = $this->mikrotikService->disconnectSession(
            $router,
            $session->username,
            'username'
        );
        
        if (!$disconnected) {
            Log::channel('mikrotik')->warning('Failed to disconnect session on router', [
                'session_id' => $session->id,
                'username' => $session->username,
                'router' => $router->name,
            ]);
        }
        
        // Update database
        $session->markTerminated($reason);
        
        Log::channel('mikrotik')->info('Session terminated', [
            'session_id' => $session->id,
            'username' => $session->username,
            'reason' => $reason,
            'router' => $router->name,
        ]);
        
        return true;
    }

    /**
     * Send warning SMS when session is about to expire
     */
    private function sendExpiryWarning(UserSession $session): void
    {
        // Integrate with SMS service (Africa's Talking, Twilio, etc.)
        // For now, just log
        
        $minutesRemaining = $session->expires_at->diffInMinutes(now());
        
        Log::channel('notification')->info('Session expiry warning', [
            'session_id' => $session->id,
            'phone' => $session->phone,
            'minutes_remaining' => $minutesRemaining,
        ]);
        
        // TODO: Send SMS via your SMS service
        // SmsService::send($session->phone, "Your CloudBridge WiFi session expires in {$minutesRemaining} minutes. Renew now to stay connected!");
    }

    /**
     * Bulk sync all active sessions for a router
     */
    public function syncRouterSessions(Router $router): int
    {
        $activeSessions = $this->mikrotikService->getActiveSessions($router);
        $synced = 0;
        
        foreach ($activeSessions as $routerSession) {
            $session = UserSession::where('router_id', $router->id)
                ->where('username', $routerSession['username'])
                ->where('status', 'active')
                ->first();
            
            if ($session) {
                $this->mikrotikService->syncSessionUsage($session);
                $synced++;
            }
        }
        
        Log::channel('mikrotik')->info('Router sessions synced', [
            'router' => $router->name,
            'total_on_router' => count($activeSessions),
            'synced' => $synced,
        ]);
        
        return $synced;
    }
}
