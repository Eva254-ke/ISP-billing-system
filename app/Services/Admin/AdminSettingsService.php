<?php

namespace App\Services\Admin;

use App\Models\Payment;
use App\Models\Tenant;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Str;

class AdminSettingsService
{
    /**
     * @var array<int, string>
     */
    private const BOOLEAN_FIELDS = [
        'tax_enabled',
        'receipt_enabled',
    ];

    /**
     * @var array<int, string>
     */
    private const INTEGER_FIELDS = [
        'mpesa_timeout',
        'mail_port',
        'invoice_next_number',
        'invoice_terms',
        'mikrotik_port',
        'mikrotik_timeout',
        'mikrotik_retry',
        'radius_port',
        'radius_acct_port',
        'radius_timeout',
        'sys_session_timeout',
        'sys_upload_max',
    ];

    /**
     * @var array<int, string>
     */
    private const DECIMAL_FIELDS = [
        'tax_rate',
    ];

    public function getSettings(?Tenant $tenant): array
    {
        $settings = array_merge(
            $this->defaults(),
            $this->globalSettings(),
            $this->storedSettings($tenant),
        );

        if ($tenant) {
            $settings['brand_name'] = trim((string) ($tenant->name ?: $settings['brand_name']));
            $settings['brand_portal_name'] = trim((string) ($tenant->captive_portal_title ?: $settings['brand_portal_name']));
            $settings['brand_logo'] = trim((string) ($tenant->logo_url ?: $settings['brand_logo']));
            $settings['brand_primary'] = $this->normalizeHexColor((string) ($tenant->brand_color_primary ?: $settings['brand_primary']), '#1E40AF');
            $settings['brand_primary_text'] = $settings['brand_primary'];
            $settings['brand_secondary'] = $this->normalizeHexColor((string) ($tenant->brand_color_secondary ?: $settings['brand_secondary']), '#38BDF8');
            $settings['brand_secondary_text'] = $settings['brand_secondary'];
            $settings['brand_welcome'] = trim((string) ($tenant->captive_portal_welcome_message ?: $settings['brand_welcome']));
            $settings['brand_terms'] = trim((string) ($tenant->captive_portal_terms_url ?: $settings['brand_terms']));
            $settings['brand_support'] = $this->formatSupportContact(
                phone: (string) ($tenant->captive_portal_support_phone ?: ''),
                email: (string) ($tenant->captive_portal_support_email ?: ''),
                fallback: (string) $settings['brand_support'],
            );
            $settings['sys_timezone'] = trim((string) ($tenant->timezone ?: $settings['sys_timezone']));
            $settings['sys_currency'] = trim((string) ($tenant->currency ?: $settings['sys_currency']));
            $settings['sys_currency_symbol'] = trim((string) ($settings['sys_currency_symbol'] ?: $tenant->currency ?: 'KES'));
            $settings['sys_session_timeout'] = (string) max(
                5,
                (int) ($tenant->captive_portal_session_timeout_minutes ?: $settings['sys_session_timeout'])
            );
            $settings['mpesa_till'] = trim((string) ($tenant->till_number ?: $settings['mpesa_till']));
            $settings['mpesa_shortcode'] = trim((string) ($tenant->payment_shortcode ?: $settings['mpesa_shortcode']));
        }

        $settings['brand_accent'] = $this->normalizeHexColor((string) $settings['brand_accent'], $settings['brand_secondary']);
        $settings['brand_accent_text'] = $settings['brand_accent'];
        $settings['mpesa_callback'] = url('/api/mpesa/callback');

        return $settings;
    }

