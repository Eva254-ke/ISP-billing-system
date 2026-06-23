<?php

namespace App\Jobs;

use App\Models\Payment;
use App\Models\UserSession;
use App\Services\IntaSend\IntaSendService;
use App\Services\MikroTik\SessionManager;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProcessIntaSendCallback implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $backoff = [10, 30, 60];

    public function __construct(
        protected array $callbackData
    ) {}

    public function handle(
        IntaSendService $intasend,
        SessionManager $sessionManager
    ): void {
        $data = $this->callbackData;
        $reference = $data['reference'] ?? null;
        $status = $data['status'] ?? null;
        $amount = $data['amount'] ?? 0;

        Log::channel('payment')->info('Processing IntaSend callback', [
            'reference' => $reference,
            'status' => $status,
        ]);

        if (!$reference || !$status) {
            Log::channel('payment')->warning('Invalid callback data', ['data' => $data]);
            return;
        }

        // Find payment (idempotent)
        $payment = Payment::where('intasend_reference', $reference)
            ->orWhere('reference', $reference)
            ->lockForUpdate()
            ->first();

        if (!$payment) {
            Log::channel('payment')->warning('Payment not found for reference', ['reference' => $reference]);
            return;
        }

        // Skip if already processed
        if ($payment->status === 'completed' || $payment->status === 'payout_sent') {
            Log::channel('payment')->info('Payment already processed', ['payment_id' => $payment->id]);
            return;
        }

        DB::transaction(function () use ($payment, $status, $amount, $intasend, $sessionManager) {
            
            if ($status === 'completed') {
                // Calculate amounts
                $fee = $amount * 0.01; // 1% IntaSend fee
                $netAmount = $amount - $fee;
                $commission = $payment->tenant->calculateCommission($netAmount);
                $tenantShare = $netAmount - $commission;

                // Update payment
                $payment->update([
                    'status' => 'completed',
                    'amount' => $amount,
                    'fee' => $fee,
                    'net_amount' => $netAmount,
                    'commission_amount' => $commission,
                    'tenant_share' => $tenantShare,
                    'paid_at' => now(),
                    'response_data' => json_encode($this->callbackData),
                ]);

                // Activate WiFi session
                $session = $payment->session;
                if ($session && $session->status === 'pending') {
                    $session->update(['status' => 'paid', 'paid_at' => now()]);
                    $sessionManager->activateSession($session);
                }

                Log::channel('payment')->info('Payment completed & WiFi activated', [
                    'payment_id' => $payment->id,
                    'amount' => $amount,
                    'tenant_share' => $tenantShare,
                ]);

                // ──────────────────────────────────────────────────────────
                // TRIGGER AUTO-PAYOUT TO TENANT'S TILL (Real-time settlement)
                // ──────────────────────────────────────────────────────────
                if ($payment->tenant->till_number && $tenantShare > 0) {
                    $payoutResult = $intasend->payoutToTill(
                        tillNumber: $payment->tenant->till_number,
                        amount: $tenantShare,
                        narration: "CloudBridge - {$payment->reference}",
                        reference: "PAYOUT-{$payment->id}"
                    );

                    if ($payoutResult['success']) {
                        $payment->update([
                            'status' => 'payout_sent',
                            'payout_reference' => $payoutResult['payout_reference'] ?? null,
                            'payout_sent_at' => now(),
                        ]);
                        
                        Log::channel('payment')->info('Payout sent to tenant', [
                            'payment_id' => $payment->id,
                            'till' => $payment->tenant->till_number,
                            'amount' => $tenantShare,
                        ]);
                    } else {
                        Log::channel('payment')->error('Payout failed', [
                            'payment_id' => $payment->id,
                            'error' => $payoutResult['error'],
                        ]);
                        // Don't fail the job - payout can be retried manually
                    }
                }

            } elseif ($status === 'failed' || $status === 'cancelled') {
                $payment->update([
                    'status' => 'failed',
                    'failed_at' => now(),
                    'response_data' => json_encode($this->callbackData),
                ]);

                Log::channel('payment')->info('Payment failed', [
                    'payment_id' => $payment->id,
                    'status' => $status,
                ]);
            }
        });
    }

    public function failed(\Throwable $exception): void
    {
        Log::channel('payment')->error('IntaSend callback job failed', [
            'reference' => $this->callbackData['reference'] ?? null,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);
    }
}