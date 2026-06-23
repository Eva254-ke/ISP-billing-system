<?php

namespace App\Services\Notifications;

use App\Models\Payment;
use App\Models\UserSession;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * WhatsApp notification service.
 *
 * ChatKazi is the default provider. The Meta Cloud API path is kept for
 * backwards compatibility when WHATSAPP_PROVIDER=meta.
 */
class WhatsAppNotificationService
{
    private const META_API_BASE = 'https://graph.facebook.com';

    private ?string $accessToken;
    private ?string $phoneNumberId;
    private ?string $apiVersion;
    private bool $enabled;
    private string $provider;

    public function __construct()
    {
        $this->enabled = (bool) config('services.whatsapp.enabled', false);
        $this->provider = strtolower((string) config('services.whatsapp.provider', 'chatkazi'));
        $this->accessToken = config('services.whatsapp.access_token');
        $this->phoneNumberId = config('services.whatsapp.phone_number_id');
        $this->apiVersion = config('services.whatsapp.api_version', 'v18.0');
    }

    /**
     * Send a WhatsApp message using an approved template (for proactive notifications).
     */
    public function sendTemplate(
        string $to,
        string $templateName,
        array $language = ['code' => 'en'],
        array $components = []
    ): bool {
        if (!$this->canSend()) {
            Log::channel('notification')->debug('WhatsApp not configured or disabled', [
                'to' => $to,
                'template' => $templateName,
            ]);

            return false;
        }

        $to = $this->normalizePhone($to);

        if ($to === null) {
            return false;
        }

        $payload = [
            'messaging_product' => 'whatsapp',
            'recipient_type' => 'individual',
            'to' => $to,
            'type' => 'template',
            'template' => [
                'name' => $templateName,
                'language' => $language,
                'components' => $components,
            ],
        ];

        return $this->dispatch($payload, $to, $templateName);
    }

    /**
     * Send a free-form text message (only works inside 24-hour window).
     */
    public function sendText(string $to, string $message, ?string $previewUrl = null): bool
    {
        if ($this->provider === 'chatkazi') {
            return $this->sendChatKaziText($to, $message);
        }

        if (!$this->canSend()) {
            Log::channel('notification')->debug('WhatsApp not configured or disabled', [
                'to' => $to,
            ]);

            return false;
        }

        $to = $this->normalizePhone($to);

        if ($to === null) {
            return false;
        }

        $payload = [
            'messaging_product' => 'whatsapp',
            'recipient_type' => 'individual',
            'to' => $to,
            'type' => 'text',
            'text' => [
                'body' => $message,
                'preview_url' => $previewUrl ?? false,
            ],
        ];

        return $this->dispatch($payload, $to, 'text');
    }

    /**
     * Router offline alert to tenant admin.
     */
    public function sendRouterOfflineAlert(string $adminPhone, string $routerName, ?string $location = null): bool
    {
        if ($this->provider === 'chatkazi') {
            return $this->sendText(
                $adminPhone,
                sprintf(
                    "CloudBridge alert: router '%s'%s is offline as of %s.",
                    $routerName,
                    $location ? " ({$location})" : '',
                    now()->format('Y-m-d H:i')
                )
            );
        }

        $template = config('services.whatsapp.templates.router_offline', 'router_offline_alert');

        return $this->sendTemplate(
            $adminPhone,
            $template,
            ['code' => 'en'],
            [
                [
                    'type' => 'body',
                    'parameters' => [
                        ['type' => 'text', 'text' => $routerName],
                        ['type' => 'text', 'text' => $location ?? 'Unknown'],
                        ['type' => 'text', 'text' => now()->format('Y-m-d H:i')],
                    ],
                ],
            ]
        );
    }

