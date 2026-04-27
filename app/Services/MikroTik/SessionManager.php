<?php

namespace App\Services\MikroTik;

use App\Models\Router;
use App\Models\UserSession;
use App\Models\Package;
use App\Services\Radius\FreeRadiusProvisioningService;
use App\Services\Radius\RadiusDisconnectService;
use Illuminate\Support\Facades\Log;

class SessionManager
{
    public function __construct(
        protected MikroTikService $mikrotikService,
        protected ?RadiusDisconnectService $radiusDisconnectService = null,
        protected ?FreeRadiusProvisioningService $radiusProvisioningService = null
    ) {}

    /**
     * Activate a new user session (after payment/voucher redemption)
     */
    public function activateSession(UserSession $session, Package $package): array
    {
        $router = $this->resolveActivationRouter($session);
        if (!$router) {
            Log::channel('mikrotik')->warning('Session activation skipped: no router resolved for session', [
                'session_id' => $session->id,
                'tenant_id' => $session->tenant_id,
                'router_id' => $session->router_id,
            ]);

            return [
                'success' => false,
                'error' => 'No router available for activation.',
                'queued' => true,
            ];
        }
        
        // Calculate duration in minutes
        $durationMinutes = $package->duration_in_minutes;
        $radiusEnabled = (bool) config('radius.enabled', false);

        if ($radiusEnabled && empty($session->mac_address) && empty($session->ip_address)) {
            Log::channel('mikrotik')->warning('RADIUS activation is missing client MAC/IP context', [
                'session_id' => $session->id,
                'tenant_id' => $session->tenant_id,
                'router_id' => $router->id,
                'username' => $session->username,
            ]);
        }

        // Non-RADIUS mode: make sure a local hotspot user exists before triggering login.
        if (!$radiusEnabled) {
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
            $diagnostics = [];

            try {
                $this->mikrotikService->pingRouter($router);
                $router = $router->fresh();
            } catch (\Throwable $refreshError) {
                Log::channel('mikrotik')->warning('Router connectivity refresh failed after activation error', [
                    'session_id' => $session->id,
                    'tenant_id' => $session->tenant_id,
                    'router_id' => $router?->id,
                    'router' => $router?->name,
                    'error' => $refreshError->getMessage(),
                ]);
            }

            $error = is_array($result)
                ? (string) ($result['error'] ?? 'Router activation failed.')
                : 'No response from router during activation.';

            $queued = (string) ($router?->status ?? '') === Router::STATUS_OFFLINE;

            if ((string) ($router?->status ?? '') === Router::STATUS_WARNING) {
                $diagnostics = $router
                    ? $this->mikrotikService->getConnectivityDiagnostics($router)
                    : [];

                $error = trim((string) ($diagnostics['message'] ?? ''));
                if ($error === '') {
                    $error = 'Router reachable but MikroTik API login/command failed. Verify API username/password and permissions.';
                }
            }

            Log::channel('mikrotik')->warning('Session activation failed', [
                'session_id' => $session->id,
                'tenant_id' => $session->tenant_id,
                'router_id' => $router?->id,
                'router' => $router?->name,
                'router_status' => $router?->status,
                'username' => $session->username,
                'radius_enabled' => $radiusEnabled,
                'mac_address' => $session->mac_address,
                'ip_address' => $session->ip_address,
                'queued' => $queued,
                'error' => $error,
                'missing_client_context' => (bool) (is_array($result) && ($result['missing_client_context'] ?? false)),
                'connectivity_diagnostics' => $diagnostics,
            ]);

            return [
                'success' => false,
                'error' => $error,
                'queued' => $queued,
                'missing_client_context' => (bool) (is_array($result) && ($result['missing_client_context'] ?? false)),
                'diagnostics' => $diagnostics,
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
            'tenant_id' => $session->tenant_id,
            'router_id' => $router->id,
            'username' => $session->username,
            'router' => $router->name,
            'radius_enabled' => $radiusEnabled,
            'mac_address' => $session->mac_address,
            'ip_address' => $session->ip_address,
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

        $minutesRemaining = now()->diffInMinutes($session->expires_at, false);

        // Check if session is expiring soon (send warning)
        if ($minutesRemaining <= 10 && $minutesRemaining > 0 && !$session->grace_period_active) {
            $this->sendExpiryWarning($session);
        }

        // Activate grace period if expired but still inside the configured buffer.
        if ($session->shouldActivateGracePeriod()) {
            $session->activateGracePeriod();
            
            Log::channel('mikrotik')->info('Grace period activated', [
                'session_id' => $session->id,
                'username' => $session->username,
                'grace_ends_at' => $session->grace_period_ends_at->toIso8601String(),
            ]);
        }

        // Check if session should be disconnected
        if ($session->shouldDisconnect()) {
            $this->terminateSession($session, 'expired');
            return;
        }
        
        // Pure RADIUS mode uses accounting updates instead of RouterOS API polling.
        if ($this->shouldUseRadiusManagedDisconnect($session, 'expiry_check')) {
            return;
        }

        // Sync usage data
        $this->mikrotikService->syncSessionUsage($session);
    }

    /**
     * Terminate a session (user request, expiry, admin action)
     */
    public function terminateSession(UserSession $session, string $reason): bool
    {
        if ($this->shouldUseRadiusManagedDisconnect($session, $reason)) {
            return $this->terminateRadiusManagedSession($session, $reason);
        }

        $router = $session->router;
        if (!$router) {
            Log::channel('mikrotik')->warning('Session termination skipped router disconnect because router is missing', [
                'session_id' => $session->id,
                'router_id' => $session->router_id,
            ]);
            $session->markTerminated($reason);
            return true;
        }
        
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

    private function resolveActivationRouter(UserSession $session): ?Router
    {
        $originalRouterId = (int) ($session->router_id ?? 0);
        $boundRouter = Router::resolveCanonicalRecord($session->router);

        if ($boundRouter && $boundRouter->hasOperationalStatus()) {
            if ($originalRouterId !== (int) $boundRouter->id) {
                $session->update(['router_id' => $boundRouter->id]);
                $session->refresh();
            }

            return $boundRouter;
        }

        $fallback = Router::resolvePreferredForTenant((int) $session->tenant_id);
        if (!$fallback) {
            $fallback = $boundRouter;
        }

        if (!$fallback) {
            return null;
        }

        if ($originalRouterId !== (int) $fallback->id) {
            $session->update(['router_id' => $fallback->id]);
            $session->refresh();
        }

        Log::channel('mikrotik')->warning('Resolved fallback router for session activation', [
            'session_id' => $session->id,
            'original_router_id' => $originalRouterId,
            'bound_router_id' => $boundRouter?->id,
            'resolved_router_id' => $fallback->id,
            'resolved_router_status' => $fallback->status,
        ]);

        return $fallback;
    }

    private function shouldUseRadiusManagedDisconnect(UserSession $session, string $reason): bool
    {
        if (!(bool) config('radius.enabled', false) || !(bool) config('radius.pure_radius', false)) {
            return false;
        }

        $radiusMetadata = is_array(($session->metadata ?? [])['radius'] ?? null)
            ? (array) $session->metadata['radius']
            : [];

        if (!((bool) ($radiusMetadata['provisioned'] ?? false) || trim((string) $session->username) !== '')) {
            return false;
        }

        return in_array($reason, ['expired', 'data_limit_reached', 'expiry_check', 'admin_request', 'admin_bulk_disconnect'], true);
    }

    private function terminateRadiusManagedSession(UserSession $session, string $reason): bool
    {
        $this->revokeRadiusAuthorization($session, $reason);

        $result = $this->resolveRadiusDisconnectService()->disconnect($session);

        if (!($result['success'] ?? false)) {
            Log::channel('radius')->warning('Session termination failed during RADIUS disconnect', [
                'session_id' => $session->id,
                'username' => $session->username,
                'reason' => $reason,
                'router_id' => $session->router_id,
                'nas_ip' => $result['nas_ip'] ?? null,
                'error' => $result['error'] ?? 'Unknown RADIUS disconnect failure.',
            ]);

            return false;
        }

        $session->markTerminated($reason);

        Log::channel('radius')->info('Session terminated via RADIUS disconnect', [
            'session_id' => $session->id,
            'username' => $session->username,
            'reason' => $reason,
            'router_id' => $session->router_id,
            'nas_ip' => $result['nas_ip'] ?? null,
        ]);

        return true;
    }

    private function resolveRadiusDisconnectService(): RadiusDisconnectService
    {
        return $this->radiusDisconnectService ??= app(RadiusDisconnectService::class);
    }

    private function resolveRadiusProvisioningService(): FreeRadiusProvisioningService
    {
        return $this->radiusProvisioningService ??= app(FreeRadiusProvisioningService::class);
    }

    private function revokeRadiusAuthorization(UserSession $session, string $reason): void
    {
        $radiusUsername = $this->resolveRadiusUsername($session);
        if ($radiusUsername === '') {
            return;
        }

        try {
            $this->resolveRadiusProvisioningService()->revokeUser($radiusUsername);
            $this->storeRadiusRevocationMetadata($session, $radiusUsername, $reason);
        } catch (\Throwable $e) {
            Log::channel('radius')->warning('Failed to revoke RADIUS user profile during session termination', [
                'session_id' => $session->id,
                'username' => $radiusUsername,
                'reason' => $reason,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function resolveRadiusUsername(UserSession $session): string
    {
        $metadata = is_array($session->metadata) ? $session->metadata : [];
        $radiusMetadata = is_array($metadata['radius'] ?? null) ? $metadata['radius'] : [];

        foreach ([
            $radiusMetadata['active_username'] ?? null,
            $radiusMetadata['username'] ?? null,
            $session->username,
        ] as $candidate) {
            $username = trim((string) $candidate);
            if ($username !== '') {
                return $username;
            }
        }

        return '';
    }

    private function storeRadiusRevocationMetadata(UserSession $session, string $username, string $reason): void
    {
        $revokedAt = now()->toIso8601String();
        $sessionMetadata = is_array($session->metadata) ? $session->metadata : [];
        $sessionRadius = is_array($sessionMetadata['radius'] ?? null) ? $sessionMetadata['radius'] : [];
        $sessionMetadata['radius'] = array_merge($sessionRadius, [
            'username' => $username,
            'active_username' => $username,
            'provisioned' => false,
            'revoked_at' => $revokedAt,
            'revocation_reason' => $reason,
        ]);

        $session->update(['metadata' => $sessionMetadata]);

        $payment = $session->payment()->first();
        if (!$payment) {
            return;
        }

        $paymentMetadata = is_array($payment->metadata) ? $payment->metadata : [];
        $paymentRadius = is_array($paymentMetadata['radius'] ?? null) ? $paymentMetadata['radius'] : [];
        $paymentMetadata['radius'] = array_merge($paymentRadius, [
            'username' => $username,
            'active_username' => $username,
            'provisioned' => false,
            'revoked_at' => $revokedAt,
            'revocation_reason' => $reason,
        ]);

        $payment->update(['metadata' => $paymentMetadata]);
    }
}
