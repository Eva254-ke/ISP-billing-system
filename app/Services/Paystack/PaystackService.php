<?php

namespace App\Services\Paystack;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PaystackService
{
    protected string $secretKey;
    protected string $baseUrl;
    protected string $mobileMoneyProvider;

    public function __construct()
    {
        $this->secretKey = (string) config('services.paystack.secret_key', '');
        $this->baseUrl = rtrim((string) config('services.paystack.base_url', 'https://api.paystack.co'), '/');
        $this->mobileMoneyProvider = (string) config('services.paystack.mobile_money_provider', 'mpesa');
    }

    public function isConfigured(): bool
    {
        $secret = strtolower(trim($this->secretKey));
        if ($secret === '') {
            return false;
        }

        return !in_array($secret, ['your_secret_key_here', 'changeme'], true);
    }

    public function chargeMobileMoney(
        string $phone,
        float $amount,
        string $reference,
        string $email,
        string $narration = 'CloudBridge WiFi',
        array $metadata = []
    ): array {
        try {
            $payload = [
                'email' => $email,
                'amount' => $this->toSubunit($amount),
                'currency' => 'KES',
                'reference' => $reference,
                'mobile_money' => [
                    'phone' => $phone,
                    'provider' => $this->mobileMoneyProvider,
                ],
                'metadata' => array_merge($metadata, [
                    'narration' => $narration,
                    'gateway' => 'paystack',
                ]),
            ];

            $response = Http::withToken($this->secretKey)
                ->acceptJson()
                ->asJson()
                ->timeout(30)
                ->post($this->baseUrl . '/charge', $payload);

            $result = $response->json() ?? [];
            $data = (array) ($result['data'] ?? []);
            $status = strtolower((string) ($data['status'] ?? ''));

            $isInitiated = $response->successful()
                && (($result['status'] ?? false) === true)
                && !in_array($status, ['failed', 'abandoned', 'cancelled', 'canceled', 'reversed'], true);

            Log::channel('payment')->info('Paystack mobile money charge attempt', [
                'reference' => $reference,
                'status' => $status,
                'http_status' => $response->status(),
                'message' => $result['message'] ?? null,
            ]);

            return [
                'success' => $isInitiated,
                'status' => $status !== '' ? $status : 'unknown',
                'reference' => (string) ($data['reference'] ?? $reference),
                'transaction_id' => isset($data['id']) ? (string) $data['id'] : null,
                'gateway_response' => (string) ($data['gateway_response'] ?? $data['message'] ?? $result['message'] ?? ''),
                'raw' => $result,
                'error' => $isInitiated ? null : (string) ($data['message'] ?? $result['message'] ?? 'Charge failed'),
            ];
        } catch (\Throwable $e) {
            Log::channel('payment')->error('Paystack charge exception', [
                'reference' => $reference,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'status' => 'error',
                'reference' => $reference,
                'transaction_id' => null,
                'gateway_response' => '',
                'raw' => [],
                'error' => 'Connection error: ' . $e->getMessage(),
            ];
        }
    }

    public function verifyTransaction(string $reference): array
    {
        try {
            $response = Http::withToken($this->secretKey)
                ->acceptJson()
                ->timeout(30)
                ->get($this->baseUrl . '/transaction/verify/' . urlencode($reference));

            $result = $response->json() ?? [];
            $data = (array) ($result['data'] ?? []);
            $status = strtolower((string) ($data['status'] ?? ''));

            $isSuccessfulRequest = $response->successful() && (($result['status'] ?? false) === true);

            return [
                'success' => $isSuccessfulRequest,
                'payment_status' => $status !== '' ? $status : 'unknown',
                'is_paid' => in_array($status, ['success'], true),
                'is_failed' => in_array($status, ['failed', 'abandoned', 'cancelled', 'canceled', 'reversed'], true),
                'reference' => (string) ($data['reference'] ?? $reference),
                'transaction_id' => isset($data['id']) ? (string) $data['id'] : null,
                'gateway_response' => (string) ($data['gateway_response'] ?? $result['message'] ?? ''),
                'paid_at' => $data['paid_at'] ?? ($data['paidAt'] ?? null),
                'raw' => $result,
                'error' => $isSuccessfulRequest ? null : (string) ($result['message'] ?? 'Verification failed'),
            ];
        } catch (\Throwable $e) {
            Log::channel('payment')->error('Paystack verify exception', [
                'reference' => $reference,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'payment_status' => 'error',
                'is_paid' => false,
                'is_failed' => false,
                'reference' => $reference,
                'transaction_id' => null,
                'gateway_response' => '',
                'paid_at' => null,
                'raw' => [],
                'error' => 'Verification error: ' . $e->getMessage(),
            ];
        }
    }

    public function verifyWebhookSignature(string $payload, ?string $signature): bool
    {
        if (!$signature || trim($signature) === '' || trim($this->secretKey) === '') {
            return false;
        }

        $expected = hash_hmac('sha512', $payload, $this->secretKey);
        return hash_equals($expected, trim($signature));
    }

    private function toSubunit(float $amount): int
    {
        return (int) round($amount * 100);
    }
}
