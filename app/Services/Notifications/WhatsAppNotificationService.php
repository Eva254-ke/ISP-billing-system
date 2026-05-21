<?php

namespace App\Services\Notifications;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * WhatsApp Cloud API (Meta) notification service.
 *
 * Requires:
 *   - Meta Business account
 *   - WhatsApp Business account
 *   - Verified phone number
 *   - Permanent access token
 *
 * For proactive notifications (router alerts, session expiry), you must use
 * approved message templates. Free-form text only works within the 24-hour
 * customer-service window.
 *
 * Create templates in the Meta Business Manager with these exact names:
 *   - router_offline_alert
 *   - router_online_alert
 *   - session_expiry_warning
 *
 * Template variables:
 *   - router_offline_alert: {{1}}=router_name, {{2}}=location, {{3}}=time
 *   - router_online_alert:  {{1}}=router_name, {{2}}=location, {{3}}=time
 *   - session_expiry_warning: {{1}}=minutes, {{2}}=username, {{3}}=brand
 */
class WhatsAppNotificationService
{
    private const API_BASE = 'https://graph.facebook.com';

    private ?string $accessToken;
    private ?string $phoneNumberId;
    private ?string $apiVersion;
    private bool $enabled;

    public function __construct()
    {
        $this->enabled = (bool) config('services.whatsapp.enabled', false);
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
     * Check if service is healthy (credentials present).
     */
    public function isConfigured(): bool
    {
        return $this->canSend();
    }

    private function canSend(): bool
    {
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

        return $normalized;
    }

    private function buildUrl(): string
    {
        return sprintf(
            '%s/%s/%s/messages',
            self::API_BASE,
            $this->apiVersion,
            $this->phoneNumberId
        );
    }

    private function dispatch(array $payload, string $to, string $context): bool
    {
        $url = $this->buildUrl();

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
}