    /**
     * Router back-online alert to tenant admin.
     */
    public function sendRouterOnlineAlert(string $adminPhone, string $routerName, ?string $location = null): bool
    {
        if ($this->provider === 'chatkazi') {
            return $this->sendText(
                $adminPhone,
                sprintf(
                    "CloudBridge alert: router '%s'%s is back online as of %s.",
                    $routerName,
                    $location ? " ({$location})" : '',
                    now()->format('Y-m-d H:i')
                )
            );
        }

        $template = config('services.whatsapp.templates.router_online', 'router_online_alert');

        return $this->sendTemplate(
            $adminPhone,
            $template,
            ['code' => 'en'],
            [
                [
                    'type' => 'body',
                    'parameters' => [
                        ['type' => 'text', 'text' => $routerName],
                        ['type' => 'text', 'text' => $location ?? 'Unknown'],
                        ['type' => 'text', 'text' => now()->format('Y-m-d H:i')],
                    ],
                ],
            ]
        );
    }

    /**
     * Session expiry warning to user.
     */
    public function sendSessionExpiryWarning(
        string $userPhone,
        int $minutesRemaining,
        string $username,
        string $brand = 'WiFi'
    ): bool {
        if ($this->provider === 'chatkazi') {
            $minutes = max(0, $minutesRemaining);

            return $this->sendText(
                $userPhone,
                sprintf(
                    '%s reminder: your WiFi session for %s expires in %d minute%s. Please renew to stay connected.',
                    $brand,
                    $username,
                    $minutes,
                    $minutes === 1 ? '' : 's'
                )
            );
        }

        $template = config('services.whatsapp.templates.session_expiry', 'session_expiry_warning');

        return $this->sendTemplate(
            $userPhone,
            $template,
            ['code' => 'en'],
            [
                [
                    'type' => 'body',
                    'parameters' => [
                        ['type' => 'text', 'text' => (string) $minutesRemaining],
                        ['type' => 'text', 'text' => $username],
                        ['type' => 'text', 'text' => $brand],
                    ],
                ],
            ]
        );
    }

    /**
     * Payment receipt / access confirmation to user.
     */
    public function sendPaymentReceipt(Payment $payment, ?UserSession $session = null): bool
    {
        $to = $payment->phone ?: $payment->mpesa_phone ?: $session?->phone;

        if (!$to) {
            Log::channel('notification')->debug('No phone number for payment receipt', [
                'payment_id' => $payment->id,
                'session_id' => $session?->id,
            ]);

            return false;
        }

        $brand = $payment->tenant?->name ?? $session?->tenant?->name ?? 'CloudBridge WiFi';
        $packageName = $payment->package_name ?: $payment->package?->name ?: $session?->package?->name ?: 'WiFi package';
        $receipt = $payment->mpesa_receipt_number ?: $payment->reference ?: ('PAY-' . $payment->id);
        $amount = number_format((float) $payment->amount, 0);
        $expiry = $session?->expires_at;
        $expiryText = $expiry ? $expiry->timezone(config('app.timezone'))->format('d M Y H:i') : null;
        $isActive = $session && (string) $session->status === 'active';

        if ($isActive && $expiryText) {
            $message = sprintf(
                '%s: payment of KES %s received for %s. Receipt: %s. Your WiFi is active until %s.',
                $brand,
                $amount,
                $packageName,
                $receipt,
                $expiryText
            );
        } elseif ($expiryText) {
            $message = sprintf(
                '%s: payment of KES %s received for %s. Receipt: %s. Complete hotspot login to start using WiFi. Access is reserved until %s.',
                $brand,
                $amount,
                $packageName,
                $receipt,
                $expiryText
            );
        } else {
            $message = sprintf(
                '%s: payment of KES %s received for %s. Receipt: %s. Your WiFi access is being prepared.',
                $brand,
                $amount,
                $packageName,
                $receipt
            );
        }

        return $this->sendText($to, $message);
    }

    /**
     * Check if service is healthy (credentials present).
     */
    public function isConfigured(): bool
    {
        return $this->canSend();
    }

