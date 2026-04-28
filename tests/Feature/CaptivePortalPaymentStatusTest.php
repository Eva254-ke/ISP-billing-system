<?php

namespace Tests\Feature;

use App\Models\Package;
use App\Models\Payment;
use App\Models\Router;
use App\Models\Tenant;
use App\Models\UserSession;
use App\Models\Voucher;
use App\Jobs\ActivateSession;
use App\Jobs\ProcessMpesaCallback;
use App\Jobs\SyncSessionUsage;
use App\Services\MikroTik\MikroTikService;
use App\Services\MikroTik\SessionManager;
use App\Services\Mpesa\DarajaService;
use App\Services\Radius\RadiusDisconnectService;
use App\Services\Radius\FreeRadiusProvisioningService;
use App\Services\Radius\RadiusAccountingService;
use App\Services\Radius\RadiusIdentityResolver;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;
use Mockery;
use Tests\TestCase;

class CaptivePortalPaymentStatusTest extends TestCase
{
    use RefreshDatabase;

    public function test_daraja_service_is_configured_without_global_callback_url_when_runtime_callback_will_be_supplied(): void
    {
        $service = new DarajaService([
            'consumer_key' => 'consumer-key',
            'consumer_secret' => 'consumer-secret',
            'passkey' => 'passkey',
            'business_shortcode' => '123456',
            'callback_url' => '',
        ]);

        $this->assertTrue($service->isConfigured());
    }

    public function test_phone_radius_identity_is_scoped_to_payment(): void
    {
        $resolver = app(RadiusIdentityResolver::class);

        $first = $resolver->resolve('0712345678', 71);
        $second = $resolver->resolve('0712345678', 72);

        $this->assertSame('cb0712345678p71', $first['username']);
        $this->assertSame('cb0712345678p72', $second['username']);
        $this->assertNotSame($first['username'], $second['username']);
        $this->assertSame($first['username'], $first['password']);
        $this->assertSame($second['username'], $second['password']);
    }

    public function test_packages_view_posts_payments_with_explicit_tenant_context(): void
    {
        $tenant = $this->createTenant();
        $this->createPackage($tenant);

        $response = $this->get(route('wifi.packages', ['tenant_id' => $tenant->id]));

        $response->assertOk();
        $response->assertSee(route('wifi.pay', ['tenant_id' => $tenant->id]), false);
        $response->assertSee('name="tenant_id"', false);
        $response->assertSee('value="' . $tenant->id . '"', false);
    }

    public function test_packages_redirects_to_status_for_recent_captive_payment_in_session(): void
    {
        $tenant = $this->createTenant();
        $package = $this->createPackage($tenant);
        $payment = $this->createPayment($tenant, $package, [
            'status' => 'confirmed',
            'confirmed_at' => now(),
        ]);

        $response = $this->withSession([
            'captive_tenant_id' => $tenant->id,
            'captive_phone' => '0712345678',
            'captive_payment_id' => $payment->id,
        ])->get(route('wifi.packages', ['tenant_id' => $tenant->id]));

        $response->assertRedirect(route('wifi.status', [
            'phone' => '0712345678',
            'tenant_id' => $tenant->id,
            'payment' => $payment->id,
        ]));
    }

    public function test_packages_redirects_to_status_for_idle_session_resolved_from_client_mac(): void
    {
        $tenant = $this->createTenant();
        $package = $this->createPackage($tenant);
        $router = $this->createRouter($tenant, [
            'status' => 'online',
            'last_seen_at' => now(),
        ]);
        $payment = $this->createPayment($tenant, $package, [
            'status' => 'confirmed',
            'confirmed_at' => now(),
        ]);

        UserSession::query()->create([
            'tenant_id' => $tenant->id,
            'router_id' => $router->id,
            'payment_id' => $payment->id,
            'package_id' => $package->id,
            'username' => $this->expectedPhoneRadiusUsername($payment),
            'phone' => '0712345678',
            'mac_address' => 'AA:BB:CC:DD:EE:FF',
            'ip_address' => '10.0.0.50',
            'status' => 'idle',
            'started_at' => now(),
            'expires_at' => now()->addHour(),
        ]);

        $response = $this->get(route('wifi.packages', [
            'tenant_id' => $tenant->id,
            'mac' => 'AA-BB-CC-DD-EE-FF',
        ]));

        $response->assertRedirect(route('wifi.status', [
            'phone' => '0712345678',
            'tenant_id' => $tenant->id,
            'payment' => $payment->id,
            'mac' => 'AA:BB:CC:DD:EE:FF',
        ]));
    }

    public function test_packages_does_not_resume_another_devices_active_session_from_phone_only(): void
    {
        $tenant = $this->createTenant();
        $package = $this->createPackage($tenant);
        $router = $this->createRouter($tenant, [
            'status' => 'online',
            'last_seen_at' => now(),
        ]);
        $payment = $this->createPayment($tenant, $package, [
            'status' => 'completed',
            'completed_at' => now()->subMinutes(5),
            'confirmed_at' => now()->subMinutes(5),
        ]);

        UserSession::query()->create([
            'tenant_id' => $tenant->id,
            'router_id' => $router->id,
            'payment_id' => $payment->id,
            'package_id' => $package->id,
            'username' => $this->expectedPhoneRadiusUsername($payment),
            'phone' => '0712345678',
            'mac_address' => 'AA:BB:CC:DD:EE:FF',
            'ip_address' => '10.0.0.50',
            'status' => 'active',
            'started_at' => now()->subMinutes(5),
            'expires_at' => now()->addHour(),
            'last_synced_at' => now(),
        ]);

        $response = $this->withSession([
            'captive_phone' => '0712345678',
        ])->get(route('wifi.packages', [
            'tenant_id' => $tenant->id,
            'mac' => '11:22:33:44:55:66',
            'ip' => '10.0.0.99',
        ]));

        $response->assertOk();
        $response->assertViewIs('captive.packages');
        $response->assertSeeText('Choose a package');
        $response->assertDontSeeText('You are already connected');
    }

    public function test_packages_does_not_resume_expired_idle_radius_session_payment(): void
    {
        config()->set('radius.enabled', true);
        config()->set('radius.pure_radius', true);

        $tenant = $this->createTenant();
        $package = $this->createPackage($tenant, [
            'duration_value' => 10,
        ]);
        $router = $this->createRouter($tenant, [
            'status' => 'online',
            'last_seen_at' => now(),
        ]);
        $payment = $this->createPayment($tenant, $package, [
            'status' => 'completed',
            'completed_at' => now()->subMinutes(15),
        ]);

        UserSession::query()->create([
            'tenant_id' => $tenant->id,
            'router_id' => $router->id,
            'payment_id' => $payment->id,
            'package_id' => $package->id,
            'username' => $this->expectedPhoneRadiusUsername($payment),
            'phone' => '0712345678',
            'mac_address' => 'AA:BB:CC:DD:EE:FF',
            'ip_address' => '10.0.0.50',
            'status' => 'idle',
            'started_at' => now()->subMinutes(15),
            'expires_at' => now()->subMinute(),
            'metadata' => [
                'radius' => [
                    'provisioned' => true,
                    'username' => $this->expectedPhoneRadiusUsername($payment),
                    'expires_at' => now()->subMinute()->toIso8601String(),
                ],
            ],
        ]);

        $response = $this->get(route('wifi.packages', [
            'tenant_id' => $tenant->id,
            'mac' => 'AA-BB-CC-DD-EE-FF',
        ]));

        $response->assertOk();
        $response->assertViewIs('captive.packages');
    }

    public function test_packages_in_pure_radius_mode_do_not_fall_back_to_router_sync_for_phone_only_active_sessions(): void
    {
        config()->set('radius.enabled', true);
        config()->set('radius.pure_radius', true);

        $tenant = $this->createTenant();
        $package = $this->createPackage($tenant);
        $router = $this->createRouter($tenant, [
            'status' => 'online',
            'last_seen_at' => now(),
        ]);
        $payment = $this->createPayment($tenant, $package, [
            'status' => 'completed',
            'completed_at' => now()->subMinutes(15),
            'confirmed_at' => now()->subMinutes(15),
        ]);

        $session = UserSession::query()->create([
            'tenant_id' => $tenant->id,
            'router_id' => $router->id,
            'payment_id' => $payment->id,
            'package_id' => $package->id,
            'username' => $this->expectedPhoneRadiusUsername($payment),
            'phone' => '0712345678',
            'status' => 'active',
            'started_at' => now()->subMinutes(20),
            'expires_at' => now()->addHour(),
            'last_synced_at' => now()->subMinutes(20),
        ]);

        $radiusAccountingService = Mockery::mock(RadiusAccountingService::class);
        $radiusAccountingService->shouldNotReceive('syncActiveSession');
        $this->app->instance(RadiusAccountingService::class, $radiusAccountingService);

        $mikroTikService = Mockery::mock(MikroTikService::class);
        $mikroTikService->shouldNotReceive('syncSessionUsage');
        $this->app->instance(MikroTikService::class, $mikroTikService);

        $response = $this->get(route('wifi.packages', [
            'tenant_id' => $tenant->id,
            'phone' => '0712345678',
        ]));

        $response->assertOk();
        $response->assertViewIs('captive.packages');
        $response->assertSeeText('Choose a package');
    }

    public function test_packages_resumes_radius_payment_waiting_for_hotspot_login_even_after_raw_idle_expiry(): void
    {
        config()->set('radius.enabled', true);
        config()->set('radius.pure_radius', true);
        config()->set('radius.pending_login_window_minutes', 120);

        $tenant = $this->createTenant();
        $package = $this->createPackage($tenant, [
            'duration_value' => 10,
        ]);
        $router = $this->createRouter($tenant, [
            'status' => 'online',
            'last_seen_at' => now(),
        ]);
        $payment = $this->createPayment($tenant, $package, [
            'status' => 'completed',
            'completed_at' => now()->subMinutes(25),
            'confirmed_at' => now()->subMinutes(25),
        ]);

        UserSession::query()->create([
            'tenant_id' => $tenant->id,
            'router_id' => $router->id,
            'payment_id' => $payment->id,
            'package_id' => $package->id,
            'username' => $this->expectedPhoneRadiusUsername($payment),
            'phone' => '0712345678',
            'mac_address' => 'AA:BB:CC:DD:EE:FF',
            'ip_address' => '10.0.0.50',
            'status' => 'idle',
            'started_at' => now()->subMinutes(25),
            'expires_at' => now()->subMinutes(15),
            'metadata' => [
                'activation' => [
                    'method' => 'radius_hotspot_login',
                    'waiting_for_hotspot_login' => true,
                    'last_attempt_at' => now()->subMinutes(25)->toIso8601String(),
                ],
            ],
        ]);

        $response = $this->get(route('wifi.packages', [
            'tenant_id' => $tenant->id,
            'mac' => 'AA-BB-CC-DD-EE-FF',
        ]));

        $response->assertRedirect(route('wifi.status', [
            'phone' => '0712345678',
            'tenant_id' => $tenant->id,
            'payment' => $payment->id,
            'mac' => 'AA:BB:CC:DD:EE:FF',
        ]));
    }

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

    public function test_check_status_does_not_offer_radius_auto_login_for_expired_completed_payment(): void
    {
        config()->set('radius.enabled', true);
        config()->set('radius.pure_radius', true);

        $tenant = $this->createTenant();
        $package = $this->createPackage($tenant, [
            'duration_value' => 10,
        ]);
        $router = $this->createRouter($tenant, [
            'status' => 'online',
            'last_seen_at' => now(),
        ]);
        $payment = $this->createPayment($tenant, $package, [
            'status' => 'completed',
            'completed_at' => now()->subMinutes(15),
            'confirmed_at' => now()->subMinutes(15),
        ]);

        $session = UserSession::query()->create([
            'tenant_id' => $tenant->id,
            'router_id' => $router->id,
            'payment_id' => $payment->id,
            'package_id' => $package->id,
            'username' => $this->expectedPhoneRadiusUsername($payment),
            'phone' => '0712345678',
            'status' => 'idle',
            'started_at' => now()->subMinutes(15),
            'expires_at' => now()->subMinute(),
            'metadata' => [
                'radius' => [
                    'provisioned' => true,
                    'username' => $this->expectedPhoneRadiusUsername($payment),
                    'expires_at' => now()->subMinute()->toIso8601String(),
                ],
            ],
        ]);

        $response = $this->getJson(route('wifi.status.check', [
            'phone' => '0712345678',
            'tenant_id' => $tenant->id,
            'payment' => $payment->id,
        ]));

        $response->assertOk();
        $response->assertJsonPath('status', 'expired');
        $response->assertJsonPath('session_active', false);
        $response->assertJsonPath('radius_auto_login', null);
        $response->assertJsonPath('radius_pending_reauth', false);
        $redirectUrl = (string) $response->json('redirect_url');
        $this->assertStringStartsWith(route('wifi.packages', [
            'phone' => '0712345678',
            'tenant_id' => $tenant->id,
        ]), $redirectUrl);
        $this->assertStringContainsString('expired=1', $redirectUrl);

        $session->refresh();
        $this->assertTrue($session->expires_at->isPast());
    }

