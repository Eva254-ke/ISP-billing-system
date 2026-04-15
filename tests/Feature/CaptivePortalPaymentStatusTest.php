<?php

namespace Tests\Feature;

use App\Models\Package;
use App\Models\Payment;
use App\Models\Tenant;
use App\Services\Mpesa\DarajaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class CaptivePortalPaymentStatusTest extends TestCase
{
    use RefreshDatabase;

    public function test_status_route_uses_explicit_payment_query_parameter(): void
    {
        $tenant = $this->createTenant();
        $package = $this->createPackage($tenant);

        $olderFailedPayment = $this->createPayment($tenant, $package, [
            'phone' => '0712345678',
            'status' => 'failed',
            'mpesa_checkout_request_id' => 'ws_CO_old_failed',
            'failed_at' => now()->subDay(),
            'reconciliation_notes' => 'Customer cancelled the prompt.',
            'metadata' => [
                'daraja_last_status' => 'rejected_by_gateway',
                'daraja_failure_reason' => 'Customer cancelled the prompt.',
            ],
        ]);

        $this->createPayment($tenant, $package, [
            'phone' => '0712345678',
            'status' => 'pending',
            'mpesa_checkout_request_id' => 'ws_CO_latest_pending',
        ]);

        $response = $this->get(route('wifi.status', [
            'phone' => '0712345678',
            'tenant_id' => $tenant->id,
            'payment' => $olderFailedPayment->id,
        ]));

        $response->assertOk();
        $response->assertSeeText('This payment attempt did not go through');
        $response->assertSeeText('Customer cancelled the prompt.');
    }

    public function test_check_status_returns_verifying_for_pending_verification_payment(): void
    {
        $tenant = $this->createTenant();
        $package = $this->createPackage($tenant);
        $payment = $this->createPayment($tenant, $package, [
            'phone' => '0712345678',
            'status' => 'pending',
            'mpesa_checkout_request_id' => 'CP-VERIFY-001',
            'metadata' => [
                'daraja_last_status' => 'pending_verification',
                'daraja_verification_required' => true,
            ],
        ]);

        $response = $this->getJson(route('wifi.status.check', [
            'phone' => '0712345678',
            'tenant_id' => $tenant->id,
            'payment' => $payment->id,
        ]));

        $response->assertOk();
        $response->assertJson([
            'status' => 'verifying',
            'payment_id' => $payment->id,
            'session_active' => false,
        ]);
    }

    public function test_pay_redirects_to_status_when_stk_request_needs_verification(): void
    {
        $tenant = $this->createTenant();
        $package = $this->createPackage($tenant);

        $daraja = Mockery::mock(DarajaService::class);
        $daraja->shouldReceive('isConfigured')->once()->andReturn(true);
        $daraja->shouldReceive('stkPush')->once()->andReturn([
            'success' => false,
            'stage' => 'stk_push',
            'http_status' => null,
            'response_code' => null,
            'response_description' => '',
            'customer_message' => '',
            'checkout_request_id' => null,
            'merchant_request_id' => null,
            'raw' => [],
            'error' => 'Connection error: timeout',
        ]);

        $this->app->instance(DarajaService::class, $daraja);

        $response = $this->post(route('wifi.pay', ['tenant_id' => $tenant->id]), [
            'phone' => '0712345678',
            'package_id' => $package->id,
        ]);

        $payment = Payment::query()->latest('id')->firstOrFail();

        $response->assertRedirect(route('wifi.status', [
            'phone' => '0712345678',
            'tenant_id' => $tenant->id,
            'payment' => $payment->id,
        ]));

        $this->assertSame('pending', $payment->status);
        $this->assertSame('pending_verification', data_get($payment->metadata, 'daraja_last_status'));
        $this->assertTrue((bool) data_get($payment->metadata, 'daraja_verification_required'));
    }

    private function createTenant(array $overrides = []): Tenant
    {
        return Tenant::query()->create(array_merge([
            'name' => 'Test Tenant',
            'subdomain' => 'tenant-' . str()->random(6),
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
        ], $overrides));
    }

    private function createPackage(Tenant $tenant, array $overrides = []): Package
    {
        return Package::query()->create(array_merge([
            'tenant_id' => $tenant->id,
            'name' => '1 Hour Pass',
            'description' => 'Fast test package',
            'code' => 'PKG-' . strtoupper(str()->random(8)),
            'price' => 50,
            'currency' => 'KES',
            'duration_value' => 60,
            'duration_unit' => 'minutes',
            'is_active' => true,
            'is_featured' => false,
            'total_sales' => 0,
            'total_revenue' => 0,
            'sort_order' => 1,
        ], $overrides));
    }

    private function createPayment(Tenant $tenant, Package $package, array $overrides = []): Payment
    {
        return Payment::query()->create(array_merge([
            'tenant_id' => $tenant->id,
            'phone' => '0712345678',
            'package_id' => $package->id,
            'package_name' => $package->name,
            'amount' => $package->price,
            'currency' => 'KES',
            'mpesa_checkout_request_id' => 'CP-' . strtoupper(uniqid()),
            'status' => 'pending',
            'initiated_at' => now(),
            'payment_channel' => 'captive_portal',
            'metadata' => [
                'gateway' => 'daraja',
            ],
        ], $overrides));
    }
}
