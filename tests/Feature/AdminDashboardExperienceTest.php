<?php

namespace Tests\Feature;

use App\Models\Package;
use App\Models\Payment;
use App\Models\Router;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminDashboardExperienceTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_renders_router_breakdown_and_payer_name_immediately(): void
    {
        $tenant = $this->createTenant();
        $admin = $this->createAdminUser($tenant, 'dashboard-admin@example.com');
        $package = $this->createPackage($tenant);

        $this->createPayment($tenant, $package, [
            'customer_name' => null,
            'metadata' => ['payer_name' => 'Alice Example'],
            'created_at' => now(),
        ]);

        $this->createRouter($tenant, [
            'name' => 'Core Router',
            'status' => Router::STATUS_ONLINE,
        ]);
        $this->createRouter($tenant, [
            'name' => 'Backhaul Router',
            'status' => Router::STATUS_WARNING,
            'ip_address' => '102.213.48.203',
        ]);
        $this->createRouter($tenant, [
            'name' => 'Edge Router',
            'status' => Router::STATUS_OFFLINE,
            'ip_address' => '102.213.48.204',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.dashboard'));

        $response->assertOk();
        $response->assertSee('Revenue (Last 7 Days)');
        $response->assertSee('Router Status Breakdown');
        $response->assertSee('Alice Example');
        $response->assertSee('Core Router');
        $response->assertSee('Backhaul Router');
        $response->assertDontSee('Package Sales');
    }

    public function test_payment_api_and_invoice_use_display_customer_name_fallback(): void
    {
        $tenant = $this->createTenant();
        $admin = $this->createAdminUser($tenant, 'payments-admin@example.com');
        $package = $this->createPackage($tenant);
        $payment = $this->createPayment($tenant, $package, [
            'status' => 'confirmed',
            'customer_name' => null,
            'metadata' => ['payer_name' => 'Alice Example'],
        ]);

        $listResponse = $this->actingAs($admin)->getJson(route('admin.api.payments.index'));

        $listResponse->assertOk();
        $listResponse->assertJsonPath('data.0.customer_name', 'Alice Example');

        $detailResponse = $this->actingAs($admin)->getJson(route('admin.api.payments.show', $payment));

        $detailResponse->assertOk();
        $detailResponse->assertJsonPath('data.customer_name', 'Alice Example');

        $invoiceResponse = $this->actingAs($admin)->get(route('admin.payments.invoice', $payment));

        $invoiceResponse->assertOk();
        $invoiceResponse->assertSee('Alice Example');
    }

    private function createTenant(array $overrides = []): Tenant
    {
        return Tenant::query()->create(array_merge([
            'name' => 'Dashboard Tenant',
            'subdomain' => 'dashboard-' . str()->lower(str()->random(6)),
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
            'name' => 'Dashboard Admin',
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

    private function createRouter(Tenant $tenant, array $overrides = []): Router
    {
        return Router::query()->create(array_merge([
            'tenant_id' => $tenant->id,
            'name' => 'Router',
            'model' => 'MikroTik Hotspot',
            'ip_address' => '102.213.48.202',
            'api_port' => 8728,
            'api_username' => 'admin',
            'api_password' => encrypt('secret'),
            'api_ssl' => false,
            'status' => Router::STATUS_OFFLINE,
        ], $overrides));
    }
}
