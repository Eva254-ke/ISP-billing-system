<?php

namespace App\Jobs;

use App\Models\UserSession;
use App\Services\MikroTik\SessionManager;
use App\Services\Radius\FreeRadiusProvisioningService;
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

            $this->ensureRadiusProvisioned($this->session->fresh(), $radiusProvisioning);

            $result = $sessionManager->activateSession(
                $this->session->fresh(),
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
                expiresAt: $expiresAt
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
}
