<?php

namespace App\Services\Mpesa;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DarajaService
{
    protected string $consumerKey;
    protected string $consumerSecret;
    protected string $passkey;
    protected string $businessShortcode;
    protected string $callbackUrl;
    protected string $environment;
    protected string $baseUrl;
    protected string $partyB;
    protected string $transactionType;
    protected int $timeout;
    protected float $connectTimeout;
    protected bool $verifySsl;
    protected ?string $caBundlePath;

    public function __construct(?array $overrides = null)
    {
        $overrides = $overrides ?? [];

        $this->consumerKey = (string) ($overrides['consumer_key'] ?? config('services.mpesa.consumer_key', ''));
        $this->consumerSecret = (string) ($overrides['consumer_secret'] ?? config('services.mpesa.consumer_secret', ''));
        $this->passkey = (string) ($overrides['passkey'] ?? config('services.mpesa.passkey', ''));
        $this->businessShortcode = (string) ($overrides['business_shortcode'] ?? config('services.mpesa.business_shortcode', ''));
        $this->callbackUrl = (string) ($overrides['callback_url'] ?? config('services.mpesa.callback_url', ''));
        $this->partyB = (string) ($overrides['partyb'] ?? config('services.mpesa.partyb', ''));
        $this->environment = strtolower((string) ($overrides['env'] ?? config('services.mpesa.env', 'sandbox')));
        $transactionType = (string) ($overrides['transaction_type'] ?? config('services.mpesa.transaction_type', 'CustomerBuyGoodsOnline'));
        $this->transactionType = in_array($transactionType, ['CustomerBuyGoodsOnline', 'CustomerPayBillOnline'], true)
            ? $transactionType
            : 'CustomerBuyGoodsOnline';
        $this->timeout = (int) ($overrides['timeout'] ?? config('services.mpesa.timeout', 30));
        $this->connectTimeout = $this->normalizeTimeout(
            $overrides['connect_timeout'] ?? config('services.mpesa.connect_timeout', 5),
            5.0
        );
        $this->verifySsl = $this->normalizeBoolean(
            $overrides['verify_ssl'] ?? config('services.mpesa.verify_ssl', true),
            true
        );
        $caBundlePath = trim((string) ($overrides['ca_bundle'] ?? config('services.mpesa.ca_bundle', '')));
        $this->caBundlePath = $caBundlePath !== '' ? $caBundlePath : null;

        if (trim($this->partyB) === '') {
            $this->partyB = $this->businessShortcode;
        }

        $configuredBaseUrl = (string) ($overrides['base_url'] ?? '');
        if ($configuredBaseUrl !== '') {
            $this->baseUrl = rtrim($configuredBaseUrl, '/');
            return;
        }

        $this->baseUrl = $this->environment === 'live'
            ? rtrim((string) config('services.mpesa.live_url', 'https://api.safaricom.co.ke'), '/')
            : rtrim((string) config('services.mpesa.sandbox_url', 'https://sandbox.safaricom.co.ke'), '/');
    }

    public function isConfigured(): bool
    {
        return $this->hasCredentialPair()
            && trim($this->passkey) !== ''
            && trim($this->businessShortcode) !== '';
    }

    public function hasCredentialPair(): bool
    {
        return trim($this->consumerKey) !== '' && trim($this->consumerSecret) !== '';
    }

    public function testConnection(): array
    {
        if (!$this->hasCredentialPair()) {
            return [
                'success' => false,
                'message' => 'Consumer Key and Consumer Secret are required.',
                'error' => 'missing_credentials',
            ];
        }

        $token = $this->requestAccessToken();
        if (!$token['success']) {
            return [
                'success' => false,
                'message' => (string) ($token['error'] ?? 'Daraja authentication failed'),
                'error' => (string) ($token['error'] ?? 'token_error'),
                'http_status' => $token['http_status'] ?? null,
            ];
        }

        return [
            'success' => true,
            'message' => 'Daraja credentials are valid.',
            'environment' => $this->environment,
            'http_status' => $token['http_status'] ?? 200,
        ];
    }