    private function canSend(): bool
    {
        if ($this->provider === 'chatkazi') {
            return (bool) config('services.chatkazi.enabled', $this->enabled)
                && filled(config('services.chatkazi.base_url'))
                && filled(config('services.chatkazi.api_key'));
        }

        return $this->enabled
            && $this->accessToken !== null
            && $this->accessToken !== ''
            && $this->phoneNumberId !== null
            && $this->phoneNumberId !== '';
    }

    private function normalizePhone(?string $phone): ?string
    {
        if ($phone === null || trim($phone) === '') {
            return null;
        }

        $normalized = preg_replace('/[^0-9]/', '', $phone);

        if ($normalized === null || $normalized === '') {
            return null;
        }

        // Ensure E.164 format with country code (e.g., 2547... for Kenya)
        if (strlen($normalized) < 10) {
            return null;
        }

        if (str_starts_with($normalized, '0') && strlen($normalized) === 10) {
            $normalized = '254'.substr($normalized, 1);
        }

        return $normalized;
    }

    private function buildMetaUrl(): string
    {
        return sprintf(
            '%s/%s/%s/messages',
            self::META_API_BASE,
            $this->apiVersion,
            $this->phoneNumberId
        );
    }

    private function dispatch(array $payload, string $to, string $context): bool
    {
        $url = $this->buildMetaUrl();

        try {
            $response = Http::withToken($this->accessToken, 'Bearer')
                ->timeout(15)
                ->connectTimeout(5)
                ->withHeaders([
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                ])
                ->post($url, $payload);

            if ($response->successful()) {
                Log::channel('notification')->info('WhatsApp message sent', [
                    'to' => $to,
                    'context' => $context,
                    'message_id' => $response->json('messages.0.id'),
                ]);

                return true;
            }

            $error = $response->json('error.message', $response->body());
            $code = $response->json('error.code', $response->status());

            Log::channel('notification')->error('WhatsApp message failed', [
                'to' => $to,
                'context' => $context,
                'error' => $error,
                'code' => $code,
                'response' => $response->body(),
            ]);

            return false;
        } catch (\Throwable $e) {
            Log::channel('notification')->error('WhatsApp request exception', [
                'to' => $to,
                'context' => $context,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    private function sendChatKaziText(string $to, string $message): bool
    {
        $originalTo = $to;

        if (!$this->canSend()) {
            Log::channel('notification')->debug('ChatKazi not configured or disabled', [
                'to' => $to,
            ]);

            return false;
        }

        $to = $this->normalizePhone($to);

        if ($to === null) {
            Log::channel('notification')->warning('ChatKazi recipient phone is invalid', [
                'to' => $originalTo,
            ]);

            return false;
        }

        $baseUrl = rtrim((string) config('services.chatkazi.base_url'), '/');
        $apiKey = (string) config('services.chatkazi.api_key');
        $sessionId = (string) config('services.chatkazi.session_id', 'default');

        try {
            $response = Http::timeout((int) config('services.chatkazi.timeout', 15))
                ->connectTimeout((int) config('services.chatkazi.connect_timeout', 5))
                ->withHeaders([
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                    'x-api-key' => $apiKey,
                ])
                ->post("{$baseUrl}/messages/text", [
                    'sessionId' => $sessionId ?: 'default',
                    'to' => $to,
                    'text' => $message,
                ]);

            if ($response->successful()) {
                Log::channel('notification')->info('ChatKazi message sent', [
                    'to' => $to,
                    'session_id' => $sessionId ?: 'default',
                    'message_id' => $response->json('data.key.id'),
                ]);

                return true;
            }

            Log::channel('notification')->error('ChatKazi message failed', [
                'to' => $to,
                'status' => $response->status(),
                'message' => $response->json('message'),
                'error' => $response->json('error'),
                'response' => $response->body(),
            ]);

            return false;
        } catch (\Throwable $e) {
            Log::channel('notification')->error('ChatKazi request exception', [
                'to' => $to,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }
}
