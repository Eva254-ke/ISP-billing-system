<?php

namespace App\Console\Commands;

use App\Models\UserSession;
use App\Services\MikroTik\SessionManager;
use App\Services\MikroTik\MikroTikService;
use App\Services\Radius\RadiusAccountingService;
use App\Jobs\SyncSessionUsage;
use App\Jobs\DisconnectSession;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CheckExpiringSessions extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'sessions:check-expiring';

    /**
     * The console command description.
     */
    protected $description = 'Check and handle expiring user sessions (grace periods, warnings, disconnects)';

    public function __construct(
        protected SessionManager $sessionManager,
        protected MikroTikService $mikroTikService,
        protected RadiusAccountingService $radiusAccountingService
    ) {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        Log::channel('mikrotik')->info('Starting expiring sessions check');

        $this->info('📋 Checking expiring sessions...');

        // ──────────────────────────────────────────────────────────────────
        // 1. GET SESSIONS EXPIRING IN NEXT 30 MINUTES
        // ──────────────────────────────────────────────────────────────────
        $expiring = UserSession::query()
            ->whereIn('status', ['active', 'idle'])
            ->where(function ($query) {
                $query->where('expires_at', '<=', now()->addMinutes(30))
                    ->orWhere('grace_period_active', true);
            })
            ->with(['router', 'package'])
            ->get();

        $this->info("Found {$expiring->count()} sessions expiring soon");

        $warningsSent = 0;
        $graceActivated = 0;
        $disconnected = 0;

        foreach ($expiring as $session) {
            $minutesRemaining = now()->diffInMinutes($session->expires_at, false);

            // ──────────────────────────────────────────────────────────────
            // EDGE CASE: Send warning SMS at T-10 minutes
            // ──────────────────────────────────────────────────────────────
            if ($minutesRemaining <= 10 && $minutesRemaining > 0 && !$session->grace_period_active) {
                $this->sendExpiryWarning($session, $minutesRemaining);
                $warningsSent++;
            }

            // ──────────────────────────────────────────────────────────────
            // EDGE CASE: Activate grace period when expired (but within 5 min)
            // ──────────────────────────────────────────────────────────────
            if ($session->shouldActivateGracePeriod()) {
                $session->activateGracePeriod();
                $session->refresh();
                $graceActivated++;
                
                Log::channel('mikrotik')->info('Grace period activated', [
                    'session_id' => $session->id,
                    'username' => $session->username,
                    'grace_ends_at' => $session->grace_period_ends_at->toIso8601String(),
                ]);

                $this->warn("⏰ Grace period activated: {$session->username}");
            }

            // ──────────────────────────────────────────────────────────────
            // EDGE CASE: Disconnect if grace period expired
            // ──────────────────────────────────────────────────────────────
            if ($session->shouldDisconnect()) {
                DisconnectSession::dispatch($session, 'expired')->onQueue('high');
                $disconnected++;
                
                $this->error("❌ Disconnected: {$session->username}");
            }
        }

        // ──────────────────────────────────────────────────────────────────
        // 2. SYNC USAGE DATA FOR ACTIVE SESSIONS (Not synced in 5 min)
        // ──────────────────────────────────────────────────────────────────
        $needsSync = UserSession::query()
            ->whereIn('status', ['active', 'idle'])
            ->where('expires_at', '>', now()->subMinutes(5))
            ->needsSync(5)
            ->limit(100)
            ->get();

        foreach ($needsSync as $session) {
            SyncSessionUsage::dispatch($session->id)->onQueue('medium');
        }

        $this->info("📊 Queued {$needsSync->count()} sessions for usage sync");

        // ──────────────────────────────────────────────────────────────────
        // 3. CLEANUP: Mark orphaned sessions as terminated
        // ──────────────────────────────────────────────────────────────────
        $orphaned = UserSession::query()
            ->whereIn('status', ['active', 'idle'])
            ->where('expires_at', '<', now()->subHours(2))
            ->where(function ($query) {
                $query->where('grace_period_active', false)
                    ->orWhereNull('grace_period_ends_at')
                    ->orWhere('grace_period_ends_at', '<=', now());
            })
            ->limit(50)
            ->get();

        foreach ($orphaned as $session) {
            if (!$session->shouldDisconnect() || $session->awaitsRadiusReauthentication()) {
                continue;
            }

            $stillConnected = false;

            if ((bool) config('radius.enabled', false)) {
                try {
                    $stillConnected = $this->radiusAccountingService->syncActiveSession($session) !== null;
                } catch (\Throwable $e) {
                    Log::channel('radius')->warning('Skipping orphaned session cleanup after RADIUS verification failure', [
                        'session_id' => $session->id,
                        'username' => $session->username,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            if (!$stillConnected) {
                try {
                    $stillConnected = $this->mikroTikService->syncSessionUsage($session);
                } catch (\Throwable $e) {
                    Log::channel('mikrotik')->warning('Skipping orphaned session cleanup after router verification failure', [
                        'session_id' => $session->id,
                        'username' => $session->username,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            if ($stillConnected) {
                continue;
            }

            $session->markExpired('orphaned_cleanup');
        }

        $this->info("🧹 Cleaned up {$orphaned->count()} orphaned sessions");

        // ──────────────────────────────────────────────────────────────────
        // SUMMARY
        // ──────────────────────────────────────────────────────────────────
        Log::channel('mikrotik')->info('Expiring sessions check completed', [
            'expiring' => $expiring->count(),
            'warnings_sent' => $warningsSent,
            'grace_activated' => $graceActivated,
            'disconnected' => $disconnected,
            'sync_queued' => $needsSync->count(),
            'orphaned_cleaned' => $orphaned->count(),
        ]);

        $this->newLine();
        $this->info('✅ Summary:');
        $this->line("   Warnings sent: {$warningsSent}");
        $this->line("   Grace periods activated: {$graceActivated}");
        $this->line("   Disconnected: {$disconnected}");
        $this->line("   Sync queued: {$needsSync->count()}");
        $this->line("   Orphaned cleaned: {$orphaned->count()}");

        return Command::SUCCESS;
    }

    /**
     * Send expiry warning SMS to user
     */
    private function sendExpiryWarning(UserSession $session, int $minutesRemaining): void
    {
        if (!$session->phone) {
            Log::channel('notification')->debug('No phone number for warning', [
                'session_id' => $session->id,
            ]);
            return;
        }

        $message = "Omwenga WiFi: Your session expires in {$minutesRemaining} minutes. Renew now to stay connected!";

        Log::channel('notification')->info('Sending expiry warning SMS', [
            'session_id' => $session->id,
            'phone' => $session->phone,
            'minutes' => $minutesRemaining,
        ]);

        // TODO: Integrate with SMS service (Africa's Talking)
        // Example:
        // SmsService::send($session->phone, $message);

        // For now, just log
        $this->line("   📱 Warning SMS would be sent to: {$session->phone}");
    }
}
