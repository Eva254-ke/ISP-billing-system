<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Models\Package;
use App\Models\Payment;
use App\Models\Tenant;
use App\Models\Router;
use App\Models\Voucher;
use App\Models\UserSession;
use App\Services\MikroTik\MikroTikService;
use App\Services\MikroTik\SessionManager;
use App\Services\Radius\FreeRadiusProvisioningService;
use App\Services\Radius\RadiusAccountingService;
use App\Services\Radius\RadiusIdentityResolver;
use App\Services\Mpesa\DarajaService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class CaptivePortalController extends Controller
{
    private const KENYA_PHONE_REGEX = '/^(?:0[17]\d{8}|(?:\+?254)[17]\d{8})$/';

    public function __construct(
        private MikroTikService $mikrotik,
        private DarajaService $daraja
    ) {}

    public function index(Request $request)
    {
        try {
            $tenant = $this->resolveTenant($request);
            if (!$tenant) {
                return response('Network configuration error. Please contact support.', 400);
            }

            $mac = $this->cleanMac($request->query('mac') ?? $request->query('mac-address') ?? '');
            $ip = $request->query('ip') ?? $request->query('ip-address') ?? $request->ip();

            if (empty($mac) || strlen($mac) < 12) {
                return response('WiFi network error: Missing device MAC. Please "Forget" this WiFi network in your phone settings and reconnect.', 400);
            }

            $isConnected = $this->isDeviceAuthorized($mac, $ip);

            $packages = Package::where('tenant_id', $tenant->id)
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->get();

            return view('captive.portal', [
                'tenant' => $tenant,
                'packages' => $packages,
                'mac' => $mac,
                'ip' => $ip,
                'isConnected' => $isConnected,
            ]);
        } catch (\Throwable $e) {
            Log::error('Captive Portal Index Fatal Error', ['error' => $e->getMessage()]);
            return response('A temporary network error occurred. Please try again in a moment.', 500);
        }
    }

    public function initiate(Request $request): JsonResponse
    {
        try {
            $mac = $this->cleanMac($request->input('mac') ?? '');
            $ip = $request->input('ip') ?? $request->ip();

            if (empty($mac) || strlen($mac) < 12) {
                return response()->json([
                    'status' => 'failed', 
                    'message' => 'Missing device MAC. Please "Forget" this WiFi network and reconnect.'
                ], 422);
            }

            $request->merge(['mac' => $mac, 'ip' => $ip]);

            $request->validate([
                'mac' => 'required|string',
                'ip' => 'required|ip',
                'type' => 'required|in:mpesa,voucher',
                'phone' => [
                    'required_if:type,mpesa',
                    'regex:' . self::KENYA_PHONE_REGEX,
                ],
                'package_id' => 'required_if:type,mpesa|exists:packages,id',
                'code' => 'required_if:type,voucher|string',
            ]);

            if ($request->type === 'voucher') {
                return $this->processVoucher($request->code, $mac, $ip);
            }

            return $this->processMpesa($request->phone, $request->package_id, $mac, $ip);
        } catch (\Throwable $e) {
            Log::error('Initiate Fatal Error', ['error' => $e->getMessage()]);
            return response()->json(['status' => 'failed', 'message' => 'Server error. Please try again.'], 500);
        }
    }

    public function checkStatus(Payment $payment): JsonResponse
    {
        try {
            if (in_array($payment->status, ['pending', 'initiated'])) {
                if (method_exists($this->daraja, 'reconcilePayment')) {
                    $this->daraja->reconcilePayment($payment);
                    $payment->refresh();
                }
            }

            if (in_array($payment->status, ['completed', 'confirmed'])) {
                $metadata = is_array($payment->metadata) ? $payment->metadata : [];
                $pMac = $this->cleanMac($metadata['mac'] ?? '');
                $pIp = $metadata['ip'] ?? '';

                if ($pMac === '') {
                    Log::channel('radius')->warning('Payment confirmed but captive client MAC is missing', [
                        'payment_id' => $payment->id,
                        'tenant_id' => $payment->tenant_id,
                        'phone' => $payment->phone,
                    ]);

                    return response()->json([
                        'status' => 'pending',
                        'message' => 'Payment confirmed. Please forget this WiFi network and reconnect so we can identify your device.',
                    ]);
                }

                $ttlSeconds = $this->paymentTtlSeconds($payment);
                $session = $this->grantNetworkAccess($pMac, $pIp, $payment->tenant_id, $ttlSeconds, $payment);

                if ((bool) config('radius.enabled', false)) {
                    $accounting = app(RadiusAccountingService::class);
                    $session = $session ?: $this->findSessionForPaymentOrClient($payment, $pMac);
                    $record = $session ? $accounting->syncActiveSession($session) : $accounting->findOpenSession($pMac, $pMac, $pIp);

                    if ($record !== null) {
                        $this->markDeviceAuthorized($pMac, $pIp, $ttlSeconds);

                        return response()->json(['status' => 'connected', 'message' => 'Access granted!']);
                    }

                    // FIXED: In Pure RADIUS mode, return connected immediately
                    // FreeRADIUS will handle authentication when device connects through hotspot
                    if ($session) {
                        if ((bool) config('radius.pure_radius', false)) {
                            return response()->json([
                                'status' => 'connected',
                                'message' => 'Access granted! Connecting...',
                            ]);
                        }
                        
                        // Non-pure RADIUS mode: trigger MikroTik login via API
                        try {
                            $sessionManager = app(SessionManager::class);
                            $package = $session->package;
                            if ($package && (bool) config('radius.enabled', false)) {
                                Log::channel('mikrotik')->info('Triggering MikroTik login after payment confirmation', [
                                    'session_id' => $session->id,
                                    'payment_id' => $payment->id,
                                    'mac' => $pMac,
                                    'ip' => $pIp,
                                ]);
                                $sessionManager->activateSession($session, $package);
                            }
                        } catch (\Throwable $e) {
                            Log::channel('mikrotik')->warning('MikroTik login trigger failed', [
                                'session_id' => $session->id,
                                'error' => $e->getMessage(),
                            ]);
                        }
                    }

                    Log::channel('radius')->info('Payment confirmed; waiting for MikroTik RADIUS accounting', [
                        'payment_id' => $payment->id,
                        'tenant_id' => $payment->tenant_id,
                        'mac' => $pMac,
                        'ip' => $pIp,
                        'session_id' => $session?->id,
                    ]);

                    return response()->json([
                        'status' => 'pending',
                        'message' => 'Payment confirmed. Connecting your device to the internet...',
                    ]);
                }

                return response()->json(['status' => 'connected', 'message' => 'Access granted!']);
            }

            if ($payment->status === 'failed') {
                return response()->json(['status' => 'failed', 'message' => 'Payment failed. Please try again.']);
            }

            return response()->json(['status' => 'pending', 'message' => 'Waiting for M-Pesa confirmation...']);
        } catch (\Throwable $e) {
            Log::error('CheckStatus Fatal Error', ['error' => $e->getMessage()]);
            return response()->json(['status' => 'failed', 'message' => 'Server error checking status.'], 500);
        }
    }

    public function reconnect(Request $request): JsonResponse
    {
        try {
            $mac = $this->cleanMac($request->input('mac') ?? '');
            $ip = $request->input('ip') ?? $request->ip();

            if (empty($mac) || strlen($mac) < 12) {
                return response()->json([
                    'status' => 'failed', 
                    'message' => 'Missing device MAC. Please reconnect to the WiFi network.'
                ], 422);
            }

            $request->merge(['mac' => $mac, 'ip' => $ip]);

            $request->validate([
                'mac' => 'required|string',
                'ip' => 'required|ip',
                'code' => 'required|string',
            ]);

            $code = strtoupper(trim($request->code));
            
            // Extract receipt code from M-Pesa SMS if user pasted the full message
            if (strlen($code) < 10 && preg_match('/[A-Z0-9]{10,12}/', $request->code, $matches)) {
                $code = strtoupper($matches[0]);
            }

            $voucher = Voucher::where('code', $code)->whereNull('used_at')->first();
            if ($voucher) {
                $voucher->update(['used_by_mac' => $mac, 'used_at' => now()]);
                $this->grantNetworkAccess($mac, $ip, $voucher->tenant_id, ($voucher->duration_minutes ?? 1440) * 60);
                return response()->json(['status' => 'connected', 'message' => 'Voucher redeemed!']);
            }

            $payment = Payment::where(function($q) use ($code) {
                $q->where('mpesa_code', $code)
                  ->orWhere('mpesa_receipt_number', $code)
                  ->orWhere('mpesa_phone', str_replace('0', '254', $code))
                  ->orWhere('phone', str_replace('254', '0', $code));
            })->whereIn('status', ['completed', 'confirmed'])->first();
                
            if ($payment) {
                $this->rememberPaymentClient($payment, $mac, $ip);
                $this->grantNetworkAccess($mac, $ip, $payment->tenant_id, $this->paymentTtlSeconds($payment), $payment);
                return response()->json(['status' => 'connected', 'message' => 'Receipt verified!']);
            }

            return response()->json(['status' => 'failed', 'message' => 'Invalid or expired code.'], 400);
        } catch (\Throwable $e) {
            Log::error('Reconnect Fatal Error', ['error' => $e->getMessage()]);
            return response()->json(['status' => 'failed', 'message' => 'Server error. Please try again.'], 500);
        }
    }

    public function connect(Request $request)
    {
        $ua = $request->header('User-Agent', '');

        if (str_contains($ua, 'CaptiveNetworkSupport') || str_contains($ua, 'iPhone') || str_contains($ua, 'iPad') || str_contains($ua, 'Mac OS X')) {
            return response('<HTML><HEAD><TITLE>Success</TITLE></HEAD><BODY>Success</BODY></HTML>', 200)
                ->header('Content-Type', 'text/html; charset=UTF-8');
        }

        return response('', 204);
    }

    // =========================================================================
    // PRIVATE HELPERS
    // =========================================================================

    private function processMpesa(string $phone, int $packageId, string $mac, string $ip): JsonResponse
    {
        $package = Package::findOrFail($packageId);
        $normalizedPhone = $this->normalizePhone($phone);

        $recentPayment = Payment::where('phone', $normalizedPhone)
            ->where('package_id', $packageId)
            ->where('status', 'pending')
            ->where('created_at', '>=', now()->subMinutes(2))
            ->first();

        if ($recentPayment) {
            return response()->json([
                'status' => 'pending', 
                'payment_id' => $recentPayment->id,
                'message' => 'An M-Pesa prompt is already active. Check your phone.'
            ]);
        }

        // FIX: Set checkout_request_id to null initially - will be populated from real Daraja response
        $payment = Payment::create([
            'tenant_id' => $package->tenant_id,
            'phone' => $normalizedPhone,
            'customer_name' => 'WiFi Guest',
            'package_id' => $package->id,
            'package_name' => $package->name, 
            'amount' => $package->price,
            'currency' => $package->currency ?? 'KES',
            'mpesa_checkout_request_id' => null,
            'status' => 'pending',
            'type' => 'captive_portal',
            'initiated_at' => now(),
            'payment_channel' => 'captive_portal',
            'metadata' => [
                'gateway' => 'mpesa',
                'created_via' => 'captive_portal',
                'mac' => $mac, 
                'ip' => $ip, 
                'package_name' => $package->name,
                'client_context' => ['mac' => $mac, 'ip' => $ip]
            ]
        ]);

        try {
            $triggered = false;
            $stkResult = null;
            
            if (method_exists($this->daraja, 'initiateStkPush')) {
                try {
                    $stkResult = $this->daraja->initiateStkPush($payment, $normalizedPhone, $package->price);
                    $triggered = true;
                } catch (\ArgumentCountError $e) {
                    $stkResult = $this->daraja->initiateStkPush($normalizedPhone, $package->price, $payment->id);
                    $triggered = true;
                }
            } elseif (method_exists($this->daraja, 'stkPush')) {
                $stkResult = $this->daraja->stkPush($normalizedPhone, $package->price, $payment->id);
                $triggered = true;
            } elseif (method_exists($this->daraja, 'sendStkPush')) {
                $stkResult = $this->daraja->sendStkPush($normalizedPhone, $package->price);
                $triggered = true;
            }
            
            if (!$triggered) {
                throw new \Exception("DarajaService is missing a recognized STK push method.");
            }

            // FIX: Save the REAL CheckoutRequestID from Safaricom response
            // Daraja returns lowercase keys, so we check for lowercase
            if (is_array($stkResult) && !empty($stkResult['checkout_request_id'])) {
                $payment->update([
                    'mpesa_checkout_request_id' => $stkResult['checkout_request_id'],
                    'metadata' => array_merge(is_array($payment->metadata) ? $payment->metadata : [], [
                        'daraja_merchant_request_id' => $stkResult['merchant_request_id'] ?? null,
                        'daraja_response_code' => $stkResult['response_code'] ?? null,
                        'daraja_response_description' => $stkResult['response_description'] ?? null,
                        'daraja_customer_message' => $stkResult['customer_message'] ?? null,
                    ]),
                ]);

                Log::channel('payment')->info('Captured real Daraja CheckoutRequestID', [
                    'payment_id' => $payment->id,
                    'checkout_request_id' => $stkResult['checkout_request_id'],
                    'merchant_request_id' => $stkResult['merchant_request_id'] ?? null,
                    'response_code' => $stkResult['response_code'] ?? null,
                ]);
            } elseif (is_object($stkResult)) {
                // Handle object response (some Daraja implementations return objects)
                $checkoutId = $stkResult->checkout_request_id ?? ($stkResult->CheckoutRequestID ?? null);
                $merchantId = $stkResult->merchant_request_id ?? ($stkResult->MerchantRequestID ?? null);
                
                if ($checkoutId) {
                    $payment->update([
                        'mpesa_checkout_request_id' => $checkoutId,
                        'metadata' => array_merge(is_array($payment->metadata) ? $payment->metadata : [], [
                            'daraja_merchant_request_id' => $merchantId,
                        ]),
                    ]);

                    Log::channel('payment')->info('Captured real Daraja CheckoutRequestID (object response)', [
                        'payment_id' => $payment->id,
                        'checkout_request_id' => $checkoutId,
                    ]);
                }
            }

        } catch (\Throwable $e) {
            Log::error('STK Push failed', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            $payment->update(['status' => 'failed']);
            
            return response()->json([
                'status' => 'failed', 
                'message' => 'M-Pesa Error: ' . $e->getMessage()
            ], 500);
        }

        return response()->json([
            'status' => 'pending', 
            'payment_id' => $payment->id,
            'message' => 'M-Pesa prompt sent. Enter your PIN.'
        ]);
    }

    private function processVoucher(string $code, string $mac, string $ip): JsonResponse
    {
        $voucher = Voucher::where('code', strtoupper(trim($code)))->whereNull('used_at')->first();

        if (!$voucher) {
            return response()->json(['status' => 'failed', 'message' => 'Invalid or used voucher.'], 400);
        }

        $voucher->update(['used_by_mac' => $mac, 'used_at' => now()]);
        $this->grantNetworkAccess($mac, $ip, $voucher->tenant_id, ($voucher->duration_minutes ?? 1440) * 60);

        return response()->json(['status' => 'connected', 'message' => 'Voucher redeemed!']);
    }

    private function grantNetworkAccess(string $mac, string $ip, int $tenantId, int $ttlSeconds = 86400, ?Payment $payment = null): ?UserSession
    {
        try {
            $mac = $this->cleanMac($mac);
            $ip = trim($ip);
            $radiusEnabled = (bool) config('radius.enabled', false);
            $package = null;
            $phone = null;

            if ($payment && !in_array((string) $payment->status, ['completed', 'confirmed'], true)) {
                Log::warning('Refusing to activate network access for unconfirmed payment', [
                    'payment_id' => $payment->id,
                    'tenant_id' => $payment->tenant_id,
                    'status' => $payment->status,
                    'mac' => $mac,
                    'ip' => $ip,
                ]);

                return null;
            }

            if ($payment && $payment->package) {
                $this->rememberPaymentClient($payment, $mac, $ip);
                $package = $payment->package;
                $phone = $payment->phone;
            } else {
                $foundPayment = Payment::where('tenant_id', $tenantId)
                    ->where('metadata->mac', $mac)
                    ->whereIn('status', ['completed', 'confirmed'])
                    ->latest()
                    ->first();

                if ($foundPayment && $foundPayment->package) {
                    $package = $foundPayment->package;
                    $phone = $foundPayment->phone;
                    $payment = $foundPayment;
                } else {
                    // FIX: Use 'status' field instead of 'is_used' boolean
                    $voucher = Voucher::where('tenant_id', $tenantId)
                        ->where('used_by_mac', $mac)
                        ->where('status', 'used')
                        ->latest()
                        ->first();
                    
                    if ($voucher && $voucher->package) {
                        $package = $voucher->package;
                        $phone = $voucher->used_by_phone ?? 'Voucher User';
                    }
                }
            }

            if (!$package) {
                Log::warning('Cannot activate session: No valid package found for MAC', ['mac' => $mac, 'ip' => $ip]);
                return null;
            }

            $ttlSeconds = $this->packageTtlSeconds($package, $ttlSeconds);
            $expiresAt = now()->addSeconds($ttlSeconds);

            // Find the router for this tenant
            $router = Router::where('tenant_id', $tenantId)->first();

            // Create or update the UserSession record in the database
            $session = UserSession::updateOrCreate(
                [
                    'tenant_id' => $tenantId,
                    'mac_address' => $mac,
                ],
                [
                    'router_id' => $router?->id,
                    'ip_address' => $ip,
                    'phone' => $phone,
                    'username' => $mac, 
                    'status' => $radiusEnabled ? 'idle' : 'active',
                    'package_id' => $package->id,
                    'payment_id' => $payment?->id,
                    'started_at' => $radiusEnabled ? null : now(),
                    'expires_at' => $expiresAt,
                    'last_activity_at' => now(),
                    'metadata' => $this->sessionMetadataForPendingRadius($mac, $ip, $payment),
                ]
            );

            // USE RADIUS WITH NORMALIZED MAC
            if ($radiusEnabled) {
                $radiusService = app(FreeRadiusProvisioningService::class);
                $identityResolver = app(RadiusIdentityResolver::class);
                
                // Normalize MAC address to match what MikroTik expects
                $normalizedMac = $identityResolver->normalizeMacAddress($mac) ?? $mac;

                $radiusService->provisionUser(
                    $normalizedMac,           // username (normalized MAC address)
                    $normalizedMac,           // password (same as username for simplicity)
                    $package,                 // Package object
                    $expiresAt,               // Expiration time
                    $normalizedMac            // callingStationId (normalized MAC address)
                );
                
                Log::channel('radius')->info('RADIUS access provisioned with normalized MAC; waiting for hotspot accounting', [
                    'original_mac' => $mac,
                    'normalized_mac' => $normalizedMac,
                    'ip' => $ip,
                    'payment_id' => $payment?->id,
                    'session_id' => $session->id,
                    'expires_at' => $expiresAt->toIso8601String()
                ]);
            } else {
                $this->markDeviceAuthorized($mac, $ip, $ttlSeconds);

                // Fallback to SessionManager if RADIUS is disabled
                $sessionManager = app(SessionManager::class);
                $result = $sessionManager->activateSession($session, $package);
                
                Log::info('Network access granted via SessionManager', [
                    'mac' => $mac, 
                    'ip' => $ip,
                    'session_id' => $session->id,
                    'session_manager_result' => $result
                ]);
            }

            return $session;

        } catch (\Throwable $e) {
            Log::error('Session activation failed', [
                'error' => $e->getMessage(), 
                'trace' => $e->getTraceAsString(),
                'mac' => $mac, 
                'ip' => $ip,
                'tenant_id' => $tenantId
            ]);

            return null;
        }
    }

    private function isDeviceAuthorized(string $mac, string $ip): bool
    {
        if (!$mac && !$ip) return false;
        try {
            if ((bool) config('radius.enabled', false)) {
                $record = app(RadiusAccountingService::class)->findOpenSession($mac, $mac, $ip);
                if ($record !== null) {
                    $this->markDeviceAuthorized($mac, $ip, 300);
                    return true;
                }

                return false;
            }

            return ($mac && Cache::get("wifi:auth:{$mac}")) 
                || ($ip && Cache::get("wifi:auth:{$ip}"));
        } catch (\Throwable $e) {
            return false;
        }
    }

    private function markDeviceAuthorized(string $mac, string $ip, int $ttlSeconds): void
    {
        try {
            Cache::put("wifi:auth:{$mac}", $ip, $ttlSeconds);
            if ($ip !== '') {
                Cache::put("wifi:auth:{$ip}", $mac, $ttlSeconds);
            }
        } catch (\Throwable $e) {
            Log::warning('Cache set failed', ['error' => $e->getMessage()]);
        }
    }

    private function findSessionForPaymentOrClient(Payment $payment, string $mac): ?UserSession
    {
        return UserSession::query()
            ->where('tenant_id', $payment->tenant_id)
            ->where(function ($query) use ($payment, $mac): void {
                $query->where('payment_id', $payment->id)
                    ->orWhere('mac_address', $mac);
            })
            ->latest('id')
            ->first();
    }

    private function rememberPaymentClient(Payment $payment, string $mac, string $ip): void
    {
        $metadata = is_array($payment->metadata) ? $payment->metadata : [];

        if (($metadata['mac'] ?? null) === $mac && ($metadata['ip'] ?? null) === $ip) {
            return;
        }

        $metadata['mac'] = $mac;
        $metadata['ip'] = $ip;

        $payment->forceFill(['metadata' => $metadata])->save();
    }

    private function paymentTtlSeconds(Payment $payment): int
    {
        return $this->packageTtlSeconds($payment->package, 86400);
    }

    private function packageTtlSeconds(?Package $package, int $fallback): int
    {
        if (!$package) {
            return max(60, $fallback);
        }

        return max(60, (int) ($package->duration_in_minutes ?? 0) * 60);
    }

    private function sessionMetadataForPendingRadius(string $mac, string $ip, ?Payment $payment): array
    {
        return [
            'radius' => [
                'authorization_prepared' => true,
                'authorization_mode' => 'mac',
                'username' => $mac,
                'calling_station_id' => $mac,
                'framed_ip_address' => $ip,
                'waiting_for_hotspot_login' => true,
                'waiting_for_reauth' => true,
                'authorization_started_at' => now()->toIso8601String(),
                'last_attempt_at' => now()->toIso8601String(),
            ],
            'activation' => [
                'payment_id' => $payment?->id,
                'waiting_for_hotspot_login' => true,
                'waiting_for_reauth' => true,
            ],
        ];
    }

    private function resolveTenant(Request $request): ?Tenant
    {
        if ($id = $request->query('tenant_id')) return Tenant::find($id);

        $host = $request->getHost();
        if (!in_array($host, ['localhost', '127.0.0.1'])) {
            $parts = explode('.', $host);
            if (count($parts) >= 3) return Tenant::where('subdomain', $parts[0])->first();
            return Tenant::where('domain', $host)->first();
        }
        return Tenant::first(); 
    }

    private function cleanMac(?string $mac): string
    {
        if ($mac === null) return '';
        $mac = strtoupper(preg_replace('/[^A-Fa-f0-9]/', '', $mac));
        if (strlen($mac) < 12) return ''; 
        return implode(':', str_split($mac, 2));
    }

    private function normalizePhone(string $phone): string
    {
        $phone = preg_replace('/\D/', '', $phone);
        if (str_starts_with($phone, '254')) return '0' . substr($phone, 3);
        return $phone;
    }
}