<?php

namespace App\Jobs;

use App\Models\UserSession;
use App\Services\MikroTik\SessionManager;
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

    public function handle(SessionManager $sessionManager): void
    {
        Log::channel('mikrotik')->info('Activating session', [
            'session_id' => $this->session->id,
            'username' => $this->session->username,
        ]);

        try {
            $result = $sessionManager->activateSession(
                $this->session,
                $this->session->package
            );

            if ($result['success']) {
                $this->session->update([
                    'status' => 'active',
                    'started_at' => now(),
                    'expires_at' => $result['expires_at'],
                    'last_synced_at' => now(),
                ]);

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
}