    public function save(?Tenant $tenant, array $input): array
    {
        $settings = $this->sanitize(array_merge($this->getSettings($tenant), $input));
        $settings['mpesa_callback'] = url('/api/mpesa/callback');
        $settings['brand_primary_text'] = $settings['brand_primary'];
        $settings['brand_secondary_text'] = $settings['brand_secondary'];
        $settings['brand_accent_text'] = $settings['brand_accent'];

        if ($tenant) {
            $tenantSettings = (array) ($tenant->settings ?? []);
            $tenantSettings['admin_settings'] = $settings;
            $tenantSettings['billing'] = $this->extractBillingSettings($settings);
            $tenantSettings['branding'] = $this->extractBrandingSettings($settings);
            $tenantSettings['system'] = $this->extractSystemSettings($settings);
            $tenantSettings['communications'] = $this->extractCommunicationSettings($settings);
            $tenantSettings['mpesa'] = $this->extractMpesaSettings($settings);
            $tenantSettings['mikrotik'] = $this->extractMikrotikSettings($settings);

            [$supportPhone, $supportEmail] = $this->splitSupportContact((string) $settings['brand_support']);

            $tenant->fill([
                'name' => trim((string) $settings['brand_name']) !== '' ? trim((string) $settings['brand_name']) : $tenant->name,
                'timezone' => trim((string) $settings['sys_timezone']) !== '' ? trim((string) $settings['sys_timezone']) : $tenant->timezone,
                'currency' => trim((string) $settings['sys_currency']) !== '' ? trim((string) $settings['sys_currency']) : $tenant->currency,
                'logo_url' => trim((string) $settings['brand_logo']) !== '' ? trim((string) $settings['brand_logo']) : null,
                'brand_color_primary' => $settings['brand_primary'],
                'brand_color_secondary' => $settings['brand_secondary'],
                'captive_portal_title' => trim((string) $settings['brand_portal_name']) !== '' ? trim((string) $settings['brand_portal_name']) : null,
                'captive_portal_welcome_message' => trim((string) $settings['brand_welcome']) !== '' ? trim((string) $settings['brand_welcome']) : null,
                'captive_portal_terms_url' => trim((string) $settings['brand_terms']) !== '' ? trim((string) $settings['brand_terms']) : null,
                'captive_portal_support_phone' => $supportPhone,
                'captive_portal_support_email' => $supportEmail,
                'captive_portal_session_timeout_minutes' => max(5, (int) $settings['sys_session_timeout']),
                'payment_shortcode' => trim((string) $settings['mpesa_shortcode']) !== '' ? trim((string) $settings['mpesa_shortcode']) : null,
                'till_number' => trim((string) $settings['mpesa_till']) !== '' ? trim((string) $settings['mpesa_till']) : null,
                'settings' => $tenantSettings,
            ]);

            if (!$tenant->payment_method && ($tenant->payment_shortcode || $tenant->till_number)) {
                $tenant->payment_method = Tenant::PAYMENT_METHOD_TILL;
            }

            $tenant->save();

            return $this->getSettings($tenant->fresh());
        }

        cache()->forever('admin_settings_global', $settings);

        return $settings;
    }

    /**
     * @return array<string, mixed>
     */
    public function previewSettings(?Tenant $tenant, array $input): array
    {
        $settings = $this->sanitize(array_merge($this->getSettings($tenant), $input));
        $settings['mpesa_callback'] = url('/api/mpesa/callback');
        $settings['brand_primary_text'] = $settings['brand_primary'];
        $settings['brand_secondary_text'] = $settings['brand_secondary'];
        $settings['brand_accent_text'] = $settings['brand_accent'];

        return $settings;
    }

    /**
     * @return array<string, mixed>
     */
    public function runtimeStatus(): array
    {
        $connection = DB::connection();
        $databaseName = $connection->getDatabaseName();
        $git = $this->gitSummary();

        return [
            'app_version' => app()->version(),
            'php_version' => PHP_VERSION,
            'database_driver' => $connection->getDriverName(),
            'database_name' => $databaseName ?: 'default',
            'app_env' => (string) config('app.env', 'production'),
            'app_debug' => (bool) config('app.debug', false),
            'cache_driver' => (string) config('cache.default', 'file'),
            'queue_connection' => (string) config('queue.default', 'sync'),
            'timezone' => (string) config('app.timezone', 'UTC'),
            'git_branch' => $git['branch'],
            'git_commit' => $git['commit'],
            'git_last_commit_at' => $git['last_commit_at'],
            'git_last_commit_label' => $git['last_commit_label'],
            'git_dirty' => $git['dirty'],
            'update_channel_configured' => false,
            'update_summary' => 'Automatic update checks are not configured. Use this panel to inspect the current deployed build safely.',
        ];
    }

    /**
     * @return array{message: string, output: string}
     */
    public function clearCaches(): array
    {
        Artisan::call('optimize:clear');

        return [
            'message' => 'Application caches cleared successfully.',
            'output' => trim((string) Artisan::output()),
        ];
    }

