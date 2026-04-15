<?php

namespace App\Jobs;

use App\Models\Payment;
use App\Models\UserSession;
use App\Services\MikroTik\SessionManager;
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

    public function handle(SessionManager $sessionManager): void
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
        if ($payment->status === 'completed') {
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

            if ($payment->status === 'completed') {
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
                    'amount' => $amount ?? $payment->amount,
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

                $sessionPhone = $this->normalizePhoneForStorage($mpesaPhone) ?? (string) $payment->phone;

                $session = UserSession::create([
                    'tenant_id' => $payment->tenant_id,
                    'router_id' => $routerId,
                    'package_id' => $payment->package_id,
                    'username' => $sessionPhone,
                    'phone' => $sessionPhone,
                    'status' => 'pending',
                    'started_at' => now(),
                    'expires_at' => now()->addMinutes((int) ($payment->package?->duration_in_minutes ?? 60)),
                    'grace_period_seconds' => 300,
                    'payment_id' => $payment->id,
                ]);

                $result = $sessionManager->activateSession($session, $payment->package);

                if ($result['success']) {
                    $payment->update([
                        'status' => 'completed',
                        'completed_at' => now(),
                        'session_id' => $session->id,
                    ]);
                    
                    Log::channel('payment')->info('Session activated successfully', [
                        'payment_id' => $payment->id,
                        'session_id' => $session->id,
                        'username' => $session->username,
                    ]);
                } else {
                    // ──────────────────────────────────────────────────────────
                    // EDGE CASE #9: Session activation failed (router error)
                    // ──────────────────────────────────────────────────────────
                    Log::channel('mikrotik')->error('Session activation failed', [
                        'payment_id' => $payment->id,
                        'error' => $result['error'] ?? 'Unknown error',
                    ]);
                    
                    $payment->update([
                        'status' => 'completed',
                        'completed_at' => now(),
                        'reconciliation_notes' => 'Session activation failed: ' . ($result['error'] ?? 'Unknown'),
                    ]);
                    
                    // Queue retry for session activation
                    \App\Jobs\ActivateSession::dispatch($session)->onQueue('high');
                }

            } 
            // ──────────────────────────────────────────────────────────────────
            // PROCESS FAILED PAYMENT
            // ──────────────────────────────────────────────────────────────────
            else {
                $payment->markFailed("M-Pesa ResultCode {$resultCode}: {$resultDesc}");
                
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

    private function getRouterForTenant(int $tenantId): ?int
    {
        return \App\Models\Router::where('tenant_id', $tenantId)
            ->where('status', 'online')
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
