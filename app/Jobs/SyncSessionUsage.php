<?php

namespace App\Jobs;

use App\Models\UserSession;
use App\Services\MikroTik\MikroTikService;
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

    public function handle(MikroTikService $mikrotikService): void
    {
        $session = UserSession::find($this->sessionId);

        if (!$session || !in_array($session->status, ['active', 'idle'])) {
            return;
        }

        try {
            $success = $mikrotikService->syncSessionUsage($session);

            if ($success) {
                Log::channel('mikrotik')->debug('Session usage synced', [
                    'session_id' => $session->id,
                    'bytes_total' => $session->bytes_total,
                ]);

                if ($session->data_limit_mb && $session->data_used_mb >= $session->data_limit_mb) {
                    DisconnectSession::dispatch($session);
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