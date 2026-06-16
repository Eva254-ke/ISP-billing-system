<?php

namespace App\Console\Commands;

use App\Models\UserSession;
use App\Services\MikroTik\SessionManager;
use App\Services\MikroTik\MikroTikService;
use App\Services\Radius\RadiusAccountingService;
use App\Services\Notifications\WhatsAppNotificationService;
use App\Jobs\SyncSessionUsage;
use App\Jobs\DisconnectSession;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
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
        protected RadiusAccountingService $radiusAccountingService,
        protected WhatsAppNotificationService $whatsappService,
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
            $this->refreshRadiusTimingBeforeExpiryDecision($session);
            $session->refresh();
            $secondsRemaining = $session->expires_at
                ? now()->diffInSeconds($session->expires_at, false)
                : 0;
            $minutesRemaining = (int) ceil(max(0, $secondsRemaining) / 60);

            // ──────────────────────────────────────────────────────────────
            // EDGE CASE: Send warning at T-10 minutes (deduplicated)
            // ──────────────────────────────────────────────────────────────
            if ($minutesRemaining <= 10 && $minutesRemaining > 0 && !$session->grace_period_active) {
                if ($this->shouldSendExpiryWarning($session)) {
                    if ($this->sendExpiryWarning($session, $minutesRemaining)) {
                        $warningsSent++;
                    }
                }
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

    private function refreshRadiusTimingBeforeExpiryDecision(UserSession $session): void
    {
        if (!(bool) config('radius.enabled', false)) {
            return;
        }

        if (!$session->expires_at || $session->expires_at->gt(now()->addMinutes(5))) {
            return;
        }

        try {
            $record = $this->radiusAccountingService->syncActiveSession($session);
            if ($record !== null) {
                Log::channel('radius')->info('Refreshed RADIUS session timing before expiry decision', [
                    'session_id' => $session->id,
                    'username' => $session->username,
                    'acct_session_id' => $record['acctsessionid'] ?? null,
                    'expires_at' => $session->fresh()?->expires_at?->toIso8601String(),
                ]);
            }
        } catch (\Throwable $e) {
            Log::channel('radius')->warning('RADIUS timing refresh failed before expiry decision', [
                'session_id' => $session->id,
                'username' => $session->username,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Determine whether an expiry warning should be sent for this session.
     * Prevents duplicate warnings within the same expiry window.
     */
    private function shouldSendExpiryWarning(UserSession $session): bool
    {
        $metadata = is_array($session->metadata) ? $session->metadata : [];
        $lastWarningAt = $metadata['last_expiry_warning_at']
            ?? $metadata['last_expiry_warning_attempt_at']
            ?? null;

        $cacheKey = $this->expiryWarningCacheKey($session);

        if (Cache::has($cacheKey)) {
            return false;
        }

        if ($lastWarningAt === null) {
            return true;
        }

        $this->warn("   WhatsApp expiry warning failed for: {$session->phone}");

        return false;

        try {
            $lastWarning = \Illuminate\Support\Carbon::parse($lastWarningAt);
            // Only warn once per 20-minute window to avoid spam
            if ($lastWarning->diffInMinutes(now()) >= 20) {
                return true;
            }
        } catch (\Throwable $e) {
            return true;
        }

        return false;
    }

    /**
     * Send expiry warning via WhatsApp to user
     */
    private function sendExpiryWarning(UserSession $session, int $minutesRemaining): bool
    {
        if (!$session->phone) {
            Log::channel('notification')->debug('No phone number for warning', [
                'session_id' => $session->id,
            ]);
            return false;
        }

        $brand = $session->tenant?->name ?? 'WiFi';

        Log::channel('notification')->info('Sending expiry warning via WhatsApp', [
            'session_id' => $session->id,
            'phone' => $session->phone,
            'minutes' => $minutesRemaining,
        ]);

        $this->recordExpiryWarningAttempt($session);

        $sent = $this->whatsappService->sendSessionExpiryWarning(
            $session->phone,
            $minutesRemaining,
            $session->username ?? $session->phone,
            $brand
        );

        if ($sent) {
            $this->recordExpiryWarningSent($session);
            $this->line("   WhatsApp expiry warning sent to: {$session->phone}");

            return true;
        }

        $this->warn("   WhatsApp expiry warning failed for: {$session->phone}");

        return false;
    }

    private function recordExpiryWarningSent(UserSession $session): void
    {
        $session->refresh();
        $metadata = is_array($session->metadata) ? $session->metadata : [];
        $metadata['last_expiry_warning_at'] = now()->toIso8601String();
        $metadata['expiry_warning_count'] = ($metadata['expiry_warning_count'] ?? 0) + 1;

        try {
            $session->update(['metadata' => $metadata]);
        } catch (\Throwable $e) {
            Log::channel('notification')->warning('Failed to persist expiry warning metadata', [
                'session_id' => $session->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function recordExpiryWarningAttempt(UserSession $session): void
    {
        $metadata = is_array($session->metadata) ? $session->metadata : [];
        $metadata['last_expiry_warning_attempt_at'] = now()->toIso8601String();
        Cache::put($this->expiryWarningCacheKey($session), true, now()->addMinutes(20));

        try {
            $session->update(['metadata' => $metadata]);
        } catch (\Throwable $e) {
            Log::channel('notification')->warning('Failed to persist expiry warning attempt metadata', [
                'session_id' => $session->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function expiryWarningCacheKey(UserSession $session): string
    {
        $expiresAt = $session->expires_at?->timestamp ?? 'none';

        return "session-expiry-warning:{$session->id}:{$expiresAt}";
    }
}
