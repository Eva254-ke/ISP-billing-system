<?php

namespace Tests\Feature;

use App\Models\Package;
use App\Models\Payment;
use App\Models\Tenant;
use App\Jobs\ProcessMpesaCallback;
use App\Services\MikroTik\SessionManager;
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

    public function test_pay_persists_checkout_id_and_keeps_verifying_when_non_success_contains_checkout_id(): void
    {
        $tenant = $this->createTenant();
        $package = $this->createPackage($tenant);

        $daraja = Mockery::mock(DarajaService::class);
        $daraja->shouldReceive('isConfigured')->once()->andReturn(true);
        $daraja->shouldReceive('stkPush')->once()->andReturn([
            'success' => false,
            'stage' => 'stk_push',
            'http_status' => 200,
            'response_code' => '1032',
            'response_description' => 'Request cancelled by user',
            'customer_message' => '',
            'checkout_request_id' => 'ws_CO_987654321',
            'merchant_request_id' => '29115-12345-1',
            'raw' => [
                'ResponseCode' => '1032',
                'ResponseDescription' => 'Request cancelled by user',
                'CheckoutRequestID' => 'ws_CO_987654321',
                'MerchantRequestID' => '29115-12345-1',
            ],
            'error' => 'Request cancelled by user',
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
        $this->assertSame('ws_CO_987654321', $payment->mpesa_checkout_request_id);
        $this->assertSame('pending_verification', data_get($payment->metadata, 'daraja_last_status'));
        $this->assertTrue((bool) data_get($payment->metadata, 'daraja_verification_required'));
    }

    public function test_check_status_recheck_can_reconcile_recent_failed_payment(): void
    {
        $tenant = $this->createTenant();
        $package = $this->createPackage($tenant);
        $payment = $this->createPayment($tenant, $package, [
            'status' => 'failed',
            'mpesa_checkout_request_id' => 'ws_CO_recent_failed',
            'failed_at' => now()->subMinutes(2),
            'metadata' => [
                'daraja_last_status' => 'rejected_by_gateway',
            ],
        ]);

        $daraja = Mockery::mock(DarajaService::class);
        $daraja->shouldReceive('queryStkStatus')->once()->with('ws_CO_recent_failed')->andReturn([
            'success' => true,
            'final' => true,
            'is_success' => true,
            'is_failed' => false,
            'response_code' => '0',
            'result_code' => 0,
            'result_desc' => 'The service request is processed successfully.',
            'merchant_request_id' => '29115-12345-1',
            'checkout_request_id' => 'ws_CO_recent_failed',
            'receipt_number' => 'QK123ABC',
            'phone_number' => '254712345678',
            'amount' => 50.0,
            'raw' => [
                'ResultCode' => 0,
                'ResultDesc' => 'The service request is processed successfully.',
                'CheckoutRequestID' => 'ws_CO_recent_failed',
                'MpesaReceiptNumber' => 'QK123ABC',
                'PhoneNumber' => '254712345678',
                'Amount' => 50,
            ],
            'error' => null,
        ]);

        $this->app->instance(DarajaService::class, $daraja);

        $response = $this->getJson(route('wifi.status.check', [
            'phone' => '0712345678',
            'tenant_id' => $tenant->id,
            'payment' => $payment->id,
            'recheck' => 1,
        ]));

        $response->assertOk();
        $response->assertJson([
            'status' => 'paid',
            'payment_id' => $payment->id,
            'session_active' => false,
        ]);

        $payment->refresh();
        $this->assertSame('confirmed', $payment->status);
        $this->assertSame('QK123ABC', $payment->mpesa_receipt_number);
    }

    public function test_mpesa_callback_can_match_payment_by_merchant_request_id_fallback(): void
    {
        $tenant = $this->createTenant();
        $package = $this->createPackage($tenant);

        $payment = $this->createPayment($tenant, $package, [
            'mpesa_checkout_request_id' => 'CP-PLACEHOLDER-001',
            'status' => 'pending',
            'metadata' => [
                'gateway' => 'daraja',
                'daraja_merchant_request_id' => '29115-77777-1',
            ],
        ]);

        $job = new ProcessMpesaCallback([
            'MerchantRequestID' => '29115-77777-1',
            'CheckoutRequestID' => 'ws_CO_real_checkout_001',
            'ResultCode' => 0,
            'ResultDesc' => 'The service request is processed successfully.',
            'MpesaReceiptNumber' => 'QK999XYZ',
            'PhoneNumber' => '254712345678',
            'Amount' => 50,
        ]);

        $job->handle(Mockery::mock(SessionManager::class));

        $payment->refresh();
        $this->assertSame('ws_CO_real_checkout_001', $payment->mpesa_checkout_request_id);
        $this->assertSame('confirmed', $payment->status);
        $this->assertSame('QK999XYZ', $payment->mpesa_receipt_number);
    }

    public function test_pay_reuses_recent_pending_payment_attempt_instead_of_creating_duplicate(): void
    {
        $tenant = $this->createTenant();
        $package = $this->createPackage($tenant);
        $existing = $this->createPayment($tenant, $package, [
            'status' => 'pending',
            'mpesa_checkout_request_id' => 'CP-EXISTING-001',
        ]);

        $daraja = Mockery::mock(DarajaService::class);
        $daraja->shouldReceive('isConfigured')->once()->andReturn(true);
        $daraja->shouldNotReceive('stkPush');
        $this->app->instance(DarajaService::class, $daraja);

        $response = $this->post(route('wifi.pay', ['tenant_id' => $tenant->id]), [
            'phone' => '0712345678',
            'package_id' => $package->id,
        ]);

        $response->assertRedirect(route('wifi.status', [
            'phone' => '0712345678',
            'tenant_id' => $tenant->id,
            'payment' => $existing->id,
        ]));

        $this->assertSame(1, Payment::query()->count());
    }

    public function test_status_ignores_stale_failed_payment_from_session_when_newer_payment_exists(): void
    {
        $tenant = $this->createTenant();
        $package = $this->createPackage($tenant);

        $staleFailed = $this->createPayment($tenant, $package, [
            'status' => 'failed',
            'mpesa_checkout_request_id' => 'CP-STALE-FAILED',
            'failed_at' => now()->subMinutes(30),
            'metadata' => [
                'daraja_last_status' => 'rejected_by_gateway',
            ],
        ]);

        Payment::query()->whereKey($staleFailed->id)->update([
            'created_at' => now()->subMinutes(30),
        ]);

        $this->createPayment($tenant, $package, [
            'status' => 'pending',
            'mpesa_checkout_request_id' => 'CP-NEW-PENDING',
        ]);

        $response = $this->withSession(['captive_payment_id' => $staleFailed->id])
            ->get(route('wifi.status', [
                'phone' => '0712345678',
                'tenant_id' => $tenant->id,
            ]));

        $response->assertOk();
        $response->assertSeeText('Confirm the M-Pesa prompt');
        $response->assertDontSeeText('This payment attempt did not go through');
    }

    public function test_check_status_returns_verifying_for_recent_failed_rejected_by_gateway_payment(): void
    {
        $tenant = $this->createTenant();
        $package = $this->createPackage($tenant);
        $payment = $this->createPayment($tenant, $package, [
            'status' => 'failed',
            'mpesa_checkout_request_id' => 'CP-FAILED-RECENT',
            'failed_at' => now()->subMinutes(2),
            'metadata' => [
                'daraja_last_status' => 'rejected_by_gateway',
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
        ]);
    }

    public function test_mpesa_callback_marks_underpaid_transaction_failed(): void
    {
        $tenant = $this->createTenant();
        $package = $this->createPackage($tenant, ['price' => 100]);
        $payment = $this->createPayment($tenant, $package, [
            'amount' => 100,
            'status' => 'pending',
            'mpesa_checkout_request_id' => 'ws_CO_underpaid_001',
        ]);

        $job = new ProcessMpesaCallback([
            'CheckoutRequestID' => 'ws_CO_underpaid_001',
            'ResultCode' => 0,
            'ResultDesc' => 'The service request is processed successfully.',
            'MpesaReceiptNumber' => 'QKUNDERPAID1',
            'PhoneNumber' => '254712345678',
            'Amount' => 50,
        ]);

        $job->handle(Mockery::mock(SessionManager::class));

        $payment->refresh();
        $this->assertSame('failed', $payment->status);
        $this->assertSame('underpaid_amount', data_get($payment->metadata, 'daraja_last_status'));
        $this->assertSame(100.0, (float) data_get($payment->metadata, 'underpaid_expected_amount'));
        $this->assertSame(50.0, (float) data_get($payment->metadata, 'underpaid_received_amount'));
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
