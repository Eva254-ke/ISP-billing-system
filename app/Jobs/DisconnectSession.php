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

class DisconnectSession implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * 🟠 HIGH PRIORITY
     */
    public int $timeout = 30;
    public int $tries = 3;
    public $backoff = [5, 10, 20];

    public int $sessionId;
    public string $reason;

    public function __construct(UserSession $session, string $reason = 'unknown')
    {
        $this->sessionId = $session->id;
        $this->reason = $reason;
        
        // ✅ Set queue in constructor
        $this->onQueue('high');
    }

    public function handle(SessionManager $sessionManager): void
    {
        $session = UserSession::find($this->sessionId);

        if (!$session) {
            return;
        }

        Log::channel('mikrotik')->info('Disconnecting session', [
            'session_id' => $session->id,
            'username' => $session->username,
            'reason' => $this->reason,
        ]);

        try {
            $result = $sessionManager->terminateSession($session, $this->reason);

            if ($result) {
                Log::channel('mikrotik')->info('Session disconnected', [
                    'session_id' => $session->id,
                ]);
            } else {
                throw new \Exception('Disconnect failed');
            }

        } catch (\Exception $e) {
            Log::channel('mikrotik')->error('Disconnect failed', [
                'session_id' => $session->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}