    public function test_status_redirects_to_packages_when_completed_session_has_expired(): void
    {
        config()->set('radius.enabled', true);
        config()->set('radius.pure_radius', true);

        $tenant = $this->createTenant();
        $package = $this->createPackage($tenant, [
            'duration_value' => 10,
        ]);
        $router = $this->createRouter($tenant, [
            'status' => 'online',
            'last_seen_at' => now(),
        ]);
        $payment = $this->createPayment($tenant, $package, [
            'status' => 'completed',
            'completed_at' => now()->subMinutes(15),
            'confirmed_at' => now()->subMinutes(15),
        ]);

        UserSession::query()->create([
            'tenant_id' => $tenant->id,
            'router_id' => $router->id,
            'payment_id' => $payment->id,
            'package_id' => $package->id,
            'username' => $this->expectedPhoneRadiusUsername($payment),
            'phone' => '0712345678',
            'status' => 'idle',
            'started_at' => now()->subMinutes(15),
            'expires_at' => now()->subMinute(),
        ]);

        $response = $this->get(route('wifi.status', [
            'phone' => '0712345678',
            'tenant_id' => $tenant->id,
            'payment' => $payment->id,
        ]));

        $redirectUrl = $response->headers->get('Location');
        $this->assertIsString($redirectUrl);
        $this->assertStringStartsWith(route('wifi.packages', [
            'phone' => '0712345678',
            'tenant_id' => $tenant->id,
        ]), $redirectUrl);
        $this->assertStringContainsString('expired=1', $redirectUrl);
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

    public function test_pay_redirects_to_status_when_unexpected_exception_happens_after_payment_creation(): void
    {
        $tenant = $this->createTenant();
        $package = $this->createPackage($tenant);

        $daraja = Mockery::mock(DarajaService::class);
        $daraja->shouldReceive('isConfigured')->once()->andReturn(true);
        $daraja->shouldReceive('stkPush')->once()->andThrow(new \RuntimeException('Daraja timeout'));
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
        $response->assertSessionHas('message');

        $this->assertSame('pending', $payment->status);
        $this->assertSame(1, Payment::query()->count());
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

    public function test_pay_accepts_tenant_id_from_post_body_without_relying_on_existing_session(): void
    {
        $tenant = $this->createTenant();
        $package = $this->createPackage($tenant);

        $daraja = Mockery::mock(DarajaService::class);
        $daraja->shouldReceive('isConfigured')->once()->andReturn(true);
        $daraja->shouldReceive('stkPush')->once()->andReturn([
            'success' => true,
            'stage' => 'stk_push',
            'http_status' => 200,
            'response_code' => '0',
            'response_description' => 'Success. Request accepted for processing',
            'customer_message' => 'Success. Request accepted for processing',
            'checkout_request_id' => 'ws_CO_sessionless_001',
            'merchant_request_id' => '29115-44444-1',
            'raw' => [
                'ResponseCode' => '0',
                'CheckoutRequestID' => 'ws_CO_sessionless_001',
                'MerchantRequestID' => '29115-44444-1',
            ],
            'error' => null,
        ]);
        $this->app->instance(DarajaService::class, $daraja);

        $response = $this->post(route('wifi.pay'), [
            'tenant_id' => $tenant->id,
            'phone' => '0712345678',
            'package_id' => $package->id,
        ]);

        $payment = Payment::query()->latest('id')->firstOrFail();

        $response->assertRedirect(route('wifi.status', [
            'phone' => '0712345678',
            'tenant_id' => $tenant->id,
            'payment' => $payment->id,
        ]));

        $this->assertSame($tenant->id, (int) $payment->tenant_id);
        $this->assertSame(Payment::CHANNEL_CAPTIVE_PORTAL, (string) $payment->payment_channel);
        $this->assertSame(Payment::TYPE_CAPTIVE_PORTAL, (string) $payment->type);
    }

    public function test_reconnect_can_recover_recent_payment_after_daraja_query_confirms_success(): void
    {
        config()->set('radius.enabled', true);
        config()->set('radius.pure_radius', true);

        $tenant = $this->createTenant();
        $package = $this->createPackage($tenant);
        $router = $this->createRouter($tenant, [
            'status' => 'online',
            'last_seen_at' => now(),
        ]);

        $payment = $this->createPayment($tenant, $package, [
            'status' => 'pending',
            'mpesa_checkout_request_id' => 'ws_CO_reconnect_recovery_001',
            'metadata' => [
                'gateway' => 'daraja',
                'hotspot_context' => [
                    'link_login_only' => 'http://' . $router->ip_address . '/login',
                ],
            ],
        ]);

        $daraja = Mockery::mock(DarajaService::class);
        $daraja->shouldReceive('queryStkStatus')->once()->with('ws_CO_reconnect_recovery_001')->andReturn([
            'success' => true,
            'final' => true,
            'is_pending' => false,
            'is_success' => true,
            'is_failed' => false,
            'response_code' => '0',
            'result_code' => 0,
            'result_desc' => 'The service request is processed successfully.',
            'merchant_request_id' => '29115-99991-1',
            'checkout_request_id' => 'ws_CO_reconnect_recovery_001',
            'receipt_number' => null,
            'phone_number' => '254712345678',
            'amount' => 50.0,
            'raw' => [
                'ResultCode' => 0,
                'ResultDesc' => 'The service request is processed successfully.',
                'CheckoutRequestID' => 'ws_CO_reconnect_recovery_001',
                'PhoneNumber' => '254712345678',
                'Amount' => 50,
            ],
            'error' => null,
        ]);
        $daraja->shouldReceive('isConfigured')->andReturn(true);
        $this->app->instance(DarajaService::class, $daraja);

        $sessionManager = Mockery::mock(SessionManager::class);
        $sessionManager->shouldNotReceive('activateSession');
        $this->app->instance(SessionManager::class, $sessionManager);

        $radiusProvisioning = Mockery::mock(FreeRadiusProvisioningService::class);
        $radiusProvisioning->shouldReceive('provisionUser')->once();
        $this->app->instance(FreeRadiusProvisioningService::class, $radiusProvisioning);

        $response = $this->post(route('wifi.reconnect', ['tenant_id' => $tenant->id]), [
            'tenant_id' => $tenant->id,
            'phone' => '0712345678',
            'mpesa_code' => 'QKRECOVER001',
            'link-login-only' => 'http://' . $router->ip_address . '/login',
        ]);

        $payment->refresh();

        $response->assertRedirect(route('wifi.status', [
            'phone' => '0712345678',
            'tenant_id' => $tenant->id,
            'payment' => $payment->id,
            'link-login-only' => 'http://' . $router->ip_address . '/login',
            'link-login' => 'http://' . $router->ip_address . '/login',
        ]));
        $response->assertSessionHas('success');

        $this->assertSame('confirmed', $payment->status);
        $this->assertSame('QKRECOVER001', $payment->mpesa_receipt_number);
        $this->assertSame(1, (int) $payment->reconnect_count);
    }

    public function test_reconnect_can_match_known_mpesa_code_without_phone_input(): void
    {
        config()->set('radius.enabled', true);
        config()->set('radius.pure_radius', true);

        $tenant = $this->createTenant();
        $package = $this->createPackage($tenant);
        $router = $this->createRouter($tenant, [
            'status' => 'online',
            'last_seen_at' => now(),
        ]);

        $payment = $this->createPayment($tenant, $package, [
            'status' => 'confirmed',
            'confirmed_at' => now()->subMinute(),
            'mpesa_receipt_number' => 'QKNOPHONE001',
            'metadata' => [
                'gateway' => 'daraja',
                'hotspot_context' => [
                    'link_login_only' => 'http://' . $router->ip_address . '/login',
                ],
            ],
        ]);

        $sessionManager = Mockery::mock(SessionManager::class);
        $sessionManager->shouldNotReceive('activateSession');
        $this->app->instance(SessionManager::class, $sessionManager);

        $radiusProvisioning = Mockery::mock(FreeRadiusProvisioningService::class);
        $radiusProvisioning->shouldReceive('provisionUser')->once();
        $this->app->instance(FreeRadiusProvisioningService::class, $radiusProvisioning);

        $response = $this->post(route('wifi.reconnect', ['tenant_id' => $tenant->id]), [
            'tenant_id' => $tenant->id,
            'mpesa_code' => 'QKNOPHONE001',
            'link-login-only' => 'http://' . $router->ip_address . '/login',
        ]);

        $payment->refresh();

        $response->assertRedirect(route('wifi.status', [
            'phone' => '0712345678',
            'tenant_id' => $tenant->id,
            'payment' => $payment->id,
            'link-login-only' => 'http://' . $router->ip_address . '/login',
            'link-login' => 'http://' . $router->ip_address . '/login',
        ]));
        $response->assertSessionHas('success');
        $this->assertSame(1, (int) $payment->reconnect_count);
    }

    public function test_reconnect_can_recover_recent_payment_without_phone_input_when_phone_is_in_session(): void
    {
        config()->set('radius.enabled', true);
        config()->set('radius.pure_radius', true);

        $tenant = $this->createTenant();
        $package = $this->createPackage($tenant);
        $router = $this->createRouter($tenant, [
            'status' => 'online',
            'last_seen_at' => now(),
        ]);

        $payment = $this->createPayment($tenant, $package, [
            'status' => 'pending',
            'mpesa_checkout_request_id' => 'ws_CO_reconnect_recovery_002',
            'metadata' => [
                'gateway' => 'daraja',
                'hotspot_context' => [
                    'link_login_only' => 'http://' . $router->ip_address . '/login',
                ],
            ],
        ]);

        $daraja = Mockery::mock(DarajaService::class);
        $daraja->shouldReceive('queryStkStatus')->once()->with('ws_CO_reconnect_recovery_002')->andReturn([
            'success' => true,
            'final' => true,
            'is_pending' => false,
            'is_success' => true,
            'is_failed' => false,
            'response_code' => '0',
            'result_code' => 0,
            'result_desc' => 'The service request is processed successfully.',
            'merchant_request_id' => '29115-99992-1',
            'checkout_request_id' => 'ws_CO_reconnect_recovery_002',
            'receipt_number' => null,
            'phone_number' => '254712345678',
            'amount' => 50.0,
            'raw' => [
                'ResultCode' => 0,
                'ResultDesc' => 'The service request is processed successfully.',
                'CheckoutRequestID' => 'ws_CO_reconnect_recovery_002',
                'PhoneNumber' => '254712345678',
                'Amount' => 50,
            ],
            'error' => null,
        ]);
        $daraja->shouldReceive('isConfigured')->andReturn(true);
        $this->app->instance(DarajaService::class, $daraja);

        $sessionManager = Mockery::mock(SessionManager::class);
        $sessionManager->shouldNotReceive('activateSession');
        $this->app->instance(SessionManager::class, $sessionManager);

        $radiusProvisioning = Mockery::mock(FreeRadiusProvisioningService::class);
        $radiusProvisioning->shouldReceive('provisionUser')->once();
        $this->app->instance(FreeRadiusProvisioningService::class, $radiusProvisioning);

        $response = $this->withSession([
            'captive_phone' => '0712345678',
        ])->post(route('wifi.reconnect', ['tenant_id' => $tenant->id]), [
            'tenant_id' => $tenant->id,
            'mpesa_code' => 'QKRECOVER002',
            'link-login-only' => 'http://' . $router->ip_address . '/login',
        ]);

        $payment->refresh();

        $response->assertRedirect(route('wifi.status', [
            'phone' => '0712345678',
            'tenant_id' => $tenant->id,
            'payment' => $payment->id,
            'link-login-only' => 'http://' . $router->ip_address . '/login',
            'link-login' => 'http://' . $router->ip_address . '/login',
        ]));
        $response->assertSessionHas('success');
        $this->assertSame('confirmed', $payment->status);
        $this->assertSame('QKRECOVER002', $payment->mpesa_receipt_number);
        $this->assertSame(1, (int) $payment->reconnect_count);
    }

    public function test_reconnect_does_not_treat_another_devices_phone_only_session_as_current_device(): void
    {
        config()->set('radius.enabled', true);
        config()->set('radius.pure_radius', true);

        $tenant = $this->createTenant();
        $package = $this->createPackage($tenant);
        $router = $this->createRouter($tenant, [
            'status' => 'online',
            'last_seen_at' => now(),
        ]);

        $oldPayment = $this->createPayment($tenant, $package, [
            'status' => 'completed',
            'confirmed_at' => now()->subMinutes(15),
            'completed_at' => now()->subMinutes(15),
            'activated_at' => now()->subMinutes(15),
        ]);

        UserSession::query()->create([
            'tenant_id' => $tenant->id,
            'router_id' => $router->id,
            'payment_id' => $oldPayment->id,
            'package_id' => $package->id,
            'username' => $this->expectedPhoneRadiusUsername($oldPayment),
            'phone' => '0712345678',
            'mac_address' => 'AA:BB:CC:DD:EE:FF',
            'ip_address' => '10.0.0.50',
            'status' => 'active',
            'started_at' => now()->subMinutes(15),
            'expires_at' => now()->addHour(),
            'last_synced_at' => now(),
        ]);

        $payment = $this->createPayment($tenant, $package, [
            'status' => 'confirmed',
            'confirmed_at' => now()->subMinute(),
            'mpesa_receipt_number' => 'QKPHONEONLY02',
            'metadata' => [
                'gateway' => 'daraja',
                'hotspot_context' => [
                    'link_login_only' => 'http://' . $router->ip_address . '/login',
                ],
            ],
        ]);

        $sessionManager = Mockery::mock(SessionManager::class);
        $sessionManager->shouldNotReceive('activateSession');
        $this->app->instance(SessionManager::class, $sessionManager);

        $radiusProvisioning = Mockery::mock(FreeRadiusProvisioningService::class);
        $radiusProvisioning->shouldReceive('provisionUser')->once();
        $this->app->instance(FreeRadiusProvisioningService::class, $radiusProvisioning);

        $response = $this->post(route('wifi.reconnect', ['tenant_id' => $tenant->id]), [
            'tenant_id' => $tenant->id,
            'phone' => '0712345678',
            'mpesa_code' => 'QKPHONEONLY02',
            'link-login-only' => 'http://' . $router->ip_address . '/login',
        ]);

        $payment->refresh();

        $response->assertRedirect(route('wifi.status', [
            'phone' => '0712345678',
            'tenant_id' => $tenant->id,
            'payment' => $payment->id,
            'link-login-only' => 'http://' . $router->ip_address . '/login',
            'link-login' => 'http://' . $router->ip_address . '/login',
        ]));
        $response->assertSessionHas('success');
        $this->assertSame(1, (int) $payment->reconnect_count);
        $this->assertNotNull(UserSession::query()->where('payment_id', $payment->id)->first());
    }

    public function test_voucher_redemption_works_without_phone_input(): void
    {
        config()->set('radius.enabled', true);
        config()->set('radius.pure_radius', true);

        $tenant = $this->createTenant();
        $package = $this->createPackage($tenant);
        $router = $this->createRouter($tenant, [
            'status' => 'online',
            'last_seen_at' => now(),
        ]);
        $voucher = $this->createVoucher($tenant, $package);

        $sessionManager = Mockery::mock(SessionManager::class);
        $sessionManager->shouldNotReceive('activateSession');
        $this->app->instance(SessionManager::class, $sessionManager);

        $radiusProvisioning = Mockery::mock(FreeRadiusProvisioningService::class);
        $radiusProvisioning->shouldReceive('provisionUser')->once();
        $this->app->instance(FreeRadiusProvisioningService::class, $radiusProvisioning);

        $response = $this->post(route('wifi.reconnect', ['tenant_id' => $tenant->id]), [
            'tenant_id' => $tenant->id,
            'voucher_code' => $voucher->code_display,
            'link-login-only' => 'http://' . $router->ip_address . '/login',
        ]);

        $payment = Payment::query()
            ->where('payment_channel', Payment::CHANNEL_VOUCHER)
            ->latest('id')
            ->firstOrFail();

        $voucher->refresh();

        $response->assertRedirect(route('wifi.status', [
            'phone' => 'access-' . $payment->id,
            'tenant_id' => $tenant->id,
            'payment' => $payment->id,
            'link-login-only' => 'http://' . $router->ip_address . '/login',
            'link-login' => 'http://' . $router->ip_address . '/login',
        ]));
        $response->assertSessionHas('success');

        $this->assertMatchesRegularExpression('/^VCH[A-Z]{8}$/', (string) $payment->phone);
        $this->assertLessThanOrEqual(15, strlen((string) $payment->phone));
        $this->assertSame(Voucher::STATUS_USED, (string) $voucher->status);
        $this->assertNull($voucher->used_by_phone);
    }

    public function test_voucher_redemption_accepts_suffix_only_when_prefix_is_supplied(): void
    {
        config()->set('radius.enabled', true);
        config()->set('radius.pure_radius', true);

        $tenant = $this->createTenant();
        $package = $this->createPackage($tenant);
        $router = $this->createRouter($tenant, [
            'status' => 'online',
            'last_seen_at' => now(),
        ]);
        $voucher = $this->createVoucher($tenant, $package, [
            'code' => '123456',
            'prefix' => 'CB-WIFI',
        ]);

        $sessionManager = Mockery::mock(SessionManager::class);
        $sessionManager->shouldNotReceive('activateSession');
        $this->app->instance(SessionManager::class, $sessionManager);

        $radiusProvisioning = Mockery::mock(FreeRadiusProvisioningService::class);
        $radiusProvisioning->shouldReceive('provisionUser')->once();
        $this->app->instance(FreeRadiusProvisioningService::class, $radiusProvisioning);

        $response = $this->post(route('wifi.reconnect', ['tenant_id' => $tenant->id]), [
            'tenant_id' => $tenant->id,
            'voucher_prefix' => 'CB-WIFI',
            'voucher_code' => '123456',
            'link-login-only' => 'http://' . $router->ip_address . '/login',
        ]);

        $payment = Payment::query()
            ->where('payment_channel', Payment::CHANNEL_VOUCHER)
            ->latest('id')
            ->firstOrFail();

        $voucher->refresh();

        $response->assertRedirect(route('wifi.status', [
            'phone' => 'access-' . $payment->id,
            'tenant_id' => $tenant->id,
            'payment' => $payment->id,
            'link-login-only' => 'http://' . $router->ip_address . '/login',
            'link-login' => 'http://' . $router->ip_address . '/login',
        ]));
        $response->assertSessionHas('success');
        $this->assertSame(Voucher::STATUS_USED, (string) $voucher->status);
        $this->assertSame('CB-WIFI-123456', (string) data_get($payment->metadata, 'voucher_code'));
    }

    public function test_voucher_redemption_accepts_suffix_only_for_legacy_prefixed_code_storage(): void
    {
        config()->set('radius.enabled', true);
        config()->set('radius.pure_radius', true);

        $tenant = $this->createTenant();
        $package = $this->createPackage($tenant);
        $router = $this->createRouter($tenant, [
            'status' => 'online',
            'last_seen_at' => now(),
        ]);
        $voucher = $this->createVoucher($tenant, $package, [
            'code' => 'CB-WIFI-RJWQHM',
            'prefix' => 'CB-WIFI-',
        ]);

        $sessionManager = Mockery::mock(SessionManager::class);
        $sessionManager->shouldNotReceive('activateSession');
        $this->app->instance(SessionManager::class, $sessionManager);

        $radiusProvisioning = Mockery::mock(FreeRadiusProvisioningService::class);
        $radiusProvisioning->shouldReceive('provisionUser')->once();
        $this->app->instance(FreeRadiusProvisioningService::class, $radiusProvisioning);

        $response = $this->post(route('wifi.reconnect', ['tenant_id' => $tenant->id]), [
            'tenant_id' => $tenant->id,
            'voucher_prefix' => 'CB-WIFI',
            'voucher_code' => 'RJWQHM',
            'link-login-only' => 'http://' . $router->ip_address . '/login',
        ]);

        $payment = Payment::query()
            ->where('payment_channel', Payment::CHANNEL_VOUCHER)
            ->latest('id')
            ->firstOrFail();

        $voucher->refresh();

        $response->assertRedirect(route('wifi.status', [
            'phone' => 'access-' . $payment->id,
            'tenant_id' => $tenant->id,
            'payment' => $payment->id,
            'link-login-only' => 'http://' . $router->ip_address . '/login',
            'link-login' => 'http://' . $router->ip_address . '/login',
        ]));
        $response->assertSessionHas('success');
        $this->assertSame(Voucher::STATUS_USED, (string) $voucher->status);
        $this->assertSame('CB-WIFI-RJWQHM', (string) $voucher->code_display);
        $this->assertSame('CB-WIFI-RJWQHM', (string) data_get($payment->metadata, 'voucher_code'));
    }

    public function test_voucher_redemption_is_not_blocked_by_another_devices_phone_only_session(): void
    {
        config()->set('radius.enabled', true);
        config()->set('radius.pure_radius', true);

        $tenant = $this->createTenant();
        $package = $this->createPackage($tenant);
        $router = $this->createRouter($tenant, [
            'status' => 'online',
            'last_seen_at' => now(),
        ]);
        $voucher = $this->createVoucher($tenant, $package);

        $activePayment = $this->createPayment($tenant, $package, [
            'status' => 'completed',
            'confirmed_at' => now()->subMinutes(10),
            'completed_at' => now()->subMinutes(10),
            'activated_at' => now()->subMinutes(10),
        ]);

        UserSession::query()->create([
            'tenant_id' => $tenant->id,
            'router_id' => $router->id,
            'payment_id' => $activePayment->id,
            'package_id' => $package->id,
            'username' => $this->expectedPhoneRadiusUsername($activePayment),
            'phone' => '0712345678',
            'mac_address' => 'AA:BB:CC:DD:EE:FF',
            'ip_address' => '10.0.0.50',
            'status' => 'active',
            'started_at' => now()->subMinutes(10),
            'expires_at' => now()->addHour(),
            'last_synced_at' => now(),
        ]);

        $sessionManager = Mockery::mock(SessionManager::class);
        $sessionManager->shouldNotReceive('activateSession');
        $this->app->instance(SessionManager::class, $sessionManager);

        $radiusProvisioning = Mockery::mock(FreeRadiusProvisioningService::class);
        $radiusProvisioning->shouldReceive('provisionUser')->once();
        $this->app->instance(FreeRadiusProvisioningService::class, $radiusProvisioning);

        $response = $this->withSession([
            'captive_phone' => '0712345678',
        ])->post(route('wifi.reconnect', ['tenant_id' => $tenant->id]), [
            'tenant_id' => $tenant->id,
            'voucher_code' => $voucher->code_display,
            'link-login-only' => 'http://' . $router->ip_address . '/login',
        ]);

        $payment = Payment::query()
            ->where('payment_channel', Payment::CHANNEL_VOUCHER)
            ->latest('id')
            ->firstOrFail();

        $voucher->refresh();

        $response->assertRedirect(route('wifi.status', [
            'phone' => '0712345678',
            'tenant_id' => $tenant->id,
            'payment' => $payment->id,
            'link-login-only' => 'http://' . $router->ip_address . '/login',
            'link-login' => 'http://' . $router->ip_address . '/login',
        ]));
        $response->assertSessionHas('success');
        $this->assertSame(Voucher::STATUS_USED, (string) $voucher->status);
        $this->assertSame('0712345678', (string) $voucher->used_by_phone);
        $this->assertSame(Payment::CHANNEL_VOUCHER, (string) $payment->payment_channel);
    }

    public function test_status_shows_prompt_sent_after_successful_stk_acceptance(): void
    {
        $tenant = $this->createTenant();
        $package = $this->createPackage($tenant);
        $payment = $this->createPayment($tenant, $package, [
            'status' => 'pending',
            'mpesa_checkout_request_id' => 'ws_CO_prompt_sent_001',
            'metadata' => [
                'daraja_last_status' => 'pending_customer_confirmation',
            ],
        ]);

        $response = $this->get(route('wifi.status', [
            'phone' => '0712345678',
            'tenant_id' => $tenant->id,
            'payment' => $payment->id,
        ]));

        $response->assertOk();
        $response->assertSeeText('Prompt sent');
        $response->assertSeeText('Complete the M-Pesa prompt');
        $response->assertSeeText('If you already entered your PIN or money is deducted, do not pay again.');
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

        $radiusProvisioning = Mockery::mock(FreeRadiusProvisioningService::class);
        $radiusProvisioning->shouldIgnoreMissing();
        $job->handle(Mockery::mock(SessionManager::class), $radiusProvisioning);

        $payment->refresh();
        $this->assertSame('ws_CO_real_checkout_001', $payment->mpesa_checkout_request_id);
        $this->assertSame('confirmed', $payment->status);
        $this->assertSame('QK999XYZ', $payment->mpesa_receipt_number);
    }

    public function test_check_status_returns_radius_auto_login_payload_for_paid_pure_radius_payment(): void
    {
        config()->set('radius.enabled', true);
        config()->set('radius.pure_radius', true);

        $tenant = $this->createTenant();
        $package = $this->createPackage($tenant);
        $router = $this->createRouter($tenant, [
            'status' => 'online',
            'last_seen_at' => now(),
        ]);

        $payment = $this->createPayment($tenant, $package, [
            'status' => 'confirmed',
            'confirmed_at' => now(),
            'mpesa_checkout_request_id' => 'ws_CO_pure_radius_payload_001',
        ]);

        $sessionManager = Mockery::mock(SessionManager::class);
        $sessionManager->shouldNotReceive('activateSession');
        $this->app->instance(SessionManager::class, $sessionManager);

        $radiusProvisioning = Mockery::mock(FreeRadiusProvisioningService::class);
        $radiusProvisioning->shouldReceive('provisionUser')->once();
        $this->app->instance(FreeRadiusProvisioningService::class, $radiusProvisioning);

        $response = $this->getJson(route('wifi.status.check', [
            'phone' => '0712345678',
            'tenant_id' => $tenant->id,
            'payment' => $payment->id,
            'link-login-only' => 'http://' . $router->ip_address . '/login',
            'dst' => 'https://example.com/after-login',
            'popup' => 'true',
        ]));

        $response->assertOk();
        $response->assertJson([
            'status' => 'paid',
            'payment_id' => $payment->id,
            'session_active' => false,
            'radius_pending_reauth' => false,
        ]);
        $expectedUsername = $this->expectedPhoneRadiusUsername($payment);
        $response->assertJsonPath('radius_auto_login.action', 'http://' . $router->ip_address . '/login');
        $response->assertJsonPath('radius_auto_login.username', $expectedUsername);
        $response->assertJsonPath('radius_auto_login.password', $expectedUsername);
        $response->assertJsonPath('radius_auto_login.dst', 'https://example.com/after-login');
        $response->assertJsonPath('radius_auto_login.popup', 'true');
    }

    public function test_check_status_keeps_pure_radius_payment_pending_until_real_session_is_detected(): void
    {
        config()->set('radius.enabled', true);
        config()->set('radius.pure_radius', true);
        config()->set('radius.portal_auto_login', true);

        $tenant = $this->createTenant();
        $package = $this->createPackage($tenant);
        $router = $this->createRouter($tenant, [
            'status' => 'online',
            'last_seen_at' => now(),
        ]);

        $payment = $this->createPayment($tenant, $package, [
            'status' => 'confirmed',
            'confirmed_at' => now(),
            'mpesa_checkout_request_id' => 'ws_CO_pure_radius_submit_001',
        ]);

        $sessionManager = Mockery::mock(SessionManager::class);
        $sessionManager->shouldNotReceive('activateSession');
        $this->app->instance(SessionManager::class, $sessionManager);

        $radiusProvisioning = Mockery::mock(FreeRadiusProvisioningService::class);
        $radiusProvisioning->shouldReceive('provisionUser')->once();
        $this->app->instance(FreeRadiusProvisioningService::class, $radiusProvisioning);

        $response = $this->getJson(route('wifi.status.check', [
            'phone' => '0712345678',
            'tenant_id' => $tenant->id,
            'payment' => $payment->id,
            'link-login-only' => 'http://' . $router->ip_address . '/login',
            'radius_login_submitted' => 1,
        ]));

        $response->assertOk();
        $response->assertJson([
            'status' => 'paid',
            'payment_id' => $payment->id,
            'session_active' => false,
        ]);

        $payment->refresh();
        $session = UserSession::query()->where('payment_id', $payment->id)->firstOrFail();
        $session->refresh();

        $this->assertSame('confirmed', $payment->status);
        $this->assertNull($payment->activated_at);
        $this->assertSame('idle', $session->status);
    }

    public function test_check_status_returns_radius_auto_login_payload_for_confirmed_radius_payment_when_router_activation_is_pending(): void
    {
        config()->set('radius.enabled', true);
        config()->set('radius.pure_radius', false);

        $tenant = $this->createTenant();
        $package = $this->createPackage($tenant);
        $router = $this->createRouter($tenant, [
            'status' => 'online',
            'last_seen_at' => now(),
        ]);

        $payment = $this->createPayment($tenant, $package, [
            'status' => 'confirmed',
            'confirmed_at' => now(),
            'mpesa_checkout_request_id' => 'ws_CO_radius_fallback_autologin_001',
        ]);

        $sessionManager = Mockery::mock(SessionManager::class);
        $sessionManager->shouldReceive('activateSession')->once()->andReturn([
            'success' => false,
            'error' => 'Hotspot login command sent, but no active session was detected on the router.',
            'queued' => false,
            'missing_client_context' => false,
        ]);
        $this->app->instance(SessionManager::class, $sessionManager);

        $expectedUsername = $this->expectedPhoneRadiusUsername($payment);
        $radiusProvisioning = Mockery::mock(FreeRadiusProvisioningService::class);
        $radiusProvisioning->shouldReceive('provisionUser')->once()->withArgs(function (string $username, string $password, Package $resolvedPackage, ?\DateTimeInterface $expiresAt = null, ?string $callingStationId = null) use ($package, $expectedUsername) {
            return $username === $expectedUsername
                && $password === $expectedUsername
                && (int) $resolvedPackage->id === (int) $package->id
                && $callingStationId === null;
        });
        $this->app->instance(FreeRadiusProvisioningService::class, $radiusProvisioning);

        $response = $this->getJson(route('wifi.status.check', [
            'phone' => '0712345678',
            'tenant_id' => $tenant->id,
            'payment' => $payment->id,
            'link-login-only' => 'http://' . $router->ip_address . '/login',
            'dst' => 'https://example.com/after-login',
            'popup' => 'true',
        ]));

        $response->assertOk();
        $response->assertJson([
            'status' => 'paid',
            'payment_id' => $payment->id,
            'session_active' => false,
            'radius_pending_reauth' => false,
        ]);
        $response->assertJsonPath('radius_auto_login.action', 'http://' . $router->ip_address . '/login');
        $response->assertJsonPath('radius_auto_login.username', $expectedUsername);
        $response->assertJsonPath('radius_auto_login.password', $expectedUsername);
        $response->assertJsonPath('radius_auto_login.dst', 'https://example.com/after-login');
        $response->assertJsonPath('radius_auto_login.popup', 'true');

        $payment->refresh();

        $this->assertSame('confirmed', $payment->status);
        $this->assertTrue((bool) data_get($payment->metadata, 'radius.provisioned'));
        $this->assertNotNull(UserSession::query()->where('payment_id', $payment->id)->first());
    }

    public function test_mpesa_callback_route_acknowledges_immediately_and_processes_after_response(): void
    {
        $tenant = $this->createTenant();
        $package = $this->createPackage($tenant);

        $payment = $this->createPayment($tenant, $package, [
            'status' => 'pending',
            'mpesa_checkout_request_id' => 'ws_CO_callback_route_001',
        ]);

        $response = $this->postJson(route('api.mpesa.callback', ['tenant' => $tenant->id]), [
            'Body' => [
                'stkCallback' => [
                    'MerchantRequestID' => '29115-12345-9',
                    'CheckoutRequestID' => 'ws_CO_callback_route_001',
                    'ResultCode' => 0,
                    'ResultDesc' => 'The service request is processed successfully.',
                    'CallbackMetadata' => [
                        'Item' => [
                            ['Name' => 'MpesaReceiptNumber', 'Value' => 'QKROUTE001'],
                            ['Name' => 'PhoneNumber', 'Value' => '254712345678'],
                            ['Name' => 'Amount', 'Value' => 50],
                        ],
                    ],
                ],
            ],
        ]);

        $response->assertOk();
        $response->assertJson([
            'ResultCode' => 0,
            'ResultDesc' => 'Accepted',
        ]);

        $payment->refresh();

        $this->assertSame('confirmed', $payment->status);
        $this->assertSame('QKROUTE001', $payment->mpesa_receipt_number);
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

    public function test_pay_creates_new_attempt_when_recent_payment_failed_hard(): void
    {
        $tenant = $this->createTenant();
        $package = $this->createPackage($tenant);

        $failed = $this->createPayment($tenant, $package, [
            'status' => 'failed',
            'mpesa_checkout_request_id' => 'CP-HARD-FAILED-001',
            'failed_at' => now()->subMinute(),
            'callback_data' => [
                'ResultCode' => 1032,
            ],
            'metadata' => [
                'daraja_last_status' => 'failed_callback',
            ],
        ]);

        $daraja = Mockery::mock(DarajaService::class);
        $daraja->shouldReceive('isConfigured')->once()->andReturn(true);
        $daraja->shouldReceive('stkPush')->once()->andReturn([
            'success' => true,
            'stage' => 'stk_push',
            'http_status' => 200,
            'response_code' => '0',
            'response_description' => 'Success. Request accepted for processing',
            'customer_message' => 'Success. Request accepted for processing',
            'checkout_request_id' => 'ws_CO_fresh_new_001',
            'merchant_request_id' => '29115-11111-1',
            'raw' => [
                'ResponseCode' => '0',
                'CheckoutRequestID' => 'ws_CO_fresh_new_001',
                'MerchantRequestID' => '29115-11111-1',
            ],
            'error' => null,
        ]);
        $this->app->instance(DarajaService::class, $daraja);

        $response = $this->post(route('wifi.pay', ['tenant_id' => $tenant->id]), [
            'phone' => '0712345678',
            'package_id' => $package->id,
        ]);

        $latest = Payment::query()->latest('id')->firstOrFail();
        $this->assertNotSame($failed->id, $latest->id);
        $this->assertSame(2, Payment::query()->count());

        $response->assertRedirect(route('wifi.status', [
            'phone' => '0712345678',
            'tenant_id' => $tenant->id,
            'payment' => $latest->id,
        ]));
    }

    public function test_pay_creates_new_attempt_when_recent_payment_is_already_confirmed(): void
    {
        $tenant = $this->createTenant();
        $package = $this->createPackage($tenant);

        $confirmed = $this->createPayment($tenant, $package, [
            'status' => 'confirmed',
            'confirmed_at' => now()->subMinute(),
            'mpesa_checkout_request_id' => 'ws_CO_existing_confirmed_001',
        ]);

        $daraja = Mockery::mock(DarajaService::class);
        $daraja->shouldReceive('isConfigured')->once()->andReturn(true);
        $daraja->shouldReceive('stkPush')->once()->andReturn([
            'success' => true,
            'stage' => 'stk_push',
            'http_status' => 200,
            'response_code' => '0',
            'response_description' => 'Success. Request accepted for processing',
            'customer_message' => 'Success. Request accepted for processing',
            'checkout_request_id' => 'ws_CO_fresh_after_confirmed_001',
            'merchant_request_id' => '29115-22222-2',
            'raw' => [
                'ResponseCode' => '0',
                'CheckoutRequestID' => 'ws_CO_fresh_after_confirmed_001',
                'MerchantRequestID' => '29115-22222-2',
            ],
            'error' => null,
        ]);
        $this->app->instance(DarajaService::class, $daraja);

        $response = $this->post(route('wifi.pay', ['tenant_id' => $tenant->id]), [
            'phone' => '0712345678',
            'package_id' => $package->id,
        ]);

        $latest = Payment::query()->latest('id')->firstOrFail();

        $this->assertNotSame($confirmed->id, $latest->id);
        $this->assertSame(2, Payment::query()->count());

        $response->assertRedirect(route('wifi.status', [
            'phone' => '0712345678',
            'tenant_id' => $tenant->id,
            'payment' => $latest->id,
        ]));
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

    public function test_status_keeps_verifying_when_query_reports_still_processing(): void
    {
        $tenant = $this->createTenant();
        $package = $this->createPackage($tenant);
        $payment = $this->createPayment($tenant, $package, [
            'status' => 'pending',
            'mpesa_checkout_request_id' => 'ws_CO_still_processing_001',
            'metadata' => [
                'daraja_last_status' => 'pending_verification',
            ],
        ]);

        $daraja = Mockery::mock(DarajaService::class);
        $daraja->shouldReceive('queryStkStatus')->once()->with('ws_CO_still_processing_001')->andReturn([
            'success' => true,
            'final' => false,
            'is_pending' => true,
            'is_success' => false,
            'is_failed' => false,
            'response_code' => '0',
            'result_code' => 1,
            'result_desc' => 'The transaction is still under processing.',
            'merchant_request_id' => '29115-88888-1',
            'checkout_request_id' => 'ws_CO_still_processing_001',
            'receipt_number' => null,
            'phone_number' => null,
            'amount' => null,
            'raw' => [
                'ResultCode' => 1,
                'ResultDesc' => 'The transaction is still under processing.',
                'CheckoutRequestID' => 'ws_CO_still_processing_001',
            ],
            'error' => null,
        ]);
        $this->app->instance(DarajaService::class, $daraja);

        $response = $this->get(route('wifi.status', [
            'phone' => '0712345678',
            'tenant_id' => $tenant->id,
            'payment' => $payment->id,
            'recheck' => 1,
        ]));

        $response->assertOk();
        $response->assertSeeText('We are verifying your payment');

        $payment->refresh();
        $this->assertSame('pending', $payment->status);
        $this->assertSame('query_pending', data_get($payment->metadata, 'daraja_last_status'));
    }

    public function test_status_keeps_verifying_when_query_reports_gateway_timeout(): void
    {
        $tenant = $this->createTenant();
        $package = $this->createPackage($tenant);
        $payment = $this->createPayment($tenant, $package, [
            'status' => 'pending',
            'mpesa_checkout_request_id' => 'ws_CO_gateway_timeout_001',
            'metadata' => [
                'daraja_last_status' => 'pending_verification',
            ],
        ]);

        $daraja = Mockery::mock(DarajaService::class);
        $daraja->shouldReceive('queryStkStatus')->once()->with('ws_CO_gateway_timeout_001')->andReturn([
            'success' => true,
            'final' => true,
            'is_pending' => false,
            'is_success' => false,
            'is_failed' => true,
            'response_code' => '0',
            'result_code' => 2002,
            'result_desc' => 'Gateway timeout while processing the transaction.',
            'merchant_request_id' => '29115-99999-1',
            'checkout_request_id' => 'ws_CO_gateway_timeout_001',
            'receipt_number' => null,
            'phone_number' => null,
            'amount' => null,
            'raw' => [
                'ResultCode' => 2002,
                'ResultDesc' => 'Gateway timeout while processing the transaction.',
                'CheckoutRequestID' => 'ws_CO_gateway_timeout_001',
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
            'status' => 'verifying',
            'payment_id' => $payment->id,
            'session_active' => false,
        ]);

        $payment->refresh();
        $this->assertSame('pending', $payment->status);
        $this->assertSame('query_pending', data_get($payment->metadata, 'daraja_last_status'));
        $this->assertTrue((bool) data_get($payment->metadata, 'daraja_verification_required'));
    }

    public function test_recent_failed_query_timeout_payment_stays_in_verifying_state(): void
    {
        $tenant = $this->createTenant();
        $package = $this->createPackage($tenant);
        $payment = $this->createPayment($tenant, $package, [
            'status' => 'failed',
            'failed_at' => now()->subMinutes(2),
            'mpesa_checkout_request_id' => 'ws_CO_gateway_timeout_failed_001',
            'callback_data' => [
                'ResultCode' => 2002,
                'ResultDesc' => 'Gateway timeout while processing the transaction.',
            ],
            'metadata' => [
                'daraja_last_status' => 'failed_via_query',
                'daraja_query_result_code' => 2002,
                'daraja_query_result_desc' => 'Gateway timeout while processing the transaction.',
                'daraja_last_query_at' => now()->toIso8601String(),
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

        $radiusProvisioning = Mockery::mock(FreeRadiusProvisioningService::class);
        $radiusProvisioning->shouldIgnoreMissing();
        $job->handle(Mockery::mock(SessionManager::class), $radiusProvisioning);

        $payment->refresh();
        $this->assertSame('failed', $payment->status);
        $this->assertSame('underpaid_amount', data_get($payment->metadata, 'daraja_last_status'));
        $this->assertSame(100.0, (float) data_get($payment->metadata, 'underpaid_expected_amount'));
        $this->assertSame(50.0, (float) data_get($payment->metadata, 'underpaid_received_amount'));
    }

    public function test_mpesa_callback_keeps_payment_confirmed_when_activation_fails_and_queues_retry(): void
    {
        Queue::fake();
        config()->set('radius.enabled', true);
        config()->set('radius.pure_radius', false);

        $tenant = $this->createTenant();
        $package = $this->createPackage($tenant);
        $router = $this->createRouter($tenant, [
            'status' => 'online',
            'last_seen_at' => now(),
        ]);

        $payment = $this->createPayment($tenant, $package, [
            'status' => 'pending',
            'mpesa_checkout_request_id' => 'ws_CO_pending_activation_001',
        ]);

        $session = UserSession::query()->create([
            'tenant_id' => $tenant->id,
            'router_id' => $router->id,
            'package_id' => $package->id,
            'username' => $this->expectedPhoneRadiusUsername($payment),
            'phone' => '0712345678',
            'status' => 'idle',
            'started_at' => now(),
            'expires_at' => now()->addMinutes((int) $package->duration_in_minutes),
            'payment_id' => $payment->id,
        ]);

        $sessionManager = Mockery::mock(SessionManager::class);
        $sessionManager->shouldReceive('activateSession')->once()->andReturn([
            'success' => false,
            'error' => 'Hotspot login command sent, but no active session was created. Missing client MAC/IP context.',
            'queued' => false,
            'missing_client_context' => true,
        ]);

        $radiusProvisioning = Mockery::mock(FreeRadiusProvisioningService::class);
        $radiusProvisioning->shouldReceive('provisionUser')->once();

        $job = new ProcessMpesaCallback([
            'CheckoutRequestID' => 'ws_CO_pending_activation_001',
            'ResultCode' => 0,
            'ResultDesc' => 'The service request is processed successfully.',
            'MpesaReceiptNumber' => 'QKPENDING001',
            'PhoneNumber' => '254712345678',
            'Amount' => 50,
        ]);

        $job->handle($sessionManager, $radiusProvisioning);

        $payment->refresh();
        $session->refresh();

        $this->assertSame('confirmed', $payment->status);
        $this->assertSame($session->id, (int) $payment->session_id);
        $this->assertStringContainsString('activation is pending', (string) $payment->reconciliation_notes);
        $this->assertTrue((bool) data_get($payment->metadata, 'activation.missing_client_context'));

        Queue::assertPushed(ActivateSession::class, function (ActivateSession $job) use ($session) {
            return (int) $job->session->id === (int) $session->id;
        });
    }

    public function test_mpesa_callback_keeps_payment_confirmed_when_radius_provisioning_fails_and_queues_retry(): void
    {
        Queue::fake();
        config()->set('radius.enabled', true);
        config()->set('radius.pure_radius', false);

        $tenant = $this->createTenant();
        $package = $this->createPackage($tenant);
        $router = $this->createRouter($tenant, [
            'status' => 'online',
            'last_seen_at' => now(),
        ]);

        $payment = $this->createPayment($tenant, $package, [
            'status' => 'pending',
            'mpesa_checkout_request_id' => 'ws_CO_radius_failure_001',
        ]);

        $session = UserSession::query()->create([
            'tenant_id' => $tenant->id,
            'router_id' => $router->id,
            'package_id' => $package->id,
            'username' => $this->expectedPhoneRadiusUsername($payment),
            'phone' => '0712345678',
            'status' => 'idle',
            'started_at' => now(),
            'expires_at' => now()->addMinutes((int) $package->duration_in_minutes),
            'payment_id' => $payment->id,
        ]);

        $sessionManager = Mockery::mock(SessionManager::class);
        $sessionManager->shouldNotReceive('activateSession');

        $radiusProvisioning = Mockery::mock(FreeRadiusProvisioningService::class);
        $radiusProvisioning->shouldReceive('provisionUser')->once()->andThrow(new \RuntimeException('Radius DB offline'));

        $job = new ProcessMpesaCallback([
            'CheckoutRequestID' => 'ws_CO_radius_failure_001',
            'ResultCode' => 0,
            'ResultDesc' => 'The service request is processed successfully.',
            'MpesaReceiptNumber' => 'QKRADIUS001',
            'PhoneNumber' => '254712345678',
            'Amount' => 50,
        ]);

        $job->handle($sessionManager, $radiusProvisioning);

        $payment->refresh();
        $session->refresh();

        $this->assertSame('confirmed', $payment->status);
        $this->assertSame($session->id, (int) $payment->session_id);
        $this->assertFalse((bool) data_get($payment->metadata, 'radius.provisioned'));
        $this->assertStringContainsString('Radius DB offline', (string) data_get($payment->metadata, 'radius.last_error'));

        Queue::assertPushed(ActivateSession::class, function (ActivateSession $job) use ($session) {
            return (int) $job->session->id === (int) $session->id;
        });
    }

    public function test_activate_session_job_reprovisions_radius_and_marks_payment_completed_on_success(): void
    {
        config()->set('radius.enabled', true);
        config()->set('radius.pure_radius', false);

        $tenant = $this->createTenant();
        $package = $this->createPackage($tenant);
        $router = $this->createRouter($tenant, [
            'status' => 'online',
            'last_seen_at' => now(),
        ]);

        $payment = $this->createPayment($tenant, $package, [
            'status' => 'confirmed',
            'mpesa_checkout_request_id' => 'ws_CO_activation_retry_001',
        ]);

        $session = UserSession::query()->create([
            'tenant_id' => $tenant->id,
            'router_id' => $router->id,
            'package_id' => $package->id,
            'username' => $this->expectedPhoneRadiusUsername($payment),
            'phone' => '0712345678',
            'status' => 'idle',
            'started_at' => now(),
            'expires_at' => now()->addMinutes((int) $package->duration_in_minutes),
            'payment_id' => $payment->id,
        ]);

        $sessionManager = Mockery::mock(SessionManager::class);
        $sessionManager->shouldReceive('activateSession')->once()->andReturn([
            'success' => true,
            'expires_at' => now()->addMinutes((int) $package->duration_in_minutes),
        ]);

        $radiusProvisioning = Mockery::mock(FreeRadiusProvisioningService::class);
        $radiusProvisioning->shouldReceive('provisionUser')->once();

        $job = new ActivateSession($session);
        $job->handle($sessionManager, $radiusProvisioning);

        $payment->refresh();
        $session->refresh();

        $this->assertSame('active', $session->status);
        $this->assertSame('completed', $payment->status);
        $this->assertSame($session->id, (int) $payment->session_id);
        $this->assertTrue((bool) data_get($payment->metadata, 'radius.provisioned'));
    }

    public function test_activate_session_job_prepares_mac_radius_authorization_without_router_api_login(): void
    {
        config()->set('radius.enabled', true);
        config()->set('radius.pure_radius', false);
        config()->set('radius.access_mode', 'mac');

        $tenant = $this->createTenant();
        $package = $this->createPackage($tenant);
        $router = $this->createRouter($tenant, [
            'status' => 'online',
            'last_seen_at' => now(),
        ]);

        $payment = $this->createPayment($tenant, $package, [
            'status' => 'confirmed',
            'mpesa_checkout_request_id' => 'ws_CO_mac_radius_001',
            'metadata' => [
                'gateway' => 'daraja',
                'client_context' => [
                    'mac' => 'AA:BB:CC:DD:EE:FF',
                    'ip' => '10.0.0.25',
                ],
            ],
        ]);

        $session = UserSession::query()->create([
            'tenant_id' => $tenant->id,
            'router_id' => $router->id,
            'package_id' => $package->id,
            'username' => 'AA:BB:CC:DD:EE:FF',
            'phone' => '0712345678',
            'mac_address' => 'AA:BB:CC:DD:EE:FF',
            'ip_address' => '10.0.0.25',
            'status' => 'idle',
            'started_at' => now(),
            'expires_at' => now()->addMinutes((int) $package->duration_in_minutes),
            'payment_id' => $payment->id,
        ]);

        $sessionManager = Mockery::mock(SessionManager::class);
        $sessionManager->shouldNotReceive('activateSession');

        $radiusProvisioning = Mockery::mock(FreeRadiusProvisioningService::class);
        $radiusProvisioning->shouldReceive('provisionUser')->once()->withArgs(function (string $username, string $password, Package $resolvedPackage, ?\DateTimeInterface $expiresAt = null, ?string $callingStationId = null) use ($package) {
            return $username === 'AA:BB:CC:DD:EE:FF'
                && $password === 'AA:BB:CC:DD:EE:FF'
                && (int) $resolvedPackage->id === (int) $package->id
                && $callingStationId === 'AA:BB:CC:DD:EE:FF';
        });

        $job = new ActivateSession($session);
        $job->handle($sessionManager, $radiusProvisioning);

        $payment->refresh();
        $session->refresh();

        $this->assertSame('idle', $session->status);
        $this->assertSame('confirmed', $payment->status);
        $this->assertSame($session->id, (int) $payment->session_id);
        $this->assertStringContainsString('Waiting for hotspot re-authentication', (string) $payment->reconciliation_notes);
        $this->assertTrue((bool) data_get($payment->metadata, 'radius.authorization_prepared'));
    }

    public function test_status_renders_pure_radius_hotspot_autologin_form_without_router_api_activation(): void
    {
        config()->set('radius.enabled', true);
        config()->set('radius.pure_radius', true);

        $tenant = $this->createTenant();
        $package = $this->createPackage($tenant);
        $router = $this->createRouter($tenant, [
            'status' => 'online',
            'last_seen_at' => now(),
        ]);

        $payment = $this->createPayment($tenant, $package, [
            'status' => 'confirmed',
            'confirmed_at' => now(),
            'mpesa_checkout_request_id' => 'ws_CO_pure_radius_view_001',
        ]);

        $sessionManager = Mockery::mock(SessionManager::class);
        $sessionManager->shouldNotReceive('activateSession');
        $this->app->instance(SessionManager::class, $sessionManager);

        $expectedUsername = $this->expectedPhoneRadiusUsername($payment);
        $radiusProvisioning = Mockery::mock(FreeRadiusProvisioningService::class);
        $radiusProvisioning->shouldReceive('provisionUser')->once()->withArgs(function (string $username, string $password, Package $resolvedPackage, ?\DateTimeInterface $expiresAt = null, ?string $callingStationId = null) use ($package, $expectedUsername) {
            return $username === $expectedUsername
                && $password === $expectedUsername
                && (int) $resolvedPackage->id === (int) $package->id
                && $callingStationId === null;
        });
        $this->app->instance(FreeRadiusProvisioningService::class, $radiusProvisioning);

        $response = $this->get(route('wifi.status', [
            'phone' => '0712345678',
            'tenant_id' => $tenant->id,
            'payment' => $payment->id,
            'link-login-only' => 'http://' . $router->ip_address . '/login',
            'dst' => 'https://example.com/after-login',
            'popup' => 'true',
        ]));

        $response->assertOk();
        $response->assertSee('id="cpRadiusAutoLoginForm"', false);
        $response->assertSee('action="http://' . $router->ip_address . '/login"', false);
        $response->assertSee('name="username"', false);
        $response->assertSee('value="' . $expectedUsername . '"', false);
        $response->assertSee('name="dst"', false);
        $response->assertSee('value="https://example.com/after-login"', false);
        $response->assertSee('Connecting you to WiFi', false);
        $response->assertSee('shouldUseTopLevelRadiusAutoLogin', false);
        $response->assertSee("form.target = shouldUseTopLevelRadiusAutoLogin(loginPayload) ? '_top' : 'cpRadiusAutoLoginFrame';", false);
        $response->assertSee("return actionUrl.origin !== window.location.origin;", false);
        $response->assertSee('If internet opens immediately, you can start browsing right away', false);
        $response->assertDontSee('radiusLoginSubmittedUrl', false);
    }

    public function test_pure_radius_phone_provisioning_binds_paid_access_to_the_client_mac(): void
    {
        config()->set('radius.enabled', true);
        config()->set('radius.pure_radius', true);
        config()->set('radius.access_mode', 'phone');
        $this->configureRadiusProvisioningConnection();

        $tenant = $this->createTenant();
        $package = $this->createPackage($tenant);
        $router = $this->createRouter($tenant, [
            'status' => 'online',
            'last_seen_at' => now(),
        ]);

        $payment = $this->createPayment($tenant, $package, [
            'status' => 'confirmed',
            'confirmed_at' => now(),
            'mpesa_checkout_request_id' => 'ws_CO_radius_mac_binding_001',
        ]);

        $sessionManager = Mockery::mock(SessionManager::class);
        $sessionManager->shouldNotReceive('activateSession');
        $this->app->instance(SessionManager::class, $sessionManager);

        $response = $this->get(route('wifi.status', [
            'phone' => '0712345678',
            'tenant_id' => $tenant->id,
            'payment' => $payment->id,
            'mac' => 'AA-BB-CC-DD-EE-FF',
            'ip' => '10.0.0.25',
            'link-login-only' => 'http://' . $router->ip_address . '/login',
        ]));

        $response->assertOk();

        $username = $this->expectedPhoneRadiusUsername($payment);
        $macBinding = DB::connection('radius')->table('radcheck')
            ->where('username', $username)
            ->where('attribute', 'Calling-Station-Id')
            ->first();

        $this->assertNotNull($macBinding);
        $this->assertSame('==', $macBinding->op);
        $this->assertSame('AA:BB:CC:DD:EE:FF', $macBinding->value);
    }

    public function test_status_hashes_password_field_for_chap_radius_autologin(): void
    {
        config()->set('radius.enabled', true);
        config()->set('radius.pure_radius', true);

        $tenant = $this->createTenant();
        $package = $this->createPackage($tenant);
        $router = $this->createRouter($tenant, [
            'status' => 'online',
            'last_seen_at' => now(),
        ]);

        $payment = $this->createPayment($tenant, $package, [
            'status' => 'confirmed',
            'confirmed_at' => now(),
            'mpesa_checkout_request_id' => 'ws_CO_pure_radius_chap_view_001',
        ]);

        $sessionManager = Mockery::mock(SessionManager::class);
        $sessionManager->shouldNotReceive('activateSession');
        $this->app->instance(SessionManager::class, $sessionManager);

        $radiusProvisioning = Mockery::mock(FreeRadiusProvisioningService::class);
        $radiusProvisioning->shouldReceive('provisionUser')->once();
        $this->app->instance(FreeRadiusProvisioningService::class, $radiusProvisioning);

        $response = $this->get(route('wifi.status', [
            'phone' => '0712345678',
            'tenant_id' => $tenant->id,
            'payment' => $payment->id,
            'link-login-only' => 'http://' . $router->ip_address . '/login',
            'chap-id' => '01',
            'chap-challenge' => '0123456789ABCDEF',
        ]));

        $response->assertOk();
        $response->assertSee('passwordInput.value = buildChapResponse(', false);
        $response->assertSee('return md5Hex(chapIdBinary + password + chapChallengeBinary);', false);
        $response->assertSee("setHiddenField(form, 'response', null);", false);
        $response->assertDontSee('name="response" value=""', false);

        $content = str_replace(["\r\n", "\r"], "\n", (string) $response->getContent());
        $this->assertStringContainsString("if (\n                passwordInput\n                && radiusAutoLogin.chap_id\n                && radiusAutoLogin.chap_challenge\n            ) {", $content);
        $this->assertStringNotContainsString("if (\n                && passwordInput", $content);
    }

    public function test_status_accepts_login_wifi_hotspot_alias_for_radius_autologin(): void
    {
        config()->set('radius.enabled', true);
        config()->set('radius.pure_radius', true);

        $tenant = $this->createTenant();
        $package = $this->createPackage($tenant);
        $this->createRouter($tenant, [
            'status' => 'online',
            'last_seen_at' => now(),
        ]);

        $payment = $this->createPayment($tenant, $package, [
            'status' => 'confirmed',
            'confirmed_at' => now(),
            'mpesa_checkout_request_id' => 'ws_CO_pure_radius_login_wifi_001',
        ]);

        $sessionManager = Mockery::mock(SessionManager::class);
        $sessionManager->shouldNotReceive('activateSession');
        $this->app->instance(SessionManager::class, $sessionManager);

        $radiusProvisioning = Mockery::mock(FreeRadiusProvisioningService::class);
        $radiusProvisioning->shouldReceive('provisionUser')->once();
        $this->app->instance(FreeRadiusProvisioningService::class, $radiusProvisioning);

        $response = $this->get(route('wifi.status', [
            'phone' => '0712345678',
            'tenant_id' => $tenant->id,
            'payment' => $payment->id,
            'link-login-only' => 'http://login.wifi/login',
        ]));

        $response->assertOk();
        $response->assertSee('action="http://login.wifi/login"', false);
    }

    public function test_status_prefers_login_wifi_referrer_for_radius_autologin_when_public_router_ip_is_passed(): void
    {
        config()->set('radius.enabled', true);
        config()->set('radius.pure_radius', true);

        $tenant = $this->createTenant();
        $package = $this->createPackage($tenant);
        $router = $this->createRouter($tenant, [
            'status' => 'online',
            'last_seen_at' => now(),
        ]);

        $payment = $this->createPayment($tenant, $package, [
            'status' => 'confirmed',
            'confirmed_at' => now(),
            'mpesa_checkout_request_id' => 'ws_CO_pure_radius_login_wifi_referrer_001',
        ]);

        $sessionManager = Mockery::mock(SessionManager::class);
        $sessionManager->shouldNotReceive('activateSession');
        $this->app->instance(SessionManager::class, $sessionManager);

        $radiusProvisioning = Mockery::mock(FreeRadiusProvisioningService::class);
        $radiusProvisioning->shouldReceive('provisionUser')->once();
        $this->app->instance(FreeRadiusProvisioningService::class, $radiusProvisioning);

        $response = $this->withHeader('referer', 'http://login.wifi/')
            ->get(route('wifi.status', [
                'phone' => '0712345678',
                'tenant_id' => $tenant->id,
                'payment' => $payment->id,
                'link-login-only' => 'http://' . $router->ip_address . '/login',
            ]));

        $response->assertOk();
        $response->assertSee('action="http://login.wifi/login"', false);
        $response->assertDontSee('action="http://' . $router->ip_address . '/login"', false);
    }

    public function test_session_manager_uses_radius_disconnect_for_expired_pure_radius_sessions(): void
    {
        config()->set('radius.enabled', true);
        config()->set('radius.pure_radius', true);

        $tenant = $this->createTenant();
        $package = $this->createPackage($tenant);
        $router = $this->createRouter($tenant, [
            'status' => 'online',
            'last_seen_at' => now(),
        ]);

        $payment = $this->createPayment($tenant, $package, [
            'status' => 'completed',
            'completed_at' => now()->subHour(),
        ]);

        $session = UserSession::query()->create([
            'tenant_id' => $tenant->id,
            'router_id' => $router->id,
            'package_id' => $package->id,
            'username' => $this->expectedPhoneRadiusUsername($payment),
            'phone' => '0712345678',
            'status' => 'active',
            'started_at' => now()->subHour(),
            'expires_at' => now()->subMinute(),
            'payment_id' => $payment->id,
            'metadata' => [
                'radius' => [
                    'provisioned' => true,
                    'username' => $this->expectedPhoneRadiusUsername($payment),
                ],
            ],
        ]);

        $mikrotikService = Mockery::mock(\App\Services\MikroTik\MikroTikService::class);
        $mikrotikService->shouldNotReceive('disconnectSession');

        $radiusDisconnectService = Mockery::mock(RadiusDisconnectService::class);
        $radiusDisconnectService->shouldReceive('disconnect')->once()->with($session)->andReturn([
            'success' => true,
            'error' => null,
            'nas_ip' => $router->ip_address,
            'port' => 3799,
            'used_accounting_record' => true,
            'attributes' => [
                'User-Name' => $session->username,
            ],
        ]);

        $manager = new SessionManager($mikrotikService, $radiusDisconnectService);

        $this->assertTrue($manager->terminateSession($session, 'expired'));

        $session->refresh();
        $this->assertSame('terminated', $session->status);
        $this->assertSame('expired', $session->termination_reason);
        $this->assertNotNull($session->terminated_at);
    }

    public function test_session_manager_revokes_radius_profile_when_expiring_pure_radius_session(): void
    {
        config()->set('radius.enabled', true);
        config()->set('radius.pure_radius', true);
        $this->configureRadiusProvisioningConnection();

        $tenant = $this->createTenant();
        $package = $this->createPackage($tenant);
        $router = $this->createRouter($tenant, [
            'status' => 'online',
            'last_seen_at' => now(),
        ]);

        $payment = $this->createPayment($tenant, $package, [
            'status' => 'completed',
            'completed_at' => now()->subHour(),
        ]);

        $username = $this->expectedPhoneRadiusUsername($payment);

        DB::connection('radius')->table('radcheck')->insert([
            'username' => $username,
            'attribute' => 'Cleartext-Password',
            'op' => ':=',
            'value' => $username,
        ]);

        DB::connection('radius')->table('radreply')->insert([
            'username' => $username,
            'attribute' => 'Session-Timeout',
            'op' => ':=',
            'value' => '3600',
        ]);

        $session = UserSession::query()->create([
            'tenant_id' => $tenant->id,
            'router_id' => $router->id,
            'package_id' => $package->id,
            'username' => $username,
            'phone' => '0712345678',
            'status' => 'active',
            'started_at' => now()->subHour(),
            'expires_at' => now()->subMinute(),
            'payment_id' => $payment->id,
            'metadata' => [
                'radius' => [
                    'provisioned' => true,
                    'username' => $username,
                ],
            ],
        ]);

        $mikrotikService = Mockery::mock(\App\Services\MikroTik\MikroTikService::class);
        $mikrotikService->shouldNotReceive('disconnectSession');

        $radiusDisconnectService = Mockery::mock(RadiusDisconnectService::class);
        $radiusDisconnectService->shouldReceive('disconnect')->once()->with($session)->andReturn([
            'success' => true,
            'error' => null,
            'nas_ip' => $router->ip_address,
            'port' => 3799,
            'used_accounting_record' => true,
            'attributes' => [
                'User-Name' => $session->username,
            ],
        ]);

        $manager = new SessionManager($mikrotikService, $radiusDisconnectService, new FreeRadiusProvisioningService());

        $this->assertTrue($manager->terminateSession($session, 'expired'));

        $session->refresh();
        $payment->refresh();

        $this->assertSame(0, DB::connection('radius')->table('radcheck')->where('username', $username)->count());
        $this->assertSame(0, DB::connection('radius')->table('radreply')->where('username', $username)->count());
        $this->assertFalse((bool) data_get($session->metadata, 'radius.provisioned'));
        $this->assertFalse((bool) data_get($payment->metadata, 'radius.provisioned'));
        $this->assertNotNull(data_get($session->metadata, 'radius.revoked_at'));
    }

    public function test_session_manager_preserves_pure_radius_session_when_radius_disconnect_fails(): void
    {
        config()->set('radius.enabled', true);
        config()->set('radius.pure_radius', true);

        $tenant = $this->createTenant();
        $package = $this->createPackage($tenant);
        $router = $this->createRouter($tenant, [
            'status' => 'online',
            'last_seen_at' => now(),
        ]);

        $payment = $this->createPayment($tenant, $package, [
            'status' => 'completed',
            'completed_at' => now()->subHour(),
        ]);

        $session = UserSession::query()->create([
            'tenant_id' => $tenant->id,
            'router_id' => $router->id,
            'package_id' => $package->id,
            'username' => $this->expectedPhoneRadiusUsername($payment),
            'phone' => '0712345678',
            'status' => 'active',
            'started_at' => now()->subHour(),
            'expires_at' => now()->subMinute(),
            'payment_id' => $payment->id,
            'metadata' => [
                'radius' => [
                    'provisioned' => true,
                    'username' => $this->expectedPhoneRadiusUsername($payment),
                ],
            ],
        ]);

        $mikrotikService = Mockery::mock(\App\Services\MikroTik\MikroTikService::class);
        $mikrotikService->shouldNotReceive('disconnectSession');

        $radiusDisconnectService = Mockery::mock(RadiusDisconnectService::class);
        $radiusDisconnectService->shouldReceive('disconnect')->once()->with($session)->andReturn([
            'success' => false,
            'error' => 'Disconnect-NAK',
            'nas_ip' => $router->ip_address,
            'port' => 3799,
            'used_accounting_record' => false,
            'attributes' => [
                'User-Name' => $session->username,
            ],
        ]);

        $manager = new SessionManager($mikrotikService, $radiusDisconnectService);

        $this->assertFalse($manager->terminateSession($session, 'expired'));

        $session->refresh();
        $this->assertSame('active', $session->status);
        $this->assertNull($session->terminated_at);
        $this->assertNull($session->termination_reason);
    }

    public function test_check_status_marks_mac_authorized_session_active_from_radius_accounting(): void
    {
        config()->set('radius.enabled', true);
        config()->set('radius.pure_radius', false);
        config()->set('radius.access_mode', 'mac');
        $this->configureRadiusAccountingConnection();

        $tenant = $this->createTenant();
        $package = $this->createPackage($tenant);
        $this->createRouter($tenant, [
            'status' => 'online',
            'last_seen_at' => now(),
        ]);

        $payment = $this->createPayment($tenant, $package, [
            'phone' => '0712345678',
            'status' => 'confirmed',
            'mpesa_checkout_request_id' => 'ws_CO_radacct_activation_001',
            'metadata' => [
                'gateway' => 'daraja',
                'client_context' => [
                    'mac' => 'AA:BB:CC:DD:EE:FF',
                    'ip' => '10.0.0.25',
                ],
            ],
        ]);

        $session = UserSession::query()->create([
            'tenant_id' => $tenant->id,
            'router_id' => Router::query()->value('id'),
            'package_id' => $package->id,
            'username' => 'AA:BB:CC:DD:EE:FF',
            'phone' => '0712345678',
            'mac_address' => 'AA:BB:CC:DD:EE:FF',
            'ip_address' => '10.0.0.25',
            'status' => 'idle',
            'started_at' => now(),
            'expires_at' => now()->addMinutes((int) $package->duration_in_minutes),
            'payment_id' => $payment->id,
        ]);

        $this->seedRadiusAccountingRecord([
            'username' => 'AA:BB:CC:DD:EE:FF',
            'callingstationid' => 'AA:BB:CC:DD:EE:FF',
            'framedipaddress' => '10.0.0.25',
            'acctstarttime' => now()->subMinute(),
            'acctupdatetime' => now(),
        ]);

        $sessionManager = Mockery::mock(SessionManager::class);
        $sessionManager->shouldNotReceive('activateSession');
        $this->app->instance(SessionManager::class, $sessionManager);

        $radiusProvisioning = Mockery::mock(FreeRadiusProvisioningService::class);
        $radiusProvisioning->shouldReceive('provisionUser')->once();
        $this->app->instance(FreeRadiusProvisioningService::class, $radiusProvisioning);

        $response = $this->getJson(route('wifi.status.check', [
            'phone' => '0712345678',
            'tenant_id' => $tenant->id,
            'payment' => $payment->id,
        ]));

        $response->assertOk();
        $response->assertJson([
            'status' => 'activated',
            'payment_id' => $payment->id,
            'session_active' => true,
        ]);

        $payment->refresh();
        $session->refresh();

        $this->assertSame('active', $session->status);
        $this->assertSame('completed', $payment->status);
        $this->assertSame($session->id, (int) $payment->session_id);
        $this->assertSame($session->id, (int) $payment->load('session')->session?->id);
        $this->assertNotNull($payment->activated_at);
        $this->assertSame('sess-001', (string) data_get($session->metadata, 'radius.acct_session_id'));
        $this->assertSame('AA:BB:CC:DD:EE:FF', (string) data_get($session->metadata, 'radius.calling_station_id'));
        $this->assertSame('10.0.0.25', (string) data_get($session->metadata, 'radius.framed_ip_address'));
        $this->assertSame('AA:BB:CC:DD:EE:FF', (string) data_get($session->metadata, 'radius.active_username'));
    }

    public function test_session_extension_reuses_active_radius_identity_and_extends_the_existing_session(): void
    {
        config()->set('radius.enabled', true);
        config()->set('radius.pure_radius', true);

        $tenant = $this->createTenant();
        $basePackage = $this->createPackage($tenant, [
            'name' => 'Monthly Pass',
            'duration_value' => 30,
            'duration_unit' => 'days',
        ]);
        $extensionPackage = $this->createPackage($tenant, [
            'name' => 'Annual Top-up',
            'duration_value' => 365,
            'duration_unit' => 'days',
            'price' => 12000,
        ]);
        $router = $this->createRouter($tenant, [
            'status' => 'online',
            'last_seen_at' => now(),
        ]);

        $parentPayment = $this->createPayment($tenant, $basePackage, [
            'status' => 'completed',
            'completed_at' => now()->subDay(),
            'activated_at' => now()->subDay(),
        ]);

        $activeUsername = $this->expectedPhoneRadiusUsername($parentPayment);
        $originalExpiry = now()->addDays(30);

        $session = UserSession::query()->create([
            'tenant_id' => $tenant->id,
            'router_id' => $router->id,
            'package_id' => $basePackage->id,
            'username' => $activeUsername,
            'phone' => '0712345678',
            'status' => 'active',
            'started_at' => now()->subDay(),
            'expires_at' => $originalExpiry,
            'payment_id' => $parentPayment->id,
            'metadata' => [
                'radius' => [
                    'provisioned' => true,
                    'username' => $activeUsername,
                    'active_username' => $activeUsername,
                    'expires_at' => $originalExpiry->toIso8601String(),
                ],
            ],
        ]);

        $extensionPayment = $this->createPayment($tenant, $extensionPackage, [
            'status' => 'confirmed',
            'confirmed_at' => now(),
            'type' => Payment::TYPE_SESSION_EXTENSION,
            'payment_channel' => Payment::CHANNEL_SESSION_EXTENSION,
            'parent_payment_id' => $parentPayment->id,
            'mpesa_checkout_request_id' => 'ws_CO_radius_extension_reuse_001',
        ]);

        $sessionManager = Mockery::mock(SessionManager::class);
        $sessionManager->shouldNotReceive('activateSession');
        $this->app->instance(SessionManager::class, $sessionManager);

        $radiusProvisioning = Mockery::mock(FreeRadiusProvisioningService::class);
        $radiusProvisioning->shouldReceive('provisionUser')->once()->withArgs(
            function (string $username, string $password, Package $resolvedPackage, ?\DateTimeInterface $expiresAt) use ($activeUsername, $extensionPackage, $originalExpiry) {
                return $username === $activeUsername
                    && $password === $activeUsername
                    && (int) $resolvedPackage->id === (int) $extensionPackage->id
                    && $expiresAt !== null
                    && Carbon::instance(\DateTimeImmutable::createFromInterface($expiresAt))->gt($originalExpiry);
            }
        );
        $this->app->instance(FreeRadiusProvisioningService::class, $radiusProvisioning);

        $response = $this->getJson(route('wifi.status.check', [
            'phone' => '0712345678',
            'tenant_id' => $tenant->id,
            'payment' => $extensionPayment->id,
        ]));

        $response->assertOk();
        $response->assertJson([
            'status' => 'activated',
            'payment_id' => $extensionPayment->id,
            'session_active' => true,
        ]);

        $session->refresh();
        $extensionPayment->refresh();

        $this->assertSame($extensionPayment->id, (int) $session->payment_id);
        $this->assertSame($session->id, (int) $extensionPayment->session_id);
        $this->assertSame('completed', $extensionPayment->status);
        $this->assertSame($activeUsername, $session->username);
        $this->assertTrue($session->expires_at->gt($originalExpiry));
        $this->assertSame($activeUsername, (string) data_get($session->metadata, 'radius.active_username'));
        $this->assertSame($activeUsername, (string) data_get($extensionPayment->metadata, 'radius.username'));
    }

    public function test_radius_accounting_realigns_idle_session_timing_to_the_actual_hotspot_login(): void
    {
        config()->set('radius.enabled', true);
        config()->set('radius.pure_radius', true);
        config()->set('radius.access_mode', 'mac');
        $this->configureRadiusAccountingConnection();

        $tenant = $this->createTenant();
        $package = $this->createPackage($tenant);
        $router = $this->createRouter($tenant, [
            'status' => 'online',
            'last_seen_at' => now(),
        ]);

        $payment = $this->createPayment($tenant, $package, [
            'phone' => '0712345678',
            'status' => 'confirmed',
            'mpesa_checkout_request_id' => 'ws_CO_radacct_activation_delay_001',
            'metadata' => [
                'gateway' => 'daraja',
                'client_context' => [
                    'mac' => 'AA:BB:CC:DD:EE:FF',
                    'ip' => '10.0.0.25',
                ],
            ],
        ]);

        $queuedAt = now()->subMinutes(5)->startOfSecond();
        $actualLoginAt = now()->startOfSecond();
        $originalExpiry = $queuedAt->copy()->addMinutes((int) $package->duration_in_minutes);
        $expectedExpiry = $actualLoginAt->copy()->addMinutes((int) $package->duration_in_minutes);

        $session = UserSession::query()->create([
            'tenant_id' => $tenant->id,
            'router_id' => $router->id,
            'package_id' => $package->id,
            'username' => 'AA:BB:CC:DD:EE:FF',
            'phone' => '0712345678',
            'mac_address' => 'AA:BB:CC:DD:EE:FF',
            'ip_address' => '10.0.0.25',
            'status' => 'idle',
            'started_at' => $queuedAt,
            'expires_at' => $originalExpiry,
            'grace_period_seconds' => $package->grace_period_seconds,
            'payment_id' => $payment->id,
            'metadata' => [
                'activation' => [
                    'method' => 'radius_hotspot_login',
                    'waiting_for_hotspot_login' => true,
                ],
            ],
        ]);

        $this->seedRadiusAccountingRecord([
            'username' => 'AA:BB:CC:DD:EE:FF',
            'callingstationid' => 'AA:BB:CC:DD:EE:FF',
            'framedipaddress' => '10.0.0.25',
            'acctstarttime' => $actualLoginAt->toDateTimeString(),
            'acctupdatetime' => $actualLoginAt->copy()->addSeconds(30)->toDateTimeString(),
        ]);

        $sessionManager = Mockery::mock(SessionManager::class);
        $sessionManager->shouldNotReceive('activateSession');
        $this->app->instance(SessionManager::class, $sessionManager);

        $radiusProvisioning = Mockery::mock(FreeRadiusProvisioningService::class);
        $radiusProvisioning->shouldReceive('provisionUser')->once();
        $this->app->instance(FreeRadiusProvisioningService::class, $radiusProvisioning);

        $response = $this->getJson(route('wifi.status.check', [
            'phone' => '0712345678',
            'tenant_id' => $tenant->id,
            'payment' => $payment->id,
        ]));

        $response->assertOk();
        $response->assertJson([
            'status' => 'activated',
            'payment_id' => $payment->id,
            'session_active' => true,
        ]);

        $payment->refresh();
        $session->refresh();

        $this->assertSame('active', $session->status);
        $this->assertSame($actualLoginAt->toDateTimeString(), $session->started_at->toDateTimeString());
        $this->assertSame($expectedExpiry->toDateTimeString(), $session->expires_at->toDateTimeString());
        $this->assertTrue($session->expires_at->gt($originalExpiry));
        $this->assertSame($actualLoginAt->toDateTimeString(), $payment->activated_at?->toDateTimeString());
        $this->assertSame('radius_accounting', (string) data_get($session->metadata, 'activation.activated_via'));
        $this->assertFalse((bool) data_get($session->metadata, 'activation.waiting_for_hotspot_login'));
    }

    public function test_expire_stale_sessions_marks_overdue_active_and_idle_sessions_expired(): void
    {
        $tenant = $this->createTenant();
        $package = $this->createPackage($tenant);
        $router = $this->createRouter($tenant, [
            'status' => 'online',
            'last_seen_at' => now(),
        ]);

        $expiredActive = UserSession::query()->create([
            'tenant_id' => $tenant->id,
            'router_id' => $router->id,
            'package_id' => $package->id,
            'username' => 'cb-expired-active',
            'phone' => '0712345678',
            'status' => 'active',
            'started_at' => now()->subHour(),
            'expires_at' => now()->subMinute(),
            'grace_period_seconds' => 0,
        ]);

        $expiredIdle = UserSession::query()->create([
            'tenant_id' => $tenant->id,
            'router_id' => $router->id,
            'package_id' => $package->id,
            'username' => 'cb-expired-idle',
            'phone' => '0712345678',
            'status' => 'idle',
            'started_at' => now()->subHour(),
            'expires_at' => now()->subMinute(),
            'grace_period_seconds' => 0,
        ]);

        $liveSession = UserSession::query()->create([
            'tenant_id' => $tenant->id,
            'router_id' => $router->id,
            'package_id' => $package->id,
            'username' => 'cb-live-session',
            'phone' => '0712345678',
            'status' => 'active',
            'started_at' => now()->subMinutes(10),
            'expires_at' => now()->addMinutes(20),
        ]);

        $updated = UserSession::expireStaleSessions($tenant->id);

        $expiredActive->refresh();
        $expiredIdle->refresh();
        $liveSession->refresh();

        $this->assertSame(2, $updated);
        $this->assertSame('expired', $expiredActive->status);
        $this->assertSame('expired', $expiredIdle->status);
        $this->assertSame('expired', $expiredActive->termination_reason);
        $this->assertSame('expired', $expiredIdle->termination_reason);
        $this->assertNotNull($expiredActive->terminated_at);
        $this->assertNotNull($expiredIdle->terminated_at);
        $this->assertSame('active', $liveSession->status);
        $this->assertSame([$liveSession->id], UserSession::query()->live()->pluck('id')->all());
    }

    public function test_expire_stale_sessions_keeps_radius_sessions_waiting_for_hotspot_login_alive(): void
    {
        config()->set('radius.pending_login_window_minutes', 120);

        $tenant = $this->createTenant();
        $package = $this->createPackage($tenant, [
            'duration_value' => 10,
        ]);
        $router = $this->createRouter($tenant, [
            'status' => 'online',
            'last_seen_at' => now(),
        ]);

        $waitingSession = UserSession::query()->create([
            'tenant_id' => $tenant->id,
            'router_id' => $router->id,
            'package_id' => $package->id,
            'username' => 'cb-radius-waiting',
            'phone' => '0712345678',
            'status' => 'idle',
            'started_at' => now()->subMinutes(25),
            'expires_at' => now()->subMinutes(15),
            'grace_period_seconds' => 0,
            'metadata' => [
                'activation' => [
                    'method' => 'radius_hotspot_login',
                    'waiting_for_hotspot_login' => true,
                    'last_attempt_at' => now()->subMinutes(25)->toIso8601String(),
                ],
            ],
        ]);

        $updated = UserSession::expireStaleSessions($tenant->id);

        $waitingSession->refresh();

        $this->assertSame(0, $updated);
        $this->assertSame('idle', $waitingSession->status);
        $this->assertFalse($waitingSession->shouldDisconnect());
    }

    public function test_expire_stale_sessions_keeps_sessions_with_remaining_grace_buffer_active(): void
    {
        $tenant = $this->createTenant();
        $package = $this->createPackage($tenant);
        $router = $this->createRouter($tenant, [
            'status' => 'online',
            'last_seen_at' => now(),
        ]);

        $session = UserSession::query()->create([
            'tenant_id' => $tenant->id,
            'router_id' => $router->id,
            'package_id' => $package->id,
            'username' => 'cb-grace-window',
            'phone' => '0712345678',
            'status' => 'active',
            'started_at' => now()->subHour(),
            'expires_at' => now()->subMinutes(4),
            'grace_period_seconds' => $package->grace_period_seconds,
        ]);

        $updated = UserSession::expireStaleSessions($tenant->id);

        $session->refresh();

        $this->assertSame(0, $updated);
        $this->assertSame('active', $session->status);
        $this->assertTrue($session->shouldActivateGracePeriod());
        $this->assertFalse($session->shouldDisconnect());
    }

    public function test_sync_session_usage_activates_only_the_remaining_grace_window_after_an_accounting_sync_miss(): void
    {
        config()->set('radius.enabled', true);
        config()->set('radius.pure_radius', true);

        $tenant = $this->createTenant();
        $package = $this->createPackage($tenant);
        $router = $this->createRouter($tenant, [
            'status' => 'online',
            'last_seen_at' => now(),
        ]);

        $expiresAt = now()->subMinutes(4)->startOfSecond();
        $expectedGraceEndsAt = $expiresAt->copy()->addSeconds($package->grace_period_seconds);

        $session = UserSession::query()->create([
            'tenant_id' => $tenant->id,
            'router_id' => $router->id,
            'package_id' => $package->id,
            'username' => 'cb-grace-sync',
            'phone' => '0712345678',
            'status' => 'active',
            'started_at' => now()->subHour(),
            'expires_at' => $expiresAt,
            'grace_period_seconds' => $package->grace_period_seconds,
        ]);

        $radiusAccountingService = Mockery::mock(RadiusAccountingService::class);
        $radiusAccountingService->shouldReceive('syncActiveSession')->once()->withArgs(
            fn (UserSession $resolvedSession): bool => (int) $resolvedSession->id === (int) $session->id
        )->andReturnNull();

        $mikroTikService = Mockery::mock(MikroTikService::class);
        $sessionManager = Mockery::mock(SessionManager::class);
        $sessionManager->shouldNotReceive('terminateSession');

        (new SyncSessionUsage($session->id))->handle(
            $mikroTikService,
            $radiusAccountingService,
            $sessionManager
        );

        $session->refresh();

        $this->assertTrue($session->grace_period_active);
        $this->assertSame('active', $session->status);
        $this->assertSame($expectedGraceEndsAt->toDateTimeString(), $session->grace_period_ends_at?->toDateTimeString());
        $this->assertFalse($session->shouldDisconnect());
    }

    public function test_expired_radius_session_is_marked_expired_when_disconnect_ack_is_missing_after_revocation(): void
    {
        config()->set('radius.enabled', true);
        config()->set('radius.pure_radius', true);

        $tenant = $this->createTenant();
        $package = $this->createPackage($tenant);
        $router = $this->createRouter($tenant, [
            'status' => 'online',
            'last_seen_at' => now(),
        ]);

        $session = UserSession::query()->create([
            'tenant_id' => $tenant->id,
            'router_id' => $router->id,
            'package_id' => $package->id,
            'username' => 'cb-expired-radius',
            'phone' => '0712345678',
            'status' => 'active',
            'started_at' => now()->subHour(),
            'expires_at' => now()->subMinute(),
            'metadata' => [
                'radius' => [
                    'provisioned' => true,
                    'username' => 'cb-expired-radius',
                ],
            ],
        ]);

        $radiusDisconnectService = Mockery::mock(RadiusDisconnectService::class);
        $radiusDisconnectService->shouldReceive('disconnect')
            ->once()
            ->withArgs(fn (UserSession $resolvedSession): bool => (int) $resolvedSession->id === (int) $session->id)
            ->andReturn([
                'success' => false,
                'error' => 'No reply from NAS',
                'nas_ip' => $router->ip_address,
                'port' => 3799,
                'used_accounting_record' => false,
                'attributes' => [
                    'User-Name' => 'cb-expired-radius',
                ],
            ]);

        $radiusProvisioning = Mockery::mock(FreeRadiusProvisioningService::class);
        $radiusProvisioning->shouldReceive('revokeUser')
            ->once()
            ->with('cb-expired-radius');

        $mikroTikService = Mockery::mock(MikroTikService::class);

        $manager = new SessionManager($mikroTikService, $radiusDisconnectService, $radiusProvisioning);

        $result = $manager->terminateSession($session, 'expired');

        $this->assertTrue($result);

        $session->refresh();

        $this->assertSame('expired', $session->status);
        $this->assertSame('expired', $session->termination_reason);
        $this->assertNotNull($session->terminated_at);
    }

    public function test_router_walled_garden_rules_include_portal_and_daraja_hosts_without_intasend(): void
    {
        config()->set('app.url', 'https://app.cloudbridge.network');

        $tenant = $this->createTenant();
        $router = $this->createRouter($tenant);
        $router->forceFill([
            'captive_portal_url' => 'https://portal.example.com/wifi',
        ]);

        $rules = $router->walled_garden_rules;

        $this->assertContains('app.cloudbridge.network', $rules);
        $this->assertContains('portal.example.com', $rules);
        $this->assertContains('api.safaricom.co.ke', $rules);
        $this->assertContains('sandbox.safaricom.co.ke', $rules);
        $this->assertNotContains('intasend.com', $rules);
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

    private function createRouter(Tenant $tenant, array $overrides = []): Router
    {
        return Router::query()->create(array_merge([
            'tenant_id' => $tenant->id,
            'name' => 'Test Router',
            'model' => 'RB750Gr3',
            'ip_address' => '10.10.10.10',
            'api_port' => 8728,
            'api_username' => 'admin',
            'api_password' => 'plain-test-password',
            'status' => 'online',
            'last_seen_at' => now(),
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

    private function createVoucher(Tenant $tenant, Package $package, array $overrides = []): \App\Models\Voucher
    {
        $columns = array_flip(Schema::getColumnListing('vouchers'));
        $candidatePayload = array_merge([
            'tenant_id' => $tenant->id,
            'package_id' => $package->id,
            'code' => 'VCH' . strtoupper(str()->random(8)),
            'prefix' => 'CB',
            'status' => \App\Models\Voucher::STATUS_UNUSED,
            'valid_from' => now()->subMinute(),
            'valid_until' => now()->addDay(),
            'validity_hours' => 24,
            'captive_portal_redeemable' => true,
            'max_redemptions' => 1,
            'redemption_count' => 0,
        ], $overrides);

        $payload = [];
        foreach ($candidatePayload as $column => $value) {
            if (isset($columns[$column])) {
                $payload[$column] = $value;
            }
        }

        return \App\Models\Voucher::query()->create($payload);
    }

    private function configureRadiusProvisioningConnection(): void
    {
        config()->set('radius.db_connection', 'radius');
        config()->set('radius.tables.radcheck', 'radcheck');
        config()->set('radius.tables.radreply', 'radreply');
        config()->set('database.connections.radius', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            'foreign_key_constraints' => true,
        ]);

        DB::purge('radius');

        $schema = Schema::connection('radius');
        foreach (['radcheck', 'radreply'] as $table) {
            if ($schema->hasTable($table)) {
                $schema->drop($table);
            }
        }

        $schema->create('radcheck', function (Blueprint $table): void {
            $table->id();
            $table->string('username');
            $table->string('attribute');
            $table->string('op', 8)->default(':=');
            $table->text('value')->nullable();
        });

        $schema->create('radreply', function (Blueprint $table): void {
            $table->id();
            $table->string('username');
            $table->string('attribute');
            $table->string('op', 8)->default(':=');
            $table->text('value')->nullable();
        });
    }

    private function expectedPhoneRadiusUsername(Payment $payment): string
    {
        return (string) app(RadiusIdentityResolver::class)->resolve(
            phone: (string) $payment->phone,
            paymentId: (int) $payment->id,
            macAddress: null
        )['username'];
    }

    private function configureRadiusAccountingConnection(): void
    {
        config()->set('radius.db_connection', 'radius');
        config()->set('radius.tables.radacct', 'radacct');
        config()->set('database.connections.radius', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            'foreign_key_constraints' => true,
        ]);

        DB::purge('radius');

        $schema = Schema::connection('radius');
        if ($schema->hasTable('radacct')) {
            $schema->drop('radacct');
        }

        $schema->create('radacct', function (Blueprint $table): void {
            $table->id();
            $table->string('acctsessionid')->nullable();
            $table->string('acctuniqueid')->nullable();
            $table->string('username')->nullable();
            $table->string('nasipaddress')->nullable();
            $table->dateTime('acctstarttime')->nullable();
            $table->dateTime('acctupdatetime')->nullable();
            $table->dateTime('acctstoptime')->nullable();
            $table->unsignedBigInteger('acctinputoctets')->nullable();
            $table->unsignedBigInteger('acctoutputoctets')->nullable();
            $table->string('callingstationid')->nullable();
            $table->string('framedipaddress')->nullable();
        });
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function seedRadiusAccountingRecord(array $overrides = []): void
    {
        DB::connection('radius')->table('radacct')->insert(array_merge([
            'acctsessionid' => 'sess-001',
            'acctuniqueid' => 'unique-001',
            'username' => 'AA:BB:CC:DD:EE:FF',
            'nasipaddress' => '159.65.18.32',
            'acctstarttime' => now()->subMinute()->toDateTimeString(),
            'acctupdatetime' => now()->toDateTimeString(),
            'acctstoptime' => null,
            'acctinputoctets' => 1024,
            'acctoutputoctets' => 2048,
            'callingstationid' => 'AA:BB:CC:DD:EE:FF',
            'framedipaddress' => '10.0.0.25',
        ], $overrides));
    }
}