    /**
     * @return array{url: string, filename: string}
     */
    public function storeBrandAsset(?Tenant $tenant, UploadedFile $file, string $target): array
    {
        $directory = public_path('uploads/branding');
        File::ensureDirectoryExists($directory);

        $extension = strtolower((string) ($file->getClientOriginalExtension() ?: $file->extension() ?: 'png'));
        $extension = $target === 'favicon' && $extension === 'ico' ? 'ico' : $extension;
        $filename = sprintf(
            'tenant-%s-%s-%s.%s',
            $tenant?->id ?: 'global',
            $target,
            now()->format('YmdHis'),
            $extension
        );

        $file->move($directory, $filename);

        $relative = 'uploads/branding/' . $filename;

        return [
            'url' => asset($relative),
            'filename' => $filename,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function invoicePreviewContext(?Tenant $tenant): array
    {
        $payment = Payment::query()
            ->when($tenant, fn ($query) => $query->where('tenant_id', $tenant->id))
            ->whereIn('status', ['confirmed', 'completed', 'activated'])
            ->latest('created_at')
            ->first();

        return [
            'portal_preview_url' => $tenant
                ? route('wifi.packages', ['tenant_id' => $tenant->id])
                : route('wifi.packages'),
            'latest_payment_invoice_url' => $payment
                ? route('admin.payments.invoice', $payment)
                : null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function gitSummary(): array
    {
        if (!is_dir(base_path('.git'))) {
            return [
                'branch' => 'Unavailable',
                'commit' => 'Unavailable',
                'last_commit_at' => null,
                'last_commit_label' => 'Unavailable',
                'dirty' => null,
            ];
        }

        $branch = trim((string) Process::path(base_path())->run(['git', 'branch', '--show-current'])->output());
        $commit = trim((string) Process::path(base_path())->run(['git', 'rev-parse', '--short', 'HEAD'])->output());
        $lastCommitAt = trim((string) Process::path(base_path())->run(['git', 'log', '-1', '--format=%cI'])->output());
        $dirtyOutput = trim((string) Process::path(base_path())->run(['git', 'status', '--short'])->output());

        $lastCommitLabel = 'Unavailable';
        if ($lastCommitAt !== '') {
            try {
                $lastCommitLabel = Carbon::parse($lastCommitAt)
                    ->timezone(config('app.timezone', 'UTC'))
                    ->format('d M Y, H:i:s');
            } catch (\Throwable) {
                $lastCommitLabel = $lastCommitAt;
            }
        }

        return [
            'branch' => $branch !== '' ? $branch : 'Unavailable',
            'commit' => $commit !== '' ? $commit : 'Unavailable',
            'last_commit_at' => $lastCommitAt !== '' ? $lastCommitAt : null,
            'last_commit_label' => $lastCommitLabel,
            'dirty' => $dirtyOutput !== '' ? true : false,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function defaults(): array
    {
        return [
            'mpesa_env' => 'production',
            'mpesa_till' => '',
            'mpesa_key' => '',
            'mpesa_secret' => '',
            'mpesa_passkey' => '',
            'mpesa_callback' => url('/api/mpesa/callback'),
            'mpesa_shortcode' => '',
            'mpesa_timeout' => '60',
            'sms_provider' => 'africastalking',
            'sms_sender' => 'CloudBridge',
            'sms_username' => '',
            'sms_apikey' => '',
            'sms_payment_success' => 'Payment confirmed for {package}. Your internet is active until {expiry}.',
            'sms_payment_failed' => 'Payment verification failed. If money was deducted, use reconnect or contact support.',
            'sms_voucher' => 'Voucher {voucher} is ready. Package: {package}.',
            'mail_driver' => 'smtp',
            'mail_encryption' => 'tls',
            'mail_host' => '',
            'mail_port' => '587',
            'mail_username' => '',
            'mail_password' => '',
            'mail_from_address' => '',
            'mail_from_name' => '',
            'tax_enabled' => true,
            'tax_label' => 'VAT',
            'tax_rate' => '16',
            'tax_inclusive' => 'inclusive',
            'tax_number' => '',
            'invoice_template' => 'modern',
            'invoice_prefix' => 'INV-',
            'invoice_next_number' => '10001',
            'invoice_address' => "CloudBridge Networks\nNairobi, Kenya",
            'invoice_footer_note' => 'Thank you for your business. Payments are due upon receipt.',
            'invoice_terms' => '0',
            'invoice_email' => '',
            'receipt_enabled' => true,
            'receipt_bcc' => '',
            'receipt_subject' => 'Your CloudBridge receipt',
            'receipt_body' => "Hello {name},\n\nWe've received your payment of {amount}. Your service is active until {expiry}.\n\nThank you for choosing CloudBridge Networks.",
            'brand_name' => 'CloudBridge Networks',
            'brand_portal_name' => 'CloudBridge WiFi',
            'brand_logo' => '',
            'brand_favicon' => '',
            'brand_primary' => '#1E40AF',
            'brand_primary_text' => '#1E40AF',
            'brand_secondary' => '#38BDF8',
            'brand_secondary_text' => '#38BDF8',
            'brand_accent' => '#14B8A6',
            'brand_accent_text' => '#14B8A6',
            'brand_welcome' => 'Welcome to CloudBridge Networks WiFi! Enjoy fast, reliable internet.',
            'brand_terms' => '',
            'brand_support' => '',
            'mikrotik_port' => '8728',
            'mikrotik_timeout' => '30',
            'mikrotik_username' => 'admin',
            'mikrotik_retry' => '3',
            'radius_server' => '',
            'radius_port' => '1812',
            'radius_secret' => '',
            'radius_acct_port' => '1813',
            'radius_timeout' => '5',
            'mikrotik_profile_template' => '',
            'sys_timezone' => (string) config('app.timezone', 'Africa/Nairobi'),
            'sys_date_format' => 'Y-m-d H:i:s',
            'sys_currency' => 'KES',
            'sys_currency_symbol' => 'KES',
            'sys_session_timeout' => '120',
            'sys_upload_max' => '64',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function globalSettings(): array
    {
        return (array) cache()->get('admin_settings_global', []);
    }

    /**
     * @return array<string, mixed>
     */
    private function storedSettings(?Tenant $tenant): array
    {
        if (!$tenant) {
            return [];
        }

        $tenantSettings = (array) ($tenant->settings ?? []);
        $adminSettings = (array) Arr::get($tenantSettings, 'admin_settings', []);
        $billingSettings = (array) Arr::get($tenantSettings, 'billing', []);
        $brandingSettings = (array) Arr::get($tenantSettings, 'branding', []);
        $systemSettings = (array) Arr::get($tenantSettings, 'system', []);
        $communicationSettings = (array) Arr::get($tenantSettings, 'communications', []);
        $mpesaSettings = (array) Arr::get($tenantSettings, 'mpesa', []);
        $mikrotikSettings = (array) Arr::get($tenantSettings, 'mikrotik', []);

        return array_merge(
            $adminSettings,
            $billingSettings,
            $brandingSettings,
            $systemSettings,
            $communicationSettings,
            $mpesaSettings,
            $mikrotikSettings,
        );
    }

    /**
     * @param array<string, mixed> $settings
     * @return array<string, mixed>
     */
    private function sanitize(array $settings): array
    {
        foreach ($settings as $key => $value) {
            if (is_string($value)) {
                $settings[$key] = trim($value);
            }
        }

        foreach (self::BOOLEAN_FIELDS as $field) {
            $settings[$field] = filter_var($settings[$field] ?? false, FILTER_VALIDATE_BOOL);
        }

        foreach (self::INTEGER_FIELDS as $field) {
            $settings[$field] = (string) max(0, (int) ($settings[$field] ?? 0));
        }

        foreach (self::DECIMAL_FIELDS as $field) {
            $settings[$field] = number_format((float) ($settings[$field] ?? 0), 2, '.', '');
        }

        $settings['brand_primary'] = $this->normalizeHexColor((string) ($settings['brand_primary'] ?? ''), '#1E40AF');
        $settings['brand_secondary'] = $this->normalizeHexColor((string) ($settings['brand_secondary'] ?? ''), '#38BDF8');
        $settings['brand_accent'] = $this->normalizeHexColor((string) ($settings['brand_accent'] ?? ''), '#14B8A6');

        return $settings;
    }

    /**
     * @param array<string, mixed> $settings
     * @return array<string, mixed>
     */
    private function extractBillingSettings(array $settings): array
    {
        return Arr::only($settings, [
            'tax_enabled',
            'tax_label',
            'tax_rate',
            'tax_inclusive',
            'tax_number',
            'invoice_template',
            'invoice_prefix',
            'invoice_next_number',
            'invoice_address',
            'invoice_footer_note',
            'invoice_terms',
            'invoice_email',
            'receipt_enabled',
            'receipt_bcc',
            'receipt_subject',
            'receipt_body',
            'sys_currency',
            'sys_currency_symbol',
        ]);
    }

    /**
     * @param array<string, mixed> $settings
     * @return array<string, mixed>
     */
    private function extractBrandingSettings(array $settings): array
    {
        return Arr::only($settings, [
            'brand_name',
            'brand_portal_name',
            'brand_logo',
            'brand_favicon',
            'brand_primary',
            'brand_secondary',
            'brand_accent',
            'brand_welcome',
            'brand_terms',
            'brand_support',
        ]);
    }

    /**
     * @param array<string, mixed> $settings
     * @return array<string, mixed>
     */
    private function extractSystemSettings(array $settings): array
    {
        return Arr::only($settings, [
            'sys_timezone',
            'sys_date_format',
            'sys_currency',
            'sys_currency_symbol',
            'sys_session_timeout',
            'sys_upload_max',
        ]);
    }

    /**
     * @param array<string, mixed> $settings
     * @return array<string, mixed>
     */
    private function extractCommunicationSettings(array $settings): array
    {
        return Arr::only($settings, [
            'sms_provider',
            'sms_sender',
            'sms_username',
            'sms_apikey',
            'sms_payment_success',
            'sms_payment_failed',
            'sms_voucher',
            'mail_driver',
            'mail_encryption',
            'mail_host',
            'mail_port',
            'mail_username',
            'mail_password',
            'mail_from_address',
            'mail_from_name',
            'receipt_enabled',
            'receipt_bcc',
            'receipt_subject',
            'receipt_body',
        ]);
    }

    /**
     * @param array<string, mixed> $settings
     * @return array<string, mixed>
     */
    private function extractMpesaSettings(array $settings): array
    {
        return Arr::only($settings, [
            'mpesa_env',
            'mpesa_till',
            'mpesa_key',
            'mpesa_secret',
            'mpesa_passkey',
            'mpesa_callback',
            'mpesa_shortcode',
            'mpesa_timeout',
        ]);
    }

    /**
     * @param array<string, mixed> $settings
     * @return array<string, mixed>
     */
    private function extractMikrotikSettings(array $settings): array
    {
        return Arr::only($settings, [
            'mikrotik_port',
            'mikrotik_timeout',
            'mikrotik_username',
            'mikrotik_retry',
            'radius_server',
            'radius_port',
            'radius_secret',
            'radius_acct_port',
            'radius_timeout',
            'mikrotik_profile_template',
        ]);
    }

    /**
     * @return array{0: ?string, 1: ?string}
     */
    private function splitSupportContact(string $value): array
    {
        $email = null;
        if (preg_match('/[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,}/i', $value, $matches) === 1) {
            $email = trim((string) ($matches[0] ?? '')) ?: null;
        }

        $phone = null;
        if (preg_match('/(\+?\d[\d\s()\-]{6,}\d)/', $value, $matches) === 1) {
            $phone = trim((string) ($matches[0] ?? '')) ?: null;
        }

        return [$phone, $email];
    }

    private function formatSupportContact(string $phone, string $email, string $fallback): string
    {
        $parts = array_values(array_filter([
            trim($phone),
            trim($email),
        ], static fn ($part): bool => $part !== ''));

        return $parts !== [] ? implode(' | ', $parts) : trim($fallback);
    }

    private function normalizeHexColor(string $value, string $fallback): string
    {
        $value = trim($value);

        if (preg_match('/^#(?:[0-9A-Fa-f]{3}){1,2}$/', $value) === 1) {
            return strtoupper($value);
        }

        return strtoupper($fallback);
    }

    /**
     * @param array<string, mixed> $settings
     * @return array<int, string>
     */
    public function buildMikrotikCommands(array $settings): array
    {
        $radiusServer = trim((string) ($settings['radius_server'] ?? config('radius.server_ip', '127.0.0.1')));
        $radiusAuthPort = max(1, (int) ($settings['radius_port'] ?? config('radius.auth_port', 1812)));
        $radiusAcctPort = max(1, (int) ($settings['radius_acct_port'] ?? config('radius.acct_port', 1813)));
        $radiusTimeoutSeconds = max(1, (int) ($settings['radius_timeout'] ?? config('radius.timeout', 5)));
        $radiusSecret = trim((string) ($settings['radius_secret'] ?? config('radius.shared_secret', '')));

        $isLoopback = in_array(Str::lower($radiusServer), ['127.0.0.1', 'localhost', '::1'], true);
        $safeServer = $radiusServer !== '' && !$isLoopback ? $radiusServer : 'YOUR_RADIUS_SERVER_IP';
        $safeSecret = $radiusSecret !== '' && Str::lower($radiusSecret) !== 'your-radius-secret'
            ? $radiusSecret
            : 'YOUR_SHARED_SECRET';

        return [
            '/radius add service=hotspot,ppp address=' . $safeServer . ' protocol=udp authentication-port=' . $radiusAuthPort . ' accounting-port=' . $radiusAcctPort . ' secret=' . $safeSecret . ' timeout=' . $radiusTimeoutSeconds . 's',
            '/ip hotspot profile set [find] use-radius=yes',
            '/ppp aaa set use-radius=yes accounting=yes interim-update=1m',
            '/radius incoming set accept=yes port=3799',
            '/radius monitor 0 once',
        ];
    }
}
