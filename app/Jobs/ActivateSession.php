<?php

namespace App\Jobs;

use App\Models\UserSession;
use App\Services\MikroTik\SessionManager;
use App\Services\Radius\FreeRadiusProvisioningService;
use App\Services\Radius\RadiusIdentityResolver;
use Illuminate\Support\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ActivateSession implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * 🟠 HIGH PRIORITY
     */
    public int $timeout = 30;
    public int $tries = 3;
    public $backoff = [5, 15, 30];

    public UserSession $session;

    public function __construct(UserSession $session)
    {
        $this->session = $session;
        
        // ✅ Set queue in constructor
        $this->onQueue('high');
    }

    public function handle(SessionManager $sessionManager, FreeRadiusProvisioningService $radiusProvisioning): void
    {
        Log::channel('mikrotik')->info('Activating session', [
            'session_id' => $this->session->id,
            'username' => $this->session->username,
        ]);

        try {
            $package = $this->session->package;
            if (!$package) {
                throw new \RuntimeException('Session package is missing.');
            }

            $freshSession = $this->session->fresh();
            $this->ensureRadiusProvisioned($freshSession, $radiusProvisioning);

            $identityResolver = app(RadiusIdentityResolver::class);
            $identity = $identityResolver->resolve(
                phone: (string) $freshSession->phone,
                paymentId: (int) $freshSession->payment_id,
                macAddress: $freshSession->mac_address
            );

            if ($identityResolver->shouldUsePureRadiusFlow($identity)) {
                $preparedAt = now();
                $authorizationExpiresAt = $this->resolvePendingRadiusAuthorizationExpiresAt($freshSession, $preparedAt);

                $this->storeRadiusMetadata($freshSession, [
                    'authorization_prepared' => true,
                    'authorization_mode' => (string) ($identity['identity_type'] ?? 'phone'),
                    'waiting_for_hotspot_login' => true,
                    'waiting_for_reauth' => ($identity['identity_type'] ?? null) === 'mac',
                    'authorization_started_at' => $preparedAt->toIso8601String(),
                    'authorization_expires_at' => $authorizationExpiresAt->toIso8601String(),
                    'last_attempt_at' => $preparedAt->toIso8601String(),
                    'last_error' => null,
                    'last_failed_at' => null,
                ]);

                $freshSession->update([
                    'status' => 'idle',
                    'expires_at' => $authorizationExpiresAt,
                    'last_synced_at' => now(),
                ]);

                $payment = $freshSession->payment()->first();
                if ($payment) {
                    $payment->update([
                        'status' => 'confirmed',
                        'session_id' => $freshSession->id,
                        'reconciliation_notes' => 'Payment confirmed. Completing hotspot login through RADIUS.',
                    ]);
                }

                Log::channel('radius')->info('Pure RADIUS hotspot authorization prepared; RouterOS API login skipped', [
                    'session_id' => $freshSession->id,
                    'payment_id' => $freshSession->payment_id,
                    'username' => $freshSession->username,
                    'identity_type' => $identity['identity_type'],
                ]);

                return;
            }

            if (
                $identityResolver->shouldBypassRouterActivation($identity)
                && $identityResolver->matchesMacIdentity((string) $freshSession->username, $freshSession->mac_address)
            ) {
                $preparedAt = now();
                $authorizationExpiresAt = $this->resolvePendingRadiusAuthorizationExpiresAt($freshSession, $preparedAt);

                $this->storeRadiusMetadata($freshSession, [
                    'authorization_prepared' => true,
                    'authorization_mode' => 'mac',
                    'waiting_for_reauth' => true,
                    'authorization_started_at' => $preparedAt->toIso8601String(),
                    'authorization_expires_at' => $authorizationExpiresAt->toIso8601String(),
                    'last_attempt_at' => $preparedAt->toIso8601String(),
                    'last_error' => null,
                    'last_failed_at' => null,
                ]);

                $freshSession->update([
                    'status' => 'idle',
                    'expires_at' => $authorizationExpiresAt,
                    'last_synced_at' => now(),
                ]);

                $payment = $freshSession->payment()->first();
                if ($payment) {
                    $payment->update([
                        'status' => 'confirmed',
                        'session_id' => $freshSession->id,
                        'reconciliation_notes' => 'Payment confirmed. Waiting for hotspot re-authentication via RADIUS.',
                    ]);
                }

                Log::channel('radius')->info('RADIUS MAC authorization prepared; waiting for hotspot re-authentication', [
                    'session_id' => $freshSession->id,
                    'payment_id' => $freshSession->payment_id,
                    'username' => $freshSession->username,
                    'mac_address' => $freshSession->mac_address,
                ]);

                return;
            }

            $result = $sessionManager->activateSession(
                $freshSession,
                $package
            );

            if ($result['success']) {
                $this->session->update([
                    'status' => 'active',
                    'started_at' => now(),
                    'expires_at' => $result['expires_at'],
                    'last_synced_at' => now(),
                ]);

                $payment = $this->session->payment()->first();
                if ($payment) {
                    $payment->update([
                        'status' => 'completed',
                        'completed_at' => $payment->completed_at ?? now(),
                        'activated_at' => now(),
                        'session_id' => $this->session->id,
                        'reconciliation_notes' => null,
                    ]);
                }

                Log::channel('mikrotik')->info('Session activated', [
                    'session_id' => $this->session->id,
                ]);
            } else {
                throw new \Exception($result['error'] ?? 'Activation failed');
            }

        } catch (\Exception $e) {
            Log::channel('mikrotik')->error('Session activation failed', [
                'session_id' => $this->session->id,
                'error' => $e->getMessage(),
            ]);

            if ($this->attempts() >= $this->tries) {
                $this->session->update([
                    'status' => 'terminated',
                    'termination_reason' => 'activation_failed',
                ]);
            }

            throw $e;
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::channel('mikrotik')->critical('Session activation failed permanently', [
            'session_id' => $this->session->id,
            'error' => $exception->getMessage(),
        ]);
    }

    private function ensureRadiusProvisioned(UserSession $session, FreeRadiusProvisioningService $radiusProvisioning): void
    {
        if (!(bool) config('radius.enabled', false)) {
            return;
        }

        $package = $session->package;
        if (!$package) {
            throw new \RuntimeException('Session package is missing; cannot provision RADIUS access.');
        }

        $expiresAt = $session->expires_at ?? now()->addMinutes(max(1, (int) ($package->duration_in_minutes ?? 60)));

        try {
            $radiusProvisioning->provisionUser(
                username: (string) $session->username,
                password: (string) $session->username,
                package: $package,
                expiresAt: $expiresAt,
                callingStationId: $session->mac_address
            );

            $this->storeRadiusMetadata($session, [
                'provisioned' => true,
                'username' => (string) $session->username,
                'provisioned_at' => now()->toIso8601String(),
                'expires_at' => $expiresAt->toIso8601String(),
                'auth_hint' => 'password_equals_username',
                'last_error' => null,
                'last_failed_at' => null,
            ]);
        } catch (\Throwable $e) {
            $this->storeRadiusMetadata($session, [
                'provisioned' => false,
                'username' => (string) $session->username,
                'last_error' => $e->getMessage(),
                'last_failed_at' => now()->toIso8601String(),
                'expires_at' => $expiresAt->toIso8601String(),
            ]);

            Log::channel('radius')->error('RADIUS provisioning failed during activation retry', [
                'session_id' => $session->id,
                'payment_id' => $session->payment_id,
                'username' => $session->username,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    private function storeRadiusMetadata(UserSession $session, array $radiusMetadata): void
    {
        $sessionMetadata = is_array($session->metadata) ? $session->metadata : [];
        $existingSessionRadius = is_array($sessionMetadata['radius'] ?? null) ? $sessionMetadata['radius'] : [];
        $sessionMetadata['radius'] = array_merge($existingSessionRadius, $radiusMetadata);
        $session->update(['metadata' => $sessionMetadata]);

        $payment = $session->payment()->first();
        if (!$payment) {
            return;
        }

        $paymentMetadata = is_array($payment->metadata) ? $payment->metadata : [];
        $existingPaymentRadius = is_array($paymentMetadata['radius'] ?? null) ? $paymentMetadata['radius'] : [];
        $paymentMetadata['radius'] = array_merge($existingPaymentRadius, $radiusMetadata);
        $payment->update(['metadata' => $paymentMetadata]);
    }

    private function resolvePendingRadiusAuthorizationExpiresAt(UserSession $session, Carbon $preparedAt): Carbon
    {
        $windowMinutes = max(
            max(1, (int) config('radius.pending_login_window_minutes', 360)),
            max(1, (int) ($session->package?->duration_in_minutes ?? 0))
        );

        return $preparedAt->copy()->addMinutes($windowMinutes);
    }
}
