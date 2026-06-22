<?php

namespace Tests\Feature;

use App\Models\Package;
use App\Models\Payment;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminPaymentsExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_export_payments_as_csv(): void
    {
        $tenant = $this->createTenant();
        $admin = $this->createAdminUser($tenant, 'csv-export-admin@example.com');
        $package = $this->createPackage($tenant, ['name' => '12 Hours']);

        $this->createPayment($tenant, $package, [
            'phone' => '0742939094',
            'customer_name' => 'Alice Example',
            'mpesa_receipt_number' => 'TST12345',
            'status' => 'completed',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.payments.export', [
            'format' => 'csv',
            'date_range' => 'week',
            'status' => 'success',
        ]));

        $response->assertOk();
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');
        $response->assertHeader('content-disposition');
        $this->assertStringContainsString('Alice Example', $response->streamedContent());
        $this->assertStringContainsString('TST12345', $response->streamedContent());
    }

    public function test_admin_can_export_payments_as_pdf(): void
    {
        $tenant = $this->createTenant();
        $admin = $this->createAdminUser($tenant, 'pdf-export-admin@example.com');
        $package = $this->createPackage($tenant, ['name' => '24 Hours']);

        $this->createPayment($tenant, $package, [
            'phone' => '0742939094',
            'customer_name' => null,
            'metadata' => ['payer_name' => 'Alice Example'],
            'mpesa_receipt_number' => 'PDF12345',
            'status' => 'confirmed',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.payments.export', [
            'format' => 'pdf',
            'date_range' => 'week',
            'status' => 'success',
        ]));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
        $response->assertHeader('content-disposition');
        $content = $response->getContent();
        $this->assertIsString($content);
        $this->assertStringStartsWith('%PDF-', $content);
        $this->assertStringContainsString('Alice Example', $content);
        $this->assertStringContainsString('Payments Report', $content);
    }

    private function createTenant(array $overrides = []): Tenant
    {
        return Tenant::query()->create(array_merge([
            'name' => 'Export Tenant',
            'subdomain' => 'export-' . str()->lower(str()->random(6)),
            'contact_email' => 'tenant@example.com',
            'contact_phone' => '0712345678',
            'timezone' => 'Africa/Nairobi',
            'currency' => 'KES',
            'status' => 'active',
            'plan' => 'starter',
            'monthly_fee' => 0,
            'billing_cycle_start' => now()->toDateString(),
            'next_billing_date' => now()->addMonth()->toDateString(),
            'max_routers' => 3,
            'max_users' => 100,
            'settings' => [],
        ], $overrides));
    }

    private function createAdminUser(Tenant $tenant, string $email): User
    {
        return User::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Export Admin',
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
