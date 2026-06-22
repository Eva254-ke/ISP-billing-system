<?php

namespace App\Services\IntaSend;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class IntaSendService
{
    protected string $publicKey;
    protected string $secretKey;
    protected string $baseUrl;
    protected string $webhookSecret;

    public function __construct()
    {
        $this->publicKey = config('services.intasend.public_key');
        $this->secretKey = config('services.intasend.secret_key');
        $this->webhookSecret = config('services.intasend.webhook_secret');
        $this->baseUrl = config('services.intasend.env') === 'live'
            ? 'https://payment.intasend.com/api/v1/'
            : 'https://sandbox.intasend.com/api/v1/';
    }

    /**
     * Initiate STK Push payment
     */
    public function stkPush(
        string $phone,
        float $amount,
        string $accountRef,
        string $narration = 'CloudBridge WiFi',
        string $callbackUrl = null
    ): array {
        try {
            $payload = [
                'public_key' => $this->publicKey,
                'phone_number' => $phone,
                'amount' => $amount,
                'currency' => 'KES',
                'account_ref' => $accountRef,
                'narration' => $narration,
            ];

            if ($callbackUrl) {
                $payload['callback_url'] = $callbackUrl;
            }

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->secretKey,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ])->timeout(30)->post($this->baseUrl . 'checkout/', $payload);

            $result = $response->json();

            Log::channel('intasend')->info('STK Push request', [
                'phone' => $phone,
                'amount' => $amount,
                'response' => $result,
            ]);

            if (isset($result['status']) && $result['status'] === 'success') {
                return [
                    'success' => true,
                    'reference' => $result['reference'] ?? null,
                    'checkout_request_id' => $result['checkout_request_id'] ?? null,
                    'customer_message' => 'Enter your M-Pesa PIN to complete payment',
                ];
            }

            return [
                'success' => false,
                'error' => $result['message'] ?? $result['error'] ?? 'Unknown error',
                'code' => $result['code'] ?? null,
            ];

        } catch (\Exception $e) {
            Log::channel('intasend')->error('STK Push exception', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'error' => 'Connection error: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Payout to tenant's M-Pesa Till (auto-settlement)
     */
    public function payoutToTill(
        string $tillNumber,
        float $amount,
        string $narration = 'CloudBridge Settlement',
        string $reference = null
    ): array {
        try {
            $payload = [
                'public_key' => $this->publicKey,
                'phone_number' => $tillNumber,
                'amount' => $amount,
                'currency' => 'KES',
                'narration' => $narration,
            ];

            if ($reference) {
                $payload['reference'] = $reference;
            }

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->secretKey,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ])->timeout(30)->post($this->baseUrl . 'payouts/', $payload);

            $result = $response->json();

            Log::channel('intasend')->info('Payout request', [
                'till' => $tillNumber,
                'amount' => $amount,
                'response' => $result,
            ]);

            if (isset($result['status']) && $result['status'] === 'success') {
                return [
                    'success' => true,
                    'payout_reference' => $result['reference'] ?? null,
                    'message' => 'Payout initiated',
                ];
            }

            return [
                'success' => false,
                'error' => $result['message'] ?? $result['error'] ?? 'Payout failed',
            ];

        } catch (\Exception $e) {
            Log::channel('intasend')->error('Payout exception', [
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => 'Payout error: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Verify payment status by reference
     */
    public function verifyPayment(string $reference): array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->secretKey,
                'Accept' => 'application/json',
            ])->get($this->baseUrl . 'transactions/' . $reference);

            return $response->json();

        } catch (\Exception $e) {
            Log::channel('intasend')->error('Verify exception', [
                'error' => $e->getMessage(),
            ]);

            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }
}