    public function stkPush(
        string $phone,
        float $amount,
        string $accountReference,
        string $description = 'CloudBridge WiFi',
        ?string $callbackUrl = null
    ): array {
        if (!$this->isConfigured()) {
            return [
                'success' => false,
                'stage' => 'configuration',
                'http_status' => null,
                'error' => 'Daraja is not fully configured (consumer credentials, passkey, shortcode, callback URL).',
                'raw' => [],
            ];
        }

        $token = $this->requestAccessToken();
        if (!$token['success']) {
            return [
                'success' => false,
                'stage' => 'oauth',
                'http_status' => $token['http_status'] ?? null,
                'error' => (string) ($token['error'] ?? 'Failed to authenticate with Daraja'),
                'raw' => [],
            ];
        }

        $stkPhone = $this->normalizePhone($phone);
        $timestamp = now()->format('YmdHis');
        $password = base64_encode($this->businessShortcode . $this->passkey . $timestamp);
        $reference = $this->sanitizeAccountReference($accountReference);
        $stkCallbackUrl = trim((string) ($callbackUrl ?? $this->callbackUrl));

        if ($stkCallbackUrl === '') {
            return [
                'success' => false,
                'stage' => 'configuration',
                'http_status' => null,
                'error' => 'Daraja callback URL is required.',
                'raw' => [],
            ];
        }

        $payload = [
            'BusinessShortCode' => $this->businessShortcode,
            'Password' => $password,
            'Timestamp' => $timestamp,
            'TransactionType' => $this->transactionType,
            'Amount' => max(1, (int) round($amount)),
            'PartyA' => $stkPhone,
            'PartyB' => $this->partyB,
            'PhoneNumber' => $stkPhone,
            'CallBackURL' => $stkCallbackUrl,
            'AccountReference' => $reference,
            'TransactionDesc' => $this->sanitizeTransactionDescription($description),
        ];

        try {
            $response = $this->requestForStage('stk_push')
                ->withToken((string) $token['access_token'])
                ->acceptJson()
                ->asJson()
                ->post($this->baseUrl . '/mpesa/stkpush/v1/processrequest', $payload);

            $result = $response->json() ?? [];
            $responseCode = (string) ($result['ResponseCode'] ?? '');
            $success = $response->successful()
                && $responseCode === '0'
                && !empty($result['CheckoutRequestID']);

            Log::channel('payment')->info('Daraja STK push attempt', [
                'environment' => $this->environment,
                'transaction_type' => $this->transactionType,
                'business_shortcode' => $this->businessShortcode,
                'party_b' => $this->partyB,
                'account_reference' => $reference,
                'response_code' => $responseCode,
                'http_status' => $response->status(),
                'checkout_request_id' => $result['CheckoutRequestID'] ?? null,
                'merchant_request_id' => $result['MerchantRequestID'] ?? null,
            ]);

            return [
                'success' => $success,
                'stage' => 'stk_push',
                'http_status' => $response->status(),
                'response_code' => $responseCode !== '' ? $responseCode : null,
                'response_description' => (string) ($result['ResponseDescription'] ?? ''),
                'customer_message' => (string) ($result['CustomerMessage'] ?? ''),
                'checkout_request_id' => $result['CheckoutRequestID'] ?? null,
                'merchant_request_id' => $result['MerchantRequestID'] ?? null,
                'raw' => $result,
                'error' => $success
                    ? null
                    : (string) ($result['errorMessage'] ?? $result['ResponseDescription'] ?? 'STK push failed'),
            ];
        } catch (\Throwable $e) {
            Log::channel('payment')->error('Daraja STK push exception', [
                'environment' => $this->environment,
                'account_reference' => $reference,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'stage' => 'stk_push',
                'http_status' => null,
                'response_code' => null,
                'response_description' => '',
                'customer_message' => '',
                'checkout_request_id' => null,
                'merchant_request_id' => null,
                'raw' => [],
                'error' => 'Connection error: ' . $e->getMessage(),
            ];
        }
    }

    public function queryStkStatus(string $checkoutRequestId): array
    {
        $checkoutRequestId = trim($checkoutRequestId);
        if ($checkoutRequestId === '') {
            return [
                'success' => false,
                'final' => false,
                'is_pending' => false,
                'is_success' => false,
                'is_failed' => false,
                'response_code' => null,
                'result_code' => null,
                'result_desc' => '',
                'merchant_request_id' => null,
                'checkout_request_id' => null,
                'receipt_number' => null,
                'phone_number' => null,
                'amount' => null,
                'raw' => [],
                'error' => 'CheckoutRequestID is required.',
            ];
        }

        if (!$this->isConfigured()) {
            return [
                'success' => false,
                'final' => false,
                'is_pending' => false,
                'is_success' => false,
                'is_failed' => false,
                'response_code' => null,
                'result_code' => null,
                'result_desc' => '',
                'merchant_request_id' => null,
                'checkout_request_id' => $checkoutRequestId,
                'receipt_number' => null,
                'phone_number' => null,
                'amount' => null,
                'raw' => [],
                'error' => 'Daraja is not fully configured.',
            ];
        }

        $token = $this->requestAccessToken();
        if (!$token['success']) {
            return [
                'success' => false,
                'final' => false,
                'is_pending' => false,
                'is_success' => false,
                'is_failed' => false,
                'response_code' => null,
                'result_code' => null,
                'result_desc' => '',
                'merchant_request_id' => null,
                'checkout_request_id' => $checkoutRequestId,
                'receipt_number' => null,
                'phone_number' => null,
                'amount' => null,
                'raw' => [],
                'error' => (string) ($token['error'] ?? 'Failed to authenticate with Daraja'),
            ];
        }

        $timestamp = now()->format('YmdHis');
        $password = base64_encode($this->businessShortcode . $this->passkey . $timestamp);
        $payload = [
            'BusinessShortCode' => $this->businessShortcode,
            'Password' => $password,
            'Timestamp' => $timestamp,
            'CheckoutRequestID' => $checkoutRequestId,
        ];

        try {
            $response = $this->requestForStage('stk_query')
                ->withToken((string) $token['access_token'])
                ->acceptJson()
                ->asJson()
                ->post($this->baseUrl . '/mpesa/stkpushquery/v1/query', $payload);

            $result = $response->json() ?? [];
            $responseCode = (string) ($result['ResponseCode'] ?? '');
            $resultCodeRaw = $result['ResultCode'] ?? null;
            $resultCode = is_numeric($resultCodeRaw) ? (int) $resultCodeRaw : null;
            $resultDesc = (string) ($result['ResultDesc'] ?? '');
            $accepted = $response->successful() && $responseCode === '0';
            $isPending = $accepted && $this->isPendingQueryResult($resultCode, $resultDesc);
            $isFinal = $accepted && $resultCode !== null && !$isPending;
            $isSuccess = $isFinal && $resultCode === 0;
            $isFailed = $isFinal && $resultCode !== 0;

            Log::channel('payment')->info('Daraja STK query attempt', [
                'environment' => $this->environment,
                'business_shortcode' => $this->businessShortcode,
                'checkout_request_id' => $checkoutRequestId,
                'response_code' => $responseCode,
                'result_code' => $resultCode,
                'result_desc' => $resultDesc,
                'is_pending' => $isPending,
                'http_status' => $response->status(),
            ]);

            return [
                'success' => $accepted,
                'final' => $isFinal,
                'is_pending' => $isPending,
                'is_success' => $isSuccess,
                'is_failed' => $isFailed,
                'response_code' => $responseCode !== '' ? $responseCode : null,
                'result_code' => $resultCode,
                'result_desc' => $resultDesc,
                'merchant_request_id' => $result['MerchantRequestID'] ?? null,
                'checkout_request_id' => $result['CheckoutRequestID'] ?? $checkoutRequestId,
                'receipt_number' => $this->firstNonEmptyString([
                    isset($result['MpesaReceiptNumber']) ? (string) $result['MpesaReceiptNumber'] : null,
                    isset($result['ReceiptNumber']) ? (string) $result['ReceiptNumber'] : null,
                    isset($result['receipt_number']) ? (string) $result['receipt_number'] : null,
                ]),
                'phone_number' => $this->firstNonEmptyString([
                    isset($result['PhoneNumber']) ? (string) $result['PhoneNumber'] : null,
                    isset($result['msisdn']) ? (string) $result['msisdn'] : null,
                    isset($result['phone']) ? (string) $result['phone'] : null,
                ]),
                'amount' => isset($result['Amount']) ? (float) $result['Amount'] : (isset($result['amount']) ? (float) $result['amount'] : null),
                'raw' => $result,
                'error' => $accepted
                    ? null
                    : (string) ($result['errorMessage'] ?? $result['ResponseDescription'] ?? 'STK query failed'),
            ];
        } catch (\Throwable $e) {
            Log::channel('payment')->error('Daraja STK query exception', [
                'environment' => $this->environment,
                'checkout_request_id' => $checkoutRequestId,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'final' => false,
                'is_pending' => false,
                'is_success' => false,
                'is_failed' => false,
                'response_code' => null,
                'result_code' => null,
                'result_desc' => '',
                'merchant_request_id' => null,
                'checkout_request_id' => $checkoutRequestId,
                'receipt_number' => null,
                'phone_number' => null,
                'amount' => null,
                'raw' => [],
                'error' => 'Connection error: ' . $e->getMessage(),
            ];
        }
    }

    private function isPendingQueryResult(?int $resultCode, string $resultDesc): bool
    {
        if ($resultCode === null || $resultCode === 0) {
            return false;
        }

        $desc = strtolower(trim($resultDesc));

        if (
            str_contains($desc, 'still under process')
            || str_contains($desc, 'still under processing')
            || str_contains($desc, 'under process')
            || str_contains($desc, 'under processing')
            || str_contains($desc, 'being processed')
            || str_contains($desc, 'in progress')
            || str_contains($desc, 'processing')
        ) {
            return true;
        }

        return in_array($resultCode, [1], true);
    }

    private function requestAccessToken(): array
    {
        try {
            $response = $this->requestForStage('oauth')
                ->withBasicAuth($this->consumerKey, $this->consumerSecret)
                ->acceptJson()
                ->get($this->baseUrl . '/oauth/v1/generate', [
                    'grant_type' => 'client_credentials',
                ]);

            $result = $response->json() ?? [];
            $accessToken = (string) ($result['access_token'] ?? '');

            if ($response->successful() && $accessToken !== '') {
                return [
                    'success' => true,
                    'access_token' => $accessToken,
                    'http_status' => $response->status(),
                ];
            }

            $error = (string) ($result['errorMessage'] ?? $result['error_description'] ?? $result['error'] ?? 'Daraja OAuth failed');
            Log::channel('payment')->warning('Daraja OAuth failed', [
                'environment' => $this->environment,
                'http_status' => $response->status(),
                'error' => $error,
            ]);

            return [
                'success' => false,
                'error' => $error,
                'http_status' => $response->status(),
            ];
        } catch (\Throwable $e) {
            Log::channel('payment')->error('Daraja OAuth exception', [
                'environment' => $this->environment,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => 'Connection error: ' . $e->getMessage(),
            ];
        }
    }

    private function normalizePhone(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone) ?? '';

        if (str_starts_with($digits, '0')) {
            return '254' . substr($digits, 1);
        }

        if (str_starts_with($digits, '254')) {
            return $digits;
        }

        if (strlen($digits) === 9 && (str_starts_with($digits, '7') || str_starts_with($digits, '1'))) {
            return '254' . $digits;
        }

        return $digits;
    }

    private function sanitizeAccountReference(string $accountReference): string
    {
        $sanitized = preg_replace('/[^A-Za-z0-9\-]/', '', strtoupper($accountReference)) ?? '';
        if ($sanitized === '') {
            $sanitized = 'WIFI';
        }

        return substr($sanitized, 0, 12);
    }

    private function sanitizeTransactionDescription(string $description): string
    {
        $text = trim($description);
        if ($text === '') {
            $text = 'WiFi Payment';
        }

        return substr($text, 0, 182);
    }

    private function request(): PendingRequest
    {
        return Http::withOptions([
            'verify' => $this->caBundlePath ?? $this->verifySsl,
        ]);
    }

    private function requestForStage(string $stage): PendingRequest
    {
        return $this->request()
            ->connectTimeout($this->connectTimeout)
            ->timeout($this->timeoutForStage($stage));
    }

    private function timeoutForStage(string $stage): int
    {
        $configured = $this->configuredStageTimeout($stage);
        if ($configured !== null) {
            return $configured;
        }

        return match ($stage) {
            'oauth' => max(5, min($this->timeout, 15)),
            'stk_push' => max(10, min($this->timeout, 20)),
            'stk_query' => max(5, min($this->timeout, 15)),
            default => max(5, $this->timeout),
        };
    }

    private function configuredStageTimeout(string $stage): ?int
    {
        $configKey = match ($stage) {
            'oauth' => 'oauth_timeout',
            'stk_push' => 'stk_push_timeout',
            'stk_query' => 'stk_query_timeout',
            default => null,
        };

        if ($configKey === null) {
            return null;
        }

        $value = config("services.mpesa.{$configKey}");
        if (!is_numeric($value) || (float) $value <= 0) {
            return null;
        }

        return max(1, (int) ceil((float) $value));
    }

    private function normalizeTimeout(mixed $value, float $default): float
    {
        if (is_numeric($value) && (float) $value > 0) {
            return max(1.0, (float) $value);
        }

        return max(1.0, $default);
    }

    private function normalizeBoolean(mixed $value, bool $default): bool
    {
        $normalized = filter_var($value, FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE);

        if ($normalized !== null) {
            return $normalized;
        }

        if (is_bool($value)) {
            return $value;
        }

        if ($value === null) {
            return $default;
        }

        return (bool) $value;
    }

    /**
     * @param  array<int, string|null>  $values
     */
    private function firstNonEmptyString(array $values): ?string
    {
        foreach ($values as $value) {
            $candidate = trim((string) $value);
            if ($candidate !== '') {
                return $candidate;
            }
        }

        return null;
    }
}
