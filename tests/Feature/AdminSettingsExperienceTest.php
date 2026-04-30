<?php

namespace Tests\Feature;

use App\Models\Package;
use App\Models\Payment;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminSettingsExperienceTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @var array<int, string>
     */
    private array $backupDirectories = [];

    protected function tearDown(): void
    {
        foreach ($this->backupDirectories as $directory) {
            File::deleteDirectory($directory);
        }

        parent::tearDown();
    }

    public function test_saving_settings_updates_tenant_fields_and_live_captive_portal_branding(): void
    {
        $tenant = $this->createTenant();
        $admin = $this->createAdminUser($tenant, 'settings-admin@example.com');
        $this->createPackage($tenant);

        $response = $this->actingAs($admin)->postJson(route('admin.api.settings.save'), [
            'settings' => [
                'brand_name' => 'Acme Fiber',
                'brand_portal_name' => 'Acme WiFi',
                'brand_primary' => '#123456',
                'brand_secondary' => '#654321',
                'brand_accent' => '#0F766E',
                'brand_welcome' => 'Welcome to Acme WiFi.',
                'brand_terms' => 'https://acme.test/terms',
                'brand_support' => '+254700123456 | help@acme.test',
                'sys_timezone' => 'Africa/Nairobi',
                'sys_currency' => 'KES',
                'sys_currency_symbol' => 'KES',
                'sys_session_timeout' => '180',
                'tax_enabled' => true,
                'tax_label' => 'VAT',
                'tax_rate' => '16',
                'tax_inclusive' => 'inclusive',
                'invoice_prefix' => 'AC-',
                'invoice_next_number' => '4100',
                'invoice_address' => "Acme Fiber\nNairobi",
                'invoice_footer_note' => 'Thanks for paying.',
                'invoice_terms' => '0',
                'invoice_email' => 'billing@acme.test',
                'receipt_enabled' => true,
            ],
        ]);

        $response->assertOk();
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('data.portal_preview_url', route('wifi.packages', ['tenant_id' => $tenant->id]));

        $tenant->refresh();

        $this->assertSame('Acme Fiber', $tenant->name);
        $this->assertSame('Acme WiFi', $tenant->captive_portal_title);
        $this->assertSame('Welcome to Acme WiFi.', $tenant->captive_portal_welcome_message);
        $this->assertSame('https://acme.test/terms', $tenant->captive_portal_terms_url);
        $this->assertSame('+254700123456', $tenant->captive_portal_support_phone);
        $this->assertSame('help@acme.test', $tenant->captive_portal_support_email);
        $this->assertSame(180, $tenant->captive_portal_session_timeout_minutes);
        $this->assertSame('#123456', $tenant->brand_color_primary);
        $this->assertSame('#654321', $tenant->brand_color_secondary);
        $this->assertSame('AC-', data_get($tenant->settings, 'billing.invoice_prefix'));
        $this->assertSame('4100', data_get($tenant->settings, 'admin_settings.invoice_next_number'));

        $portalResponse = $this->get(route('wifi.packages', ['tenant_id' => $tenant->id]));

        $portalResponse->assertOk();
        $portalResponse->assertSee('Acme WiFi');
        $portalResponse->assertSee('Welcome to Acme WiFi.');
        $portalResponse->assertSee('Acme Fiber');
    }

    public function test_admin_can_download_and_restore_a_real_tenant_backup(): void
    {
        $tenant = $this->createTenant(['name' => 'Backup Tenant']);
        $admin = $this->createAdminUser($tenant, 'backup-admin@example.com');
        $package = $this->createPackage($tenant, ['name' => 'Starter 12H']);
        $payment = $this->createPayment($tenant, $package, [
            'reference' => 'PAY-ORIGINAL',
            'amount' => 20,
        ]);

        $downloadResponse = $this->actingAs($admin)->get(route('admin.api.settings.backup.download'));

        $downloadResponse->assertOk();
        $backupFile = $downloadResponse->baseResponse->getFile();
        $this->assertNotNull($backupFile);
        $this->backupDirectories[] = dirname($backupFile->getPathname());

        $backupPayload = json_decode((string) file_get_contents($backupFile->getPathname()), true);

        $this->assertIsArray($backupPayload);
        $this->assertSame('cloudbridge-tenant-backup', $backupPayload['format'] ?? null);
        $this->assertSame($tenant->id, data_get($backupPayload, 'tenant.id'));
        $this->assertCount(1, data_get($backupPayload, 'tables.packages', []));
        $this->assertCount(1, data_get($backupPayload, 'tables.payments', []));

        $package->update(['name' => 'Changed Package']);
        $this->createPayment($tenant, $package, [
            'reference' => 'PAY-EXTRA',
            'amount' => 45,
        ]);

        $restoreResponse = $this->actingAs($admin)->post(
            route('admin.api.settings.backup.restore'),
            [
                'backup_file' => UploadedFile::fake()->createWithContent(
                    'tenant-backup.json',
                    json_encode($backupPayload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
                ),
            ]
        );

        $restoreResponse->assertOk();
        $restoreResponse->assertJsonPath('success', true);
        $restoreResponse->assertJsonPath('data.restored_tables.packages', 1);
        $restoreResponse->assertJsonPath('data.restored_tables.payments', 1);

        $this->assertDatabaseHas('packages', [
            'id' => $package->id,
            'tenant_id' => $tenant->id,
            'name' => 'Starter 12H',
        ]);
        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'tenant_id' => $tenant->id,
            'reference' => 'PAY-ORIGINAL',
        ]);
        $this->assertDatabaseMissing('payments', [
            'tenant_id' => $tenant->id,
            'reference' => 'PAY-EXTRA',
        ]);
    }

    public function test_admin_can_open_a_real_invoice_with_tax_breakdown_from_payments(): void
    {
        $tenant = $this->createTenant([
            'name' => 'Invoice Tenant',
            'settings' => [
                'billing' => [
                    'tax_enabled' => true,
                    'tax_label' => 'VAT',
                    'tax_rate' => '16',
                    'tax_inclusive' => 'exclusive',
                    'invoice_prefix' => 'CBN-',
                    'invoice_next_number' => '2001',
                    'invoice_address' => "Invoice Tenant\nNairobi",
                    'invoice_footer_note' => 'Receipt generated from CloudBridge.',
                    'invoice_terms' => '0',
                    'invoice_email' => 'billing@invoice.test',
                    'receipt_enabled' => true,
                    'sys_currency' => 'KES',
                    'sys_currency_symbol' => 'KES',
                ],
                'admin_settings' => [
                    'invoice_next_number' => '2001',
                ],
            ],
        ]);
        $admin = $this->createAdminUser($tenant, 'invoice-admin@example.com');
        $package = $this->createPackage($tenant, ['name' => '24 Hours']);
        $payment = $this->createPayment($tenant, $package, [
            'status' => 'confirmed',
            'reference' => 'CP-INV-1',
            'amount' => 20,
            'customer_name' => 'Jane Doe',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.payments.invoice', $payment));

        $response->assertOk();
        $response->assertSee('CBN-2001');
        $response->assertSee('VAT (16.00%)');
        $response->assertSee('KES 23.20');
        $response->assertSee('Jane Doe');

        $payment->refresh();
        $tenant->refresh();

        $this->assertSame('CBN-2001', data_get($payment->metadata, 'invoice.number'));
        $this->assertSame('2002', data_get($tenant->settings, 'billing.invoice_next_number'));
    }

    private function createTenant(array $overrides = []): Tenant
    {
        return Tenant::query()->create(array_merge([
            'name' => 'Settings Tenant',
            'subdomain' => 'settings-' . str()->lower(str()->random(6)),
            'contact_email' => 'tenant@example.com',
            'contact_phone' => '0712345678',
            'timezone' => 'Africa/Nairobi',
            'currency' => 'KES',
            'status' => 'active',
            'plan' => 'starter',
            'monthly_fee' => 0,
            'billing_cycle_start' => now()->toDateString(),
            'next_billing_date' => now()->addMonth()->toDateString(),
            'max_routers' => 1,
            'max_users' => 100,
            'settings' => [],
        ], $overrides));
    }

    private function createAdminUser(Tenant $tenant, string $email): User
    {
        return User::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Settings Admin',
            'email' => $email,
            'password' => Hash::make('password'),
            'role' => 'tenant_admin',
            'permissions' => ['dashboard.view'],
            'phone' => '0712345678',
            'timezone' => 'Africa/Nairobi',
            'is_active' => true,
        ]);
    }

    private function createPackage(Tenant $tenant, array $overrides = []): Package
    {
        return Package::query()->create(array_merge([
            'tenant_id' => $tenant->id,
            'name' => 'Day Pass',
            'description' => '24 hours access',
            'price' => 20,
            'currency' => 'KES',
            'duration_value' => 24,
            'duration_unit' => 'hours',
            'download_limit_mbps' => 10,
            'upload_limit_mbps' => 10,
            'data_limit_mb' => null,
            'is_active' => true,
            'is_featured' => false,
            'sort_order' => 1,
        ], $overrides));
    }

    private function createPayment(Tenant $tenant, Package $package, array $overrides = []): Payment
    {
        return Payment::query()->create(array_merge([
            'tenant_id' => $tenant->id,
            'package_id' => $package->id,
            'package_name' => $package->name,
            'phone' => '0712345678',
            'customer_name' => 'Customer One',
            'amount' => 20,
            'currency' => 'KES',
            'mpesa_checkout_request_id' => 'ws_CO_' . str()->upper(str()->random(12)),
            'status' => 'completed',
            'type' => Payment::TYPE_CAPTIVE_PORTAL,
            'reference' => 'PAY-' . str()->upper(str()->random(8)),
            'initiated_at' => now(),
            'payment_channel' => Payment::CHANNEL_CAPTIVE_PORTAL,
            'metadata' => [],
            'created_at' => now(),
            'updated_at' => now(),
        ], $overrides));
    }
}
