<?php

namespace App\Jobs;

use App\Models\UserSession;
use App\Services\MikroTik\MikroTikService;
use App\Services\MikroTik\SessionManager;
use App\Services\Radius\RadiusAccountingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SyncSessionUsage implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * 🟡 MEDIUM PRIORITY
     */
    public int $timeout = 60;
    public int $tries = 2;
    public $backoff = [30, 60];

    public int $sessionId;

    public function __construct(int $sessionId)
    {
        $this->sessionId = $sessionId;
        
        // ✅ Set queue in constructor
        $this->onQueue('medium');
    }

    public function handle(
        MikroTikService $mikrotikService,
        RadiusAccountingService $radiusAccountingService,
        SessionManager $sessionManager
    ): void
    {
        $session = UserSession::find($this->sessionId);

        if (!$session || !in_array($session->status, ['active', 'idle'])) {
            return;
        }

        try {
            $success = false;

            if ((bool) config('radius.enabled', false) && (bool) config('radius.pure_radius', false)) {
                $success = $radiusAccountingService->syncActiveSession($session) !== null;

                if (!$success && $session->shouldActivateGracePeriod()) {
                    $session->activateGracePeriod();
                    Log::channel('radius')->info('Grace period activated after accounting sync miss', [
                        'session_id' => $session->id,
                        'username' => $session->username,
                        'grace_ends_at' => $session->fresh()?->grace_period_ends_at?->toIso8601String(),
                    ]);
                    return;
                }

                if (!$success && $session->shouldDisconnect()) {
                    $sessionManager->terminateSession($session, 'expired');
                    $session->markExpired('expired');
                    Log::channel('radius')->info('Expired pure-RADIUS session marked expired after accounting sync miss', [
                        'session_id' => $session->id,
                        'username' => $session->username,
                    ]);
                    return;
                }
            } else {
                $success = $mikrotikService->syncSessionUsage($session);
            }

            if ($success) {
                Log::channel('mikrotik')->debug('Session usage synced', [
                    'session_id' => $session->id,
                    'bytes_total' => $session->bytes_total,
                ]);

                if ($session->data_limit_mb && $session->data_used_mb >= $session->data_limit_mb) {
                    DisconnectSession::dispatch($session, 'data_limit_reached');
                }
            } else {
                $session->increment('sync_retry_count');
                
                if ($session->sync_retry_count >= 5) {
                    $session->update(['sync_failed' => true]);
                }
            }

        } catch (\Exception $e) {
            Log::channel('mikrotik')->error('Session usage sync failed', [
                'session_id' => $session->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}
