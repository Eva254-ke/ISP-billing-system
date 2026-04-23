<?php

namespace App\Jobs;

use App\Models\Payment;
use App\Models\UserSession;
use App\Services\MikroTik\SessionManager;
use App\Services\Radius\FreeRadiusProvisioningService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class ProcessMpesaCallback implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    private const AMOUNT_TOLERANCE = 0.01;

    /**
     * 🔴 CRITICAL PRIORITY
     */
    public int $timeout = 30;
    public int $tries = 5;
    public $backoff = [60, 120, 300, 600, 900]; // 1min, 2min, 5min, 10min, 15min
    public array $callbackData;

    public function __construct(array $callbackData)
    {
        $this->callbackData = $callbackData;
        $this->onQueue('critical');
    }

    public function handle(SessionManager $sessionManager, FreeRadiusProvisioningService $radiusProvisioning): void
    {
        // ──────────────────────────────────────────────────────────────────
        // EDGE CASE #1: Missing CheckoutRequestID
        // ──────────────────────────────────────────────────────────────────
        $checkoutRequestId = $this->callbackData['CheckoutRequestID'] ?? null;
        
        if (!$checkoutRequestId) {
            Log::channel('payment')->critical('Callback missing CheckoutRequestID', [
                'data' => $this->callbackData,
                'timestamp' => now()->toIso8601String(),
            ]);
            return; // Don't retry - bad data, will never be valid
        }

        // ──────────────────────────────────────────────────────────────────
        // EDGE CASE #2: Callback before payment record exists
        // ──────────────────────────────────────────────────────────────────
        $payment = Payment::where('mpesa_checkout_request_id', $checkoutRequestId)->first();
        $matchedViaFallback = false;

        if (!$payment) {
            $payment = $this->findFallbackPayment($checkoutRequestId);
            $matchedViaFallback = $payment !== null;
        }

        if (!$payment) {
            Log::channel('payment')->warning('Payment not found, queuing for retry', [
                'checkout_request_id' => $checkoutRequestId,
                'attempt' => $this->attempts(),
            ]);
            
            // Retry with exponential backoff (max 5 times over 24 hours)
            if ($this->attempts() < 5) {
                $this->release(pow(2, $this->attempts()) * 60); // 1min, 2min, 4min, 8min, 16min
            } else {
                // Alert admin after 24 hours of failed retries
                Log::channel('payment')->critical('Payment not found after 24hrs - manual review needed', [
                    'checkout_request_id' => $checkoutRequestId,
                    'data' => $this->callbackData,
                ]);
                // TODO: Send alert to admin (email/SMS/Slack)
            }
            return;
        }

        if ($matchedViaFallback) {
            Log::channel('payment')->warning('Callback matched payment via fallback lookup', [
                'checkout_request_id' => $checkoutRequestId,
                'payment_id' => $payment->id,
                'attempt' => $this->attempts(),
            ]);
        }

        $paymentId = (int) $payment->id;

        // ──────────────────────────────────────────────────────────────────
        // EDGE CASE #3: Duplicate callback (Idempotency)
        // ──────────────────────────────────────────────────────────────────
        if (in_array((string) $payment->status, ['completed', 'activated'], true)) {
            Log::channel('payment')->info('Duplicate callback ignored - already completed', [
                'payment_id' => $payment->id,
                'receipt' => $payment->mpesa_receipt_number,
                'amount' => $payment->amount,
            ]);
            return; // Success - don't retry, don't process again
        }

        // ──────────────────────────────────────────────────────────────────
        // EDGE CASE #4: Concurrent callback processing (Race condition)
        // ──────────────────────────────────────────────────────────────────
        $lockKey = "mpesa_callback:{$checkoutRequestId}";
        $lock = Cache::lock($lockKey, 30); // 30 second lock

        if (!$lock->get()) {
            Log::channel('payment')->warning('Callback already processing - skipping', [
                'checkout_request_id' => $checkoutRequestId,
            ]);
            return; // Another worker is handling this - don't retry
        }

        try {
            DB::beginTransaction();

            // ──────────────────────────────────────────────────────────────────
            // EDGE CASE #5: Database deadlock prevention
            // ──────────────────────────────────────────────────────────────────
            $payment = Payment::query()
                ->whereKey($paymentId)
                ->lockForUpdate()
                ->first();

            if (!$payment) {
                DB::rollBack();
                $lock->release();
                return;
            }

            if (in_array((string) $payment->status, ['completed', 'activated'], true)) {
                DB::commit();
                $lock->release();
                return;
            }

            if ((string) $payment->mpesa_checkout_request_id !== $checkoutRequestId) {
                $checkoutIdTaken = Payment::withTrashed()
                    ->where('mpesa_checkout_request_id', $checkoutRequestId)
                    ->where('id', '!=', $payment->id)
                    ->exists();

                if ($checkoutIdTaken) {
                    Log::channel('payment')->critical('CheckoutRequestID conflict during callback mapping', [
                        'checkout_request_id' => $checkoutRequestId,
                        'payment_id' => $payment->id,
                    ]);

                    DB::rollBack();
                    $lock->release();
                    return;
                }

                $payment->update([
                    'mpesa_checkout_request_id' => $checkoutRequestId,
                ]);
            }

            // ──────────────────────────────────────────────────────────────────
            // EDGE CASE #6: Partial/Malformed callback data
            // ──────────────────────────────────────────────────────────────────
            $resultCode = $this->callbackData['ResultCode'] ?? null;
            $resultDesc = $this->callbackData['ResultDesc'] ?? 'Unknown';
            
            if ($resultCode === null) {
                Log::channel('payment')->error('Callback missing ResultCode', [
                    'checkout_request_id' => $checkoutRequestId,
                    'data' => $this->callbackData,
                ]);
                
                $payment->update([
                    'callback_data' => $this->callbackData,
                    'callback_attempts' => $payment->callback_attempts + 1,
                ]);
                
                DB::rollBack();
                $lock->release();
                
                // Retry - might be temporary Safaricom issue
                throw new \Exception('Missing ResultCode in callback');
            }

            // ──────────────────────────────────────────────────────────────────
            // PROCESS SUCCESSFUL PAYMENT
            // ──────────────────────────────────────────────────────────────────
            if ($resultCode == 0) {
                $mpesaReceipt = $this->extractReceiptNumber($this->callbackData);
                $mpesaPhone = $this->extractPhoneNumber($this->callbackData);
                $amount = $this->extractAmount($this->callbackData);
                $expectedAmount = (float) $payment->amount;

                if ($amount !== null && ($amount + self::AMOUNT_TOLERANCE) < $expectedAmount) {
                    $payment->update([
                        'status' => 'failed',
                        'failed_at' => now(),
                        'callback_data' => $this->callbackData,
                        'callback_attempts' => $payment->callback_attempts + 1,
                        'reconciliation_notes' => sprintf('Underpaid amount: expected %.2f, received %.2f', $expectedAmount, $amount),
                        'metadata' => array_merge($payment->metadata ?? [], [
                            'daraja_last_status' => 'underpaid_amount',
                            'underpaid_expected_amount' => $expectedAmount,
                            'underpaid_received_amount' => $amount,
                        ]),
                    ]);

                    Log::channel('payment')->critical('Underpaid M-Pesa callback rejected', [
                        'payment_id' => $payment->id,
                        'checkout_request_id' => $checkoutRequestId,
                        'expected_amount' => $expectedAmount,
                        'received_amount' => $amount,
                    ]);

                    DB::commit();
                    $lock->release();
                    return;
                }

                // ──────────────────────────────────────────────────────────────
                // EDGE CASE #7: Duplicate receipt number (unique constraint)
                // ──────────────────────────────────────────────────────────────
                if (!empty($mpesaReceipt)) {
                    $existingPayment = Payment::withTrashed()
                        ->where('mpesa_receipt_number', $mpesaReceipt)
                        ->where('id', '!=', $payment->id)
                        ->first();

                    if ($existingPayment) {
                        Log::channel('payment')->critical('Duplicate receipt number detected', [
                            'receipt' => $mpesaReceipt,
                            'payment_id' => $payment->id,
                            'existing_payment_id' => $existingPayment->id,
                        ]);

                        DB::rollBack();
                        $lock->release();

                        // Don't retry - this is fraud or Safaricom error
                        return;
                    }
                }

                $payment->update([
                    'status' => 'confirmed',
                    'mpesa_receipt_number' => $mpesaReceipt,
                    'mpesa_phone' => $this->normalizePhoneForStorage($mpesaPhone) ?? $payment->mpesa_phone,
                    'amount' => $amount !== null ? max($expectedAmount, (float) $amount) : $payment->amount,
                    'callback_data' => $this->callbackData,
                    'callback_attempts' => $payment->callback_attempts + 1,
                    'confirmed_at' => now(),
                ]);

                Log::channel('payment')->info('Payment confirmed', [
                    'payment_id' => $payment->id,
                    'receipt' => $mpesaReceipt,
                    'phone' => $mpesaPhone,
                    'amount' => $amount,
                ]);

                // ──────────────────────────────────────────────────────────────
                // EDGE CASE #8: Router offline during activation
                // ──────────────────────────────────────────────────────────────
                $routerId = $this->getRouterForTenant($payment->tenant_id);
                
                if (!$routerId) {
                    Log::channel('mikrotik')->error('No online router for tenant', [
                        'tenant_id' => $payment->tenant_id,
                        'payment_id' => $payment->id,
                    ]);
                    
                    $payment->update([
                        'reconciliation_notes' => 'No online router available',
                    ]);
                    
                    DB::commit();
                    $lock->release();
                    return;
                }

                $package = $payment->package;
                if (!$package) {
                    Log::channel('payment')->error('Payment confirmed but package is missing; cannot activate access', [
                        'payment_id' => $payment->id,
                        'tenant_id' => $payment->tenant_id,
                    ]);

                    $payment->update([
                        'status' => 'confirmed',
                        'reconciliation_notes' => 'Payment confirmed but package is missing; manual intervention required.',
                    ]);

                    DB::commit();
                    $lock->release();
                    return;
                }

                $sessionPhone = $this->normalizePhoneForStorage($mpesaPhone) ?? (string) $payment->phone;
                $sessionUsername = $this->resolveRadiusUsernameFromPhone((string) $payment->phone, (int) $payment->id);
                $durationMinutes = max(1, (int) ($package->duration_in_minutes ?? 60));
                $paymentMetadata = is_array($payment->metadata) ? $payment->metadata : [];
                $paymentClientContext = is_array($paymentMetadata['client_context'] ?? null) ? $paymentMetadata['client_context'] : [];
                $clientMac = $this->normalizeMacAddress((string) ($paymentClientContext['mac'] ?? ''));
                $clientIp = $this->normalizeClientIpAddress((string) ($paymentClientContext['ip'] ?? ''));
                $session = UserSession::query()->where('payment_id', $payment->id)->first();

                if (!$session) {
                    $startAt = now();
                    $session = UserSession::create([
                        'tenant_id' => $payment->tenant_id,
                        'router_id' => $routerId,
                        'package_id' => $payment->package_id,
                        'username' => $sessionUsername,
                        'phone' => $sessionPhone,
                        'mac_address' => $clientMac,
                        'ip_address' => $clientIp,
                        'status' => 'idle',
                        'started_at' => $startAt,
                        'expires_at' => $startAt->copy()->addMinutes($durationMinutes),
                        'grace_period_seconds' => 300,
                        'payment_id' => $payment->id,
                    ]);
                } else {
                    $startedAt = $session->started_at ?: now();
                    $targetExpiry = $session->expires_at;
                    if (!$targetExpiry || $targetExpiry->isPast()) {
                        $targetExpiry = $startedAt->copy()->addMinutes($durationMinutes);
                    }

                    $session->update([
                        'router_id' => $session->router_id ?: $routerId,
                        'package_id' => $session->package_id ?: $payment->package_id,
                        'username' => ((string) $session->status === 'active')
                            ? ($session->username ?: $sessionUsername)
                            : $sessionUsername,
                        'phone' => $session->phone ?: $sessionPhone,
                        'mac_address' => $clientMac ?? $session->mac_address,
                        'ip_address' => $clientIp ?? $session->ip_address,
                        'status' => ((string) $session->status === 'active') ? 'active' : 'idle',
                        'started_at' => $startedAt,
                        'expires_at' => $targetExpiry,
                    ]);
                }

                $session = $session->fresh();
                $expiresAt = $session?->expires_at ?? now()->addMinutes($durationMinutes);
                $activationContext = [
                    'payment_id' => $payment->id,
                    'checkout_request_id' => $checkoutRequestId,
                    'tenant_id' => $payment->tenant_id,
                    'router_id' => $routerId,
                    'session_id' => $session->id,
                    'username' => $sessionUsername,
                    'radius_enabled' => (bool) config('radius.enabled', false),
                    'client_mac' => $session->mac_address,
                    'client_ip' => $session->ip_address,
                ];

                if ((bool) config('radius.enabled', false)) {
                    try {
                        $radiusProvisioning->provisionUser(
                            username: $sessionUsername,
                            password: $sessionUsername,
                            package: $package,
                            expiresAt: $expiresAt
                        );

                        $radiusMetadata = [
                            'provisioned' => true,
                            'username' => $sessionUsername,
                            'provisioned_at' => now()->toIso8601String(),
                            'expires_at' => $expiresAt->toIso8601String(),
                            'auth_hint' => 'password_equals_username',
                            'last_error' => null,
                            'last_failed_at' => null,
                        ];

                        $this->attachRadiusMetadataToSession($session, $radiusMetadata);
                        $this->attachRadiusMetadataToPayment($payment, $radiusMetadata);

                        Log::channel('payment')->info('RADIUS provisioned for confirmed callback payment', [
                            'payment_id' => $payment->id,
                            'checkout_request_id' => $checkoutRequestId,
                            'session_id' => $session->id,
                            'username' => $sessionUsername,
                            'router_id' => $routerId,
                            'client_mac' => $session->mac_address,
                            'client_ip' => $session->ip_address,
                            'expires_at' => $expiresAt->toIso8601String(),
                        ]);
                    } catch (\Throwable $radiusError) {
                        $radiusMetadata = [
                            'provisioned' => false,
                            'username' => $sessionUsername,
                            'last_error' => $radiusError->getMessage(),
                            'last_failed_at' => now()->toIso8601String(),
                            'expires_at' => $expiresAt->toIso8601String(),
                        ];

                        $this->attachRadiusMetadataToSession($session, $radiusMetadata);
                        $this->attachRadiusMetadataToPayment($payment, $radiusMetadata);

                        Log::channel('radius')->error('RADIUS provisioning failed for confirmed callback payment', [
                            'payment_id' => $payment->id,
                            'checkout_request_id' => $checkoutRequestId,
                            'session_id' => $session->id,
                            'username' => $sessionUsername,
                            'router_id' => $routerId,
                            'error' => $radiusError->getMessage(),
                        ]);
                    }
                }

                if ((string) $session->status === 'active') {
                    $payment->update([
                        'status' => 'completed',
                        'completed_at' => $payment->completed_at ?? now(),
                        'activated_at' => $payment->activated_at ?? now(),
                        'session_id' => $session->id,
                        'reconciliation_notes' => null,
                    ]);

                    DB::commit();
                    $lock->release();
                    return;
                }

                $result = $sessionManager->activateSession($session, $package);

                if ($result['success']) {
                    $payment->update([
                        'status' => 'completed',
                        'completed_at' => now(),
                        'activated_at' => now(),
                        'session_id' => $session->id,
                        'reconciliation_notes' => null,
                    ]);

                    Log::channel('payment')->info('Session activated successfully', [
                        'payment_id' => $payment->id,
                        'checkout_request_id' => $checkoutRequestId,
                        'session_id' => $session->id,
                        'username' => $session->username,
                        'router_id' => $routerId,
                    ]);
                } else {
                    Log::channel('mikrotik')->error('Session activation failed after payment confirmation', array_merge($activationContext, [
                        'error' => $result['error'] ?? 'Unknown error',
                        'queued' => (bool) ($result['queued'] ?? false),
                        'missing_client_context' => (bool) ($result['missing_client_context'] ?? false),
                    ]));

                    $paymentMetadata = is_array($payment->metadata) ? $payment->metadata : [];
                    $activationMetadata = is_array($paymentMetadata['activation'] ?? null) ? $paymentMetadata['activation'] : [];
                    $activationMetadata = array_merge($activationMetadata, [
                        'last_failed_at' => now()->toIso8601String(),
                        'last_error' => (string) ($result['error'] ?? 'Unknown'),
                        'queued' => (bool) ($result['queued'] ?? false),
                        'missing_client_context' => (bool) ($result['missing_client_context'] ?? false),
                        'router_id' => $routerId,
                        'session_id' => $session->id,
                        'radius_enabled' => (bool) config('radius.enabled', false),
                        'checkout_request_id' => $checkoutRequestId,
                    ]);

                    $payment->update([
                        'status' => 'confirmed',
                        'session_id' => $session->id,
                        'reconciliation_notes' => 'Payment confirmed but internet activation is pending: ' . ($result['error'] ?? 'Unknown'),
                        'metadata' => array_merge($paymentMetadata, [
                            'activation' => $activationMetadata,
                        ]),
                    ]);

                    // Queue retry for session activation (for both RADIUS and local hotspot modes).
                    \App\Jobs\ActivateSession::dispatch($session->fresh())->onQueue('high');

                    Log::channel('payment')->warning('Payment confirmed but internet activation is still pending', array_merge($activationContext, [
                        'error' => $result['error'] ?? 'Unknown',
                        'queued' => (bool) ($result['queued'] ?? false),
                        'missing_client_context' => (bool) ($result['missing_client_context'] ?? false),
                        'retry_dispatched' => true,
                    ]));
                }

            }
            // PROCESS FAILED PAYMENT
            // ──────────────────────────────────────────────────────────────────
            else {
                $payment->update([
                    'status' => 'failed',
                    'failed_at' => now(),
                    'callback_data' => $this->callbackData,
                    'callback_attempts' => $payment->callback_attempts + 1,
                    'reconciliation_notes' => "M-Pesa ResultCode {$resultCode}: {$resultDesc}",
                    'metadata' => array_merge($payment->metadata ?? [], [
                        'daraja_last_status' => 'failed_callback',
                        'daraja_result_code' => $resultCode,
                        'daraja_result_desc' => $resultDesc,
                    ]),
                ]);
                
                Log::channel('payment')->warning('Payment failed', [
                    'payment_id' => $payment->id,
                    'result_code' => $resultCode,
                    'result_desc' => $resultDesc,
                ]);
            }

            DB::commit();
            $lock->release();

        } 
        // ──────────────────────────────────────────────────────────────────
        // EDGE CASE #10: Database deadlock during transaction
        // ──────────────────────────────────────────────────────────────────
        catch (\Illuminate\Database\DeadlockException $e) {
            DB::rollBack();
            $lock->release();
            
            Log::channel('payment')->warning('Database deadlock - retrying', [
                'checkout_request_id' => $checkoutRequestId,
                'attempt' => $this->attempts(),
                'error' => $e->getMessage(),
            ]);
            
            $this->release(30); // Retry after 30 seconds
            return;
            
        } 
        // ──────────────────────────────────────────────────────────────────
        // EDGE CASE #11: General exception (network, timeout, etc.)
        // ──────────────────────────────────────────────────────────────────
        catch (\Exception $e) {
            DB::rollBack();
            $lock->release();
            
            Log::channel('payment')->error('Callback processing failed', [
                'checkout_request_id' => $checkoutRequestId,
                'attempt' => $this->attempts(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            // Let Laravel handle retry based on $tries and $backoff
            throw $e;
        }
    }

    // ──────────────────────────────────────────────────────────────────
    // HELPER METHODS
    // ──────────────────────────────────────────────────────────────────

    private function findFallbackPayment(string $checkoutRequestId): ?Payment
    {
        $merchantRequestId = trim((string) ($this->callbackData['MerchantRequestID'] ?? ''));
        if ($merchantRequestId !== '') {
            $merchantMatches = Payment::query()
                ->whereIn('status', ['initiated', 'pending', 'failed'])
                ->whereIn('payment_channel', ['captive_portal', 'session_extension'])
                ->whereNull('mpesa_receipt_number')
                ->where('created_at', '>=', now()->subHours(4))
                ->where('metadata->daraja_merchant_request_id', $merchantRequestId)
                ->orderByDesc('created_at')
                ->limit(3)
                ->get();

            if ($merchantMatches->count() === 1) {
                return $merchantMatches->first();
            }

            if ($merchantMatches->count() > 1) {
                Log::channel('payment')->critical('Ambiguous merchant-based callback mapping; manual review required', [
                    'checkout_request_id' => $checkoutRequestId,
                    'merchant_request_id' => $merchantRequestId,
                    'payment_ids' => $merchantMatches->pluck('id')->all(),
                ]);
            }
        }

        $amount = $this->extractAmount($this->callbackData);
        $phoneCandidates = $this->buildPhoneCandidates($this->extractPhoneNumber($this->callbackData));

        if ($amount === null || $phoneCandidates === []) {
            return null;
        }

        $minAmount = max(0, $amount - 0.05);
        $maxAmount = $amount + 0.05;

        $matches = Payment::query()
            ->whereIn('status', ['initiated', 'pending', 'failed'])
            ->whereIn('payment_channel', ['captive_portal', 'session_extension'])
            ->whereNull('mpesa_receipt_number')
            ->where('created_at', '>=', now()->subHours(4))
            ->whereBetween('amount', [$minAmount, $maxAmount])
            ->where(function ($query) use ($phoneCandidates) {
                foreach ($phoneCandidates as $phone) {
                    $query->orWhere('phone', $phone);
                }
            })
            ->orderByDesc('created_at')
            ->limit(3)
            ->get();

        if ($matches->count() === 1) {
            return $matches->first();
        }

        if ($matches->count() > 1) {
            Log::channel('payment')->critical('Ambiguous fallback callback mapping; manual review required', [
                'checkout_request_id' => $checkoutRequestId,
                'phone_candidates' => $phoneCandidates,
                'amount' => $amount,
                'payment_ids' => $matches->pluck('id')->all(),
            ]);
        }

        return null;
    }

    private function buildPhoneCandidates(?string $phone): array
    {
        $digits = preg_replace('/\D+/', '', (string) $phone);
        if ($digits === '') {
            return [];
        }

        $candidates = [$digits];

        if (str_starts_with($digits, '254') && strlen($digits) >= 12) {
            $candidates[] = '0' . substr($digits, 3);
        }

        if (str_starts_with($digits, '0') && strlen($digits) >= 10) {
            $candidates[] = '254' . substr($digits, 1);
        }

        return array_values(array_unique(array_filter($candidates)));
    }

    private function extractReceiptNumber(array $data): ?string
    {
        return $data['MpesaReceiptNumber'] 
            ?? $data['ReceiptNumber'] 
            ?? $data['receipt_number'] 
            ?? null;
    }

    private function extractPhoneNumber(array $data): ?string
    {
        return $data['PhoneNumber'] 
            ?? $data['msisdn'] 
            ?? $data['phone'] 
            ?? null;
    }

    private function extractAmount(array $data): ?float
    {
        $amount = $data['Amount'] 
            ?? $data['amount'] 
            ?? $data['TransAmount'] 
            ?? null;
        
        return $amount ? (float) $amount : null;
    }

    private function normalizePhoneForStorage(?string $phone): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $phone);
        if ($digits === '') {
            return null;
        }

        if (str_starts_with($digits, '254') && strlen($digits) >= 12) {
            return '0' . substr($digits, 3, 9);
        }

        if (str_starts_with($digits, '0') && strlen($digits) >= 10) {
            return substr($digits, 0, 10);
        }

        return $digits;
    }

    private function resolveRadiusUsernameFromPhone(string $phone, int $paymentId): string
    {
        $digits = preg_replace('/\D+/', '', $phone);
        if ($digits !== '') {
            return 'cb' . $digits;
        }

        return 'cbu' . $paymentId;
    }

    private function normalizeMacAddress(string $mac): ?string
    {
        $normalized = strtoupper(preg_replace('/[^0-9A-Fa-f]/', '', $mac) ?? '');
        if (strlen($normalized) !== 12) {
            return null;
        }

        return implode(':', str_split($normalized, 2));
    }

    private function normalizeClientIpAddress(string $ipAddress): ?string
    {
        $candidate = trim($ipAddress);
        if ($candidate === '') {
            return null;
        }

        return filter_var($candidate, FILTER_VALIDATE_IP) ? $candidate : null;
    }

    private function attachRadiusMetadataToSession(UserSession $session, array $radiusMetadata): void
    {
        $sessionMetadata = is_array($session->metadata) ? $session->metadata : [];
        $existingRadiusMetadata = is_array($sessionMetadata['radius'] ?? null) ? $sessionMetadata['radius'] : [];
        $sessionMetadata['radius'] = array_merge($existingRadiusMetadata, $radiusMetadata);
        $session->update(['metadata' => $sessionMetadata]);
        $session->refresh();
    }

    private function attachRadiusMetadataToPayment(Payment $payment, array $radiusMetadata): void
    {
        $paymentMetadata = is_array($payment->metadata) ? $payment->metadata : [];
        $existingRadiusMetadata = is_array($paymentMetadata['radius'] ?? null) ? $paymentMetadata['radius'] : [];
        $paymentMetadata['radius'] = array_merge($existingRadiusMetadata, $radiusMetadata);
        $payment->update(['metadata' => $paymentMetadata]);
        $payment->refresh();
    }

    private function getRouterForTenant(int $tenantId): ?int
    {
        return \App\Models\Router::where('tenant_id', $tenantId)
            ->whereIn('status', ['online', 'warning'])
            ->orderByRaw(
                "CASE WHEN status = ? THEN 0 WHEN status = ? THEN 1 ELSE 2 END",
                ['online', 'warning']
            )
            ->orderBy('last_seen_at', 'desc')
            ->value('id');
    }

    // ──────────────────────────────────────────────────────────────────
    // FAILED JOB HANDLER
    // ──────────────────────────────────────────────────────────────────

    public function failed(\Throwable $exception): void
    {
        Log::channel('payment')->critical('M-Pesa callback job failed permanently - manual review needed', [
            'checkout_request_id' => $this->callbackData['CheckoutRequestID'] ?? null,
            'error' => $exception->getMessage(),
            'attempts' => $this->attempts(),
            'data' => $this->callbackData,
        ]);

        // TODO: Send alert to admin (email/SMS/Slack)
        // Example:
        // \App\Models\User::where('role', 'super_admin')
        //     ->get()
        //     ->each(function ($user) {
        //         $user->notify(new \App\Notifications\MpesaCallbackFailed($this->callbackData));
        //     });
    }
}

