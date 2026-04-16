<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Package;
use App\Models\Payment;
use App\Models\Tenant;
use App\Models\UserSession;
use App\Models\Voucher;
use App\Services\MikroTik\SessionManager;
use App\Services\Mpesa\DarajaService;
use App\Services\Radius\FreeRadiusProvisioningService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class CaptivePortalController extends Controller
{
    private const STATUS_CHANNELS = ['captive_portal', 'session_extension', 'voucher'];
    private const STATUS_PRIORITIES = ['initiated', 'pending', 'confirmed', 'completed', 'activated'];
    private const KENYA_PHONE_REGEX = '/^(?:0[17]\d{8}|(?:\+?254)[17]\d{8})$/';
    private const VERIFICATION_WINDOW_MINUTES = 20;

    /**
     * Show package selection page (public, no auth required)
     */
    public function packages(Request $request)
    {
        $tenant = $this->resolveTenant($request);
        $phoneInput = session('captive_phone') ?? $request->query('phone');
        $phone = $phoneInput !== null
            ? ($this->normalizePhoneForStorage((string) $phoneInput) ?? trim((string) $phoneInput))
            : null;
        $mode = strtolower(trim((string) $request->query('mode', '')));
        $showReconnectScreen = $mode === 'reconnect';

        if (!$tenant) {
            return response()->view($showReconnectScreen ? 'captive.reconnect' : 'captive.packages', [
                'packages' => collect(),
                'activeSession' => null,
                'phone' => $phone,
                'tenant' => null,
                'tenantResolutionError' => 'Tenant portal not resolved. Use your tenant domain (e.g. https://your-subdomain.cloudbridge.network/wifi) or include tenant_id in the URL.',
            ], 400);
        }

        session(['captive_tenant_id' => $tenant->id]);

        if ($showReconnectScreen) {
            return view('captive.reconnect', compact('phone', 'tenant'));
        }
        
        $activeSession = null;
        if ($phone) {
            $activeSession = UserSession::where('tenant_id', $tenant->id)
                ->where('phone', $phone)
                ->active()
                ->first();
        }

        $packagesQuery = Package::query()
            ->where('tenant_id', $tenant->id)
            ->where('is_active', true);

        $packages = $packagesQuery
            ->orderByRaw('COALESCE(sort_order, 999999) asc')
            ->orderBy('sort_order')
            ->orderBy('price')
            ->get();

        return view('captive.packages', compact('packages', 'activeSession', 'phone', 'tenant'));
    }
    
    /**
     * Process payment request
     */
    public function pay(Request $request)
    {
        $tenantId = $this->resolveTenantId($request);
        $gateway = $this->resolvePaymentGateway();

        if ($tenantId <= 0) {
            return back()->withErrors(['Tenant portal not resolved. Reopen your WiFi portal and try again.']);
        }

        $request->validate([
            'phone' => ['required', 'regex:' . self::KENYA_PHONE_REGEX],
            'package_id' => ['required', 'exists:packages,id'],
        ]);

        $phone = $this->normalizePhoneForStorage((string) $request->phone);
        if ($phone === null) {
            return back()
                ->withErrors(['Use a valid Safaricom number: 07XXXXXXXX, 01XXXXXXXX, +2547XXXXXXXX or +2541XXXXXXXX.'])
                ->withInput();
        }

        $package = Package::query()
            ->when($tenantId > 0, fn ($query) => $query->where('tenant_id', $tenantId))
            ->where('is_active', true)
            ->findOrFail($request->package_id);

        if (!$this->isGatewayConfigured($gateway)) {
            return back()->withErrors(['Payment gateway is not configured. Please contact support.']);
        }
        
        $payment = DB::transaction(function () use ($phone, $package, $gateway) {
            return Payment::create([
                'tenant_id' => $package->tenant_id,
                'phone' => $phone,
                'package_id' => $package->id,
                'package_name' => $package->name,
                'amount' => $package->price,
                'currency' => $package->currency ?? 'KES',
                'mpesa_checkout_request_id' => 'CP-' . strtoupper(uniqid()),
                'status' => 'pending',
                'initiated_at' => now(),
                'payment_channel' => 'captive_portal',
                'metadata' => [
                    'gateway' => $gateway,
                    'created_via' => 'captive_portal',
                ],
            ]);
        });
        
        Log::info('Captive payment initiated', [
            'phone' => $phone,
            'package' => $package->name,
            'amount' => $package->price,
            'reference' => $payment->mpesa_checkout_request_id,
        ]);
        
        session([
            'captive_phone' => $phone,
            'captive_tenant_id' => $package->tenant_id,
            'captive_payment_id' => $payment->id,
        ]);
        
        try {
            $response = $this->initiateStkPush(
                payment: $payment,
                package: $package,
                phone: $phone,
                flow: 'captive_portal'
            );

            if ($response['success']) {
                return redirect()->route('wifi.status', $this->buildStatusRouteParameters(
                        phone: $phone,
                        payment: $payment,
                        tenantId: $package->tenant_id
                    ))
                    ->with('message', (string) ($response['user_message'] ?? 'Check your phone to complete payment'));
            }

            if ($response['redirect_to_status'] ?? false) {
                return redirect()->route('wifi.status', $this->buildStatusRouteParameters(
                        phone: $phone,
                        payment: $payment,
                        tenantId: $package->tenant_id
                    ))
                    ->with('message', (string) ($response['user_message'] ?? 'We are verifying your payment request.'));
            }

            return back()->withErrors([(string) ($response['error'] ?? 'Payment initiation failed. Try again.')]);
            
        } catch (\Exception $e) {
            Log::error('Captive portal STK initiation exception', [
                'error' => $e->getMessage(),
                'phone' => $phone,
                'gateway' => $gateway,
            ]);
            return back()->withErrors(['Payment service unavailable. Please try again.']);
        }
    }

    private function resolveTenant(Request $request): ?Tenant
    {
        // 1) Explicit tenant_id from query has highest priority.
        $queryTenantId = (int) $request->query('tenant_id', 0);
        if ($queryTenantId > 0) {
            return Tenant::active()->find($queryTenantId);
        }

        // 2) Tenant domain/subdomain mapping for public captive traffic.
        $host = strtolower((string) $request->getHost());
        if ($host && !in_array($host, ['localhost', '127.0.0.1'], true)) {
            $identifier = preg_replace('/:\d+$/', '', $host);

            $tenant = Tenant::findBySubdomainOrDomain($identifier);
            if ($tenant) {
                return $tenant;
            }

            $parts = explode('.', $identifier);
            if (count($parts) >= 3) {
                $tenant = Tenant::findBySubdomainOrDomain($parts[0]);
                if ($tenant) {
                    return $tenant;
                }
            }
        }

        // Production safety: never fall back to session/arbitrary tenant/admin auth user.
        return null;
    }

    private function resolveTenantId(Request $request): int
    {
        $tenant = $this->resolveTenant($request);
        if ($tenant) {
            session(['captive_tenant_id' => $tenant->id]);
            return (int) $tenant->id;
        }

        $sessionTenantId = (int) session('captive_tenant_id', 0);
        if ($sessionTenantId > 0 && Tenant::active()->whereKey($sessionTenantId)->exists()) {
            return $sessionTenantId;
        }

        return 0;
    }

    private function buildStatusPaymentQuery(string $phone, int $tenantId)
    {
        return Payment::query()
            ->where('phone', $phone)
            ->whereIn('payment_channel', self::STATUS_CHANNELS)
            ->when($tenantId > 0, fn ($query) => $query->where('tenant_id', $tenantId));
    }

    private function buildStatusRouteParameters(string $phone, Payment $payment, ?int $tenantId = null, array $extra = []): array
    {
        return array_filter(array_merge([
            'phone' => $phone,
            'tenant_id' => ($tenantId ?? (int) $payment->tenant_id) > 0 ? ($tenantId ?? (int) $payment->tenant_id) : null,
            'payment' => $payment->id,
        ], $extra), static fn ($value) => $value !== null && $value !== '');
    }

    private function resolveRequestedStatusPayment(Request $request, string $phone, int $tenantId): ?Payment
    {
        $paymentQuery = $this->buildStatusPaymentQuery($phone, $tenantId);

        $requestedPaymentId = (int) $request->query('payment', 0);
        if ($requestedPaymentId > 0) {
            return (clone $paymentQuery)
                ->whereKey($requestedPaymentId)
                ->first();
        }

        $sessionPaymentId = (int) session('captive_payment_id', 0);
        if ($sessionPaymentId > 0) {
            return (clone $paymentQuery)
                ->whereKey($sessionPaymentId)
                ->first();
        }

        return null;
    }

    private function resolveStatusPayment(Request $request, string $phone, int $tenantId): ?Payment
    {
        $requestedPayment = $this->resolveRequestedStatusPayment($request, $phone, $tenantId);
        if ($requestedPayment) {
            return $requestedPayment;
        }

        $paymentQuery = $this->buildStatusPaymentQuery($phone, $tenantId);

        $payment = (clone $paymentQuery)
            ->whereIn('status', self::STATUS_PRIORITIES)
            ->orderBy('created_at', 'desc')
            ->first();

        if ($payment) {
            return $payment;
        }

        return (clone $paymentQuery)
            ->orderBy('created_at', 'desc')
            ->first();
    }

    private function paymentNeedsVerification(Payment $payment): bool
    {
        if ((string) $payment->status !== 'failed') {
            return false;
        }

        if (!empty($payment->mpesa_receipt_number)) {
            return false;
        }

        $metadata = is_array($payment->metadata) ? $payment->metadata : [];
        $lastStatus = trim((string) ($metadata['daraja_last_status'] ?? ''));
        $callbackResultCode = data_get($payment->callback_data, 'ResultCode');
        $hasFinalFailureFromCallback = is_numeric($callbackResultCode) && (int) $callbackResultCode !== 0;
        $recentFailure = $payment->failed_at?->gt(now()->subMinutes(self::VERIFICATION_WINDOW_MINUTES)) ?? false;

        if ($hasFinalFailureFromCallback || in_array($lastStatus, ['failed_via_query', 'rejected_by_gateway'], true)) {
            return false;
        }

        return $recentFailure;
    }

    private function deriveStatusView(Payment $payment, ?UserSession $session = null): string
    {
        $statusView = (string) $payment->status;
        $metadata = is_array($payment->metadata) ? $payment->metadata : [];

        if ($statusView === 'initiated') {
            $statusView = 'pending';
        }

        if ($statusView === 'pending' && trim((string) ($metadata['daraja_last_status'] ?? '')) === 'pending_verification') {
            $statusView = 'verifying';
        }

        if ($statusView === 'failed' && $this->paymentNeedsVerification($payment)) {
            $statusView = 'verifying';
        }

        if ($session && in_array($statusView, ['completed', 'confirmed', 'paid', 'activated'], true)) {
            return 'activated';
        }

        if (in_array($statusView, ['completed', 'confirmed'], true) && !$session) {
            return 'paid';
        }

        return $statusView;
    }
    
    /**
     * Check payment status
     */
    public function status(Request $request, $phone)
    {
        $phone = $this->normalizePhoneForStorage((string) $phone) ?? (string) $phone;
        $tenantId = $this->resolveTenantId($request);
        $paymentQuery = $this->buildStatusPaymentQuery($phone, $tenantId);
        $requestedPayment = $this->resolveRequestedStatusPayment($request, $phone, $tenantId);

        if ($tenantId === 0 && !$requestedPayment) {
            $tenantMatches = (clone $paymentQuery)->select('tenant_id')->distinct()->count('tenant_id');
            if ($tenantMatches > 1) {
                return redirect()->route('wifi.packages')
                    ->withErrors(['Unable to determine this hotspot portal. Reopen WiFi portal and try again.']);
            }
        }

        $payment = $requestedPayment ?? $this->resolveStatusPayment($request, $phone, $tenantId);

        if (!$payment) {
            return redirect()->route('wifi.packages')
                ->withErrors(['No payment found. Please start again.']);
        }

        session([
            'captive_phone' => $phone,
            'captive_payment_id' => $payment->id,
        ]);

        $this->reconcileDarajaPaymentIfNeeded($payment, $request->boolean('recheck'));
        $payment = $payment->fresh();

        if (in_array($payment->status, ['completed', 'confirmed'], true)) {
            try {
                $this->activatePaidAccess($payment);
                
            } catch (\Exception $e) {
                Log::error('MikroTik activation failed', [
                    'payment_id' => $payment->id,
                    'error' => $e->getMessage()
                ]);
            }
        }

        $activeSession = UserSession::where('payment_id', $payment->id)
            ->active()
            ->first();

        $statusView = $this->deriveStatusView($payment, $activeSession);

        $radiusFallback = null;
        if ($statusView === 'paid' && !$activeSession && (bool) config('radius.enabled', false)) {
            $radiusFallback = [
                'username' => $this->resolveRadiusUsernameFromPhone($payment->phone, $payment->id),
                'password_hint' => 'Use the same value as username',
            ];
        }
        
        return view('captive.status', compact('payment', 'phone', 'activeSession', 'statusView', 'radiusFallback'));
    }
    
    /**
     * Reconnect with M-Pesa transaction code
     */
    public function reconnect(Request $request)
    {
        $tenantId = $this->resolveTenantId($request);

        if ($tenantId <= 0) {
            return back()->withErrors(['Tenant portal not resolved. Reopen your WiFi portal and try again.']);
        }

        if ($request->filled('voucher_code')) {
            $request->validate([
                'voucher_code' => ['required', 'string', 'max:64'],
                'phone' => ['required', 'regex:' . self::KENYA_PHONE_REGEX],
            ]);

            $phone = $this->normalizePhoneForStorage((string) $request->phone);
            if ($phone === null) {
                return redirect()->back()
                    ->withErrors(['Use a valid Safaricom number: 07XXXXXXXX, 01XXXXXXXX, +2547XXXXXXXX or +2541XXXXXXXX.'])
                    ->withInput();
            }
            $voucherInput = strtoupper(trim((string) $request->voucher_code));
            $codeCandidate = strtoupper(substr($voucherInput, strrpos('-' . $voucherInput, '-') + 1));

            $voucher = Voucher::query()
                ->when($tenantId > 0, fn ($query) => $query->where('tenant_id', $tenantId))
                ->where('status', Voucher::STATUS_UNUSED)
                ->where(function ($query) {
                    $query->whereNull('valid_from')
                        ->orWhere('valid_from', '<=', now());
                })
                ->where(function ($query) {
                    $query->whereNull('valid_until')
                        ->orWhere('valid_until', '>', now());
                })
                ->where(function ($query) use ($voucherInput, $codeCandidate) {
                    $query->whereRaw('UPPER(code) = ?', [$voucherInput])
                        ->orWhereRaw('UPPER(code) = ?', [$codeCandidate]);
                })
                ->with('package')
                ->first();

            if (!$voucher || !$voucher->package || !$voucher->package->is_active) {
                return redirect()->back()
                    ->withErrors(['Invalid or expired voucher code.'])
                    ->withInput();
            }

            $activeSession = UserSession::where('phone', $phone)
                ->when($tenantId > 0, fn ($query) => $query->where('tenant_id', $tenantId))
                ->active()
                ->first();
            if ($activeSession) {
                return redirect()->route('wifi.status', ['phone' => $phone, 'tenant_id' => $voucher->tenant_id])
                    ->with('success', 'Session already active. You are connected.');
            }

            $routerId = (int) (\App\Models\Router::query()
                ->where('tenant_id', $voucher->tenant_id)
                ->orderByDesc('status')
                ->orderBy('id')
                ->value('id') ?? 0);

            if ($routerId <= 0) {
                return redirect()->back()->withErrors(['No active router is configured for this tenant.']);
            }

            try {
                $payment = DB::transaction(function () use ($voucher, $phone, $routerId) {
                    $payment = Payment::create([
                        'tenant_id' => $voucher->tenant_id,
                        'phone' => $phone,
                        'package_id' => $voucher->package_id,
                        'package_name' => $voucher->package?->name,
                        'amount' => 0,
                        'currency' => 'KES',
                        'mpesa_checkout_request_id' => 'VCH-' . strtoupper(uniqid()),
                        'status' => Payment::STATUS_COMPLETED,
                        'initiated_at' => now(),
                        'confirmed_at' => now(),
                        'completed_at' => now(),
                        'payment_channel' => 'voucher',
                        'metadata' => [
                            'voucher_id' => $voucher->id,
                            'voucher_code' => $voucher->code_display,
                            'redeemed_via' => 'captive_portal',
                        ],
                    ]);

                    UserSession::create([
                        'tenant_id' => $voucher->tenant_id,
                        'router_id' => $routerId,
                        'package_id' => $voucher->package_id,
                        'username' => $this->generateUsername($phone),
                        'phone' => $phone,
                        'status' => 'active',
                        'started_at' => now(),
                        'expires_at' => now()->copy()->addMinutes($voucher->package->duration_in_minutes ?? 60),
                        'payment_id' => $payment->id,
                        'voucher_id' => $voucher->id,
                    ]);

                    $voucher->update([
                        'status' => Voucher::STATUS_USED,
                        'used_at' => now(),
                        'used_by_phone' => $phone,
                        'router_id' => $routerId,
                        'redeemed_via' => Voucher::REDEEMED_VIA_CAPTIVE_PORTAL,
                        'redeemed_at' => now(),
                        'redemption_count' => (int) ($voucher->redemption_count ?? 0) + 1,
                    ]);

                    return $payment;
                });

                session(['captive_phone' => $phone]);
                session(['captive_payment_id' => $payment->id]);

                return redirect()->route('wifi.status', $this->buildStatusRouteParameters(
                    phone: $phone,
                    payment: $payment,
                    tenantId: $voucher->tenant_id
                ))
                    ->with('success', 'Voucher redeemed successfully!');
            } catch (\Throwable $e) {
                Log::error('Voucher redemption failed', [
                    'voucher_id' => $voucher->id,
                    'phone' => $phone,
                    'error' => $e->getMessage(),
                ]);

                return redirect()->back()->withErrors(['Voucher redemption failed. Please try again.'])->withInput();
            }
        }

        $request->validate([
            'mpesa_code' => ['required', 'string', 'max:32'],
            'phone' => ['required', 'regex:' . self::KENYA_PHONE_REGEX],
        ]);
        
        $mpesaCode = strtoupper(trim($request->mpesa_code));
        $phone = $this->normalizePhoneForStorage((string) $request->phone);
        if ($phone === null) {
            return redirect()->back()
                ->withErrors(['Use a valid Safaricom number: 07XXXXXXXX, 01XXXXXXXX, +2547XXXXXXXX or +2541XXXXXXXX.'])
                ->withInput();
        }
        
        $payment = Payment::where(function($query) use ($mpesaCode) {
                $query->where('mpesa_transaction_id', $mpesaCode)
                    ->orWhere('mpesa_receipt_number', $mpesaCode);
            })
            ->when($tenantId > 0, fn ($query) => $query->where('tenant_id', $tenantId))
            ->where('phone', $phone)
            ->whereIn('payment_channel', self::STATUS_CHANNELS)
            ->first();

        if (!$payment) {
            return redirect()->back()
                ->withErrors(['Invalid M-Pesa code or phone number. Please check and try again.'])
                ->withInput();
        }

        $this->reconcileDarajaPaymentIfNeeded($payment, true);
        $payment = $payment->fresh();

        if (!in_array((string) $payment->status, ['completed', 'confirmed'], true)) {
            return redirect()->back()
                ->withErrors(['Payment found but not yet confirmed. Tap "Recheck Payment" on status screen in 10-20 seconds.'])
                ->withInput();
        }
        
        $activeSession = UserSession::where('phone', $phone)
            ->when($tenantId > 0, fn ($query) => $query->where('tenant_id', $tenantId))
            ->active()
            ->first();
        
        if ($activeSession) {
            session(['captive_payment_id' => $payment->id]);

            return redirect()->route('wifi.status', $this->buildStatusRouteParameters(
                phone: $phone,
                payment: $payment,
                tenantId: $payment->tenant_id
            ))
                ->with('success', 'Session already active. You are connected.');
        }
        
        try {
            $routerId = $this->resolveRouterIdForPayment($payment);

            if (!$routerId) {
                return redirect()->back()->withErrors(['No active router is configured for this tenant.']);
            }

            UserSession::create([
                'tenant_id' => $payment->tenant_id,
                'router_id' => $routerId,
                'package_id' => $payment->package_id,
                'username' => $this->generateUsername($phone),
                'phone' => $phone,
                'mac_address' => $request->mac ?? null,
                'status' => 'active',
                'started_at' => now(),
                'expires_at' => now()->copy()->addMinutes($payment->package?->duration_in_minutes ?? 60),
                'payment_id' => $payment->id,
            ]);
            
            $payment->increment('reconnect_count');
            
            Log::info('User reconnected via M-Pesa code', [
                'phone' => $phone,
                'mpesa_code' => $mpesaCode,
                'payment_id' => $payment->id
            ]);
            
            session(['captive_payment_id' => $payment->id]);

            return redirect()->route('wifi.status', $this->buildStatusRouteParameters(
                phone: $phone,
                payment: $payment,
                tenantId: $payment->tenant_id
            ))
                ->with('success', 'Reconnected successfully!');
                
        } catch (\Exception $e) {
            Log::error('Reconnection failed', [
                'phone' => $phone,
                'error' => $e->getMessage()
            ]);
            
            return redirect()->back()
                ->withErrors(['Reconnection failed. Please contact support if issue persists.'])
                ->withInput();
        }
    }
    
    /**
     * Extend session time (pay for additional minutes)
     */
    public function extend(Request $request)
    {
        $tenantId = $this->resolveTenantId($request);
        $gateway = $this->resolvePaymentGateway();

        if ($tenantId <= 0) {
            return back()->withErrors(['Tenant portal not resolved. Reopen your WiFi portal and try again.']);
        }

        $request->validate([
            'phone' => ['required', 'regex:' . self::KENYA_PHONE_REGEX],
            'package_id' => ['required', 'exists:packages,id'],
        ]);
        
        $phone = $this->normalizePhoneForStorage((string) $request->phone);
        if ($phone === null) {
            return back()
                ->withErrors(['Use a valid Safaricom number: 07XXXXXXXX, 01XXXXXXXX, +2547XXXXXXXX or +2541XXXXXXXX.'])
                ->withInput();
        }
        $package = Package::query()
            ->when($tenantId > 0, fn ($query) => $query->where('tenant_id', $tenantId))
            ->findOrFail($request->package_id);

        if (!$this->isGatewayConfigured($gateway)) {
            return back()->withErrors(['Payment gateway is not configured. Please contact support.']);
        }
        
        $activeSession = UserSession::where('phone', $phone)
            ->when($tenantId > 0, fn ($query) => $query->where('tenant_id', $tenantId))
            ->active()
            ->first();
        
        if (!$activeSession) {
            return redirect()->route('wifi.packages')
                ->withErrors(['No active session found. Please purchase a package first.']);
        }
        
        $payment = DB::transaction(function () use ($phone, $package, $activeSession, $gateway) {
            return Payment::create([
                'tenant_id' => $package->tenant_id,
                'phone' => $phone,
                'package_id' => $package->id,
                'package_name' => $package->name,
                'amount' => $package->price,
                'currency' => $package->currency ?? 'KES',
                'mpesa_checkout_request_id' => 'EXT-' . strtoupper(uniqid()),
                'status' => 'pending',
                'initiated_at' => now(),
                'payment_channel' => 'session_extension',
                'metadata' => [
                    'gateway' => $gateway,
                    'parent_payment_id' => $activeSession->payment_id,
                ],
            ]);
        });
        
        try {
            $response = $this->initiateStkPush(
                payment: $payment,
                package: $package,
                phone: $phone,
                flow: 'session_extension'
            );

            if ($response['success']) {
                Log::info('Extension STK sent', [
                    'phone' => $phone,
                    'amount' => $package->price,
                    'reference' => $payment->mpesa_checkout_request_id,
                    'gateway' => $gateway,
                ]);

                session(['captive_payment_id' => $payment->id]);

                return redirect()->route('wifi.status', $this->buildStatusRouteParameters(
                    phone: $phone,
                    payment: $payment,
                    tenantId: $package->tenant_id
                ))
                    ->with('message', (string) ($response['user_message'] ?? 'Complete STK Push to extend your session'));
            }

            if ($response['redirect_to_status'] ?? false) {
                session(['captive_payment_id' => $payment->id]);

                return redirect()->route('wifi.status', $this->buildStatusRouteParameters(
                    phone: $phone,
                    payment: $payment,
                    tenantId: $package->tenant_id
                ))
                    ->with('message', (string) ($response['user_message'] ?? 'We are verifying your payment request.'));
            }

            return back()->withErrors([(string) ($response['error'] ?? 'STK Push failed. Try again.')]);
            
        } catch (\Exception $e) {
            Log::error('Extension STK failed', [
                'phone' => $phone,
                'error' => $e->getMessage(),
                'gateway' => $gateway,
            ]);
            return back()->withErrors(['Payment service unavailable.']);
        }
    }
    
    /**
     * AJAX endpoint to check session status (for polling)
     */
    public function checkStatus(Request $request, $phone)
    {
        $phone = $this->normalizePhoneForStorage((string) $phone) ?? (string) $phone;
        $tenantId = $this->resolveTenantId($request);
        $paymentQuery = $this->buildStatusPaymentQuery($phone, $tenantId);
        $requestedPayment = $this->resolveRequestedStatusPayment($request, $phone, $tenantId);

        if ($tenantId === 0 && !$requestedPayment) {
            $tenantMatches = (clone $paymentQuery)->select('tenant_id')->distinct()->count('tenant_id');
            if ($tenantMatches > 1) {
                return response()->json([
                    'status' => 'ambiguous_tenant',
                    'session_active' => false,
                    'expires_at' => null,
                ], 409);
            }
        }
        $payment = $requestedPayment ?? $this->resolveStatusPayment($request, $phone, $tenantId);
        
        if (!$payment) {
            return response()->json(['status' => 'not_found']);
        }

        session([
            'captive_phone' => $phone,
            'captive_payment_id' => $payment->id,
        ]);

        $this->reconcileDarajaPaymentIfNeeded($payment, $request->boolean('recheck'));
        $payment = $payment->fresh();

        if (in_array($payment->status, ['completed', 'confirmed'], true)) {
            try {
                $this->activatePaidAccess($payment);
            } catch (\Throwable $e) {
                Log::error('Automatic activation failed during status poll', [
                    'payment_id' => $payment->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
        
        $session = UserSession::where('payment_id', $payment->id)
            ->active()
            ->first();

        $status = $this->deriveStatusView($payment, $session);

        return response()->json([
            'status' => $status,
            'payment_id' => $payment->id,
            'session_active' => $session ? true : false,
            'expires_at' => $session ? $session->expires_at->toIso8601String() : null,
            'package' => $payment->package ? [
                'name' => $payment->package->name,
                'duration_minutes' => $payment->package->duration_in_minutes,
            ] : null
        ]);
    }
    
    /**
     * Paystack is disabled for captive portal production flow.
     */
    public function paystackCallback(Request $request)
    {
        Log::warning('Paystack callback ignored: captive portal is Daraja-only', [
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return response()->json([
            'status' => 'disabled',
            'message' => 'Paystack is disabled. Use M-Pesa Daraja STK Push.',
        ], 410);
    }

    private function initiateStkPush(Payment $payment, Package $package, string $phone, string $flow): array
    {
        return $this->initiateDarajaStkPush($payment, $package, $phone, $flow);
    }

    private function initiateDarajaStkPush(Payment $payment, Package $package, string $phone, string $flow): array
    {
        $daraja = app(DarajaService::class);
        $response = $daraja->stkPush(
            phone: $this->normalizePhoneForStk($phone),
            amount: (float) $package->price,
            accountReference: (string) $payment->mpesa_checkout_request_id,
            description: ($flow === 'session_extension' ? 'CloudBridge Session Extension - ' : 'CloudBridge WiFi - ') . $package->name,
            callbackUrl: $this->resolveDarajaCallbackUrl()
        );

        $newCheckoutId = $this->extractDarajaCheckoutRequestId($response);
        $newMerchantId = $this->extractDarajaMerchantRequestId($response);
        $metadata = is_array($payment->metadata) ? $payment->metadata : [];

        $updates = [
            'metadata' => array_merge($metadata, [
                'gateway' => 'daraja',
                'daraja_response_code' => $response['response_code'] ?? null,
                'daraja_response_description' => $response['response_description'] ?? null,
                'daraja_merchant_request_id' => $newMerchantId,
                'daraja_last_request_at' => now()->toIso8601String(),
                'daraja_last_status' => ($response['success'] ?? false) ? 'pending_customer_confirmation' : ($metadata['daraja_last_status'] ?? null),
            ]),
        ];

        if ($newCheckoutId !== null && $newCheckoutId !== (string) $payment->mpesa_checkout_request_id) {
            $checkoutIdTaken = Payment::withTrashed()
                ->where('mpesa_checkout_request_id', $newCheckoutId)
                ->where('id', '!=', $payment->id)
                ->exists();

            if ($checkoutIdTaken) {
                Log::critical('Daraja checkout id conflict while updating initiated payment', [
                    'payment_id' => $payment->id,
                    'checkout_request_id' => $newCheckoutId,
                ]);
            } else {
                $updates['mpesa_checkout_request_id'] = $newCheckoutId;
            }
        }

        $payment->update($updates);

        if ($response['success']) {
            return [
                'success' => true,
                'redirect_to_status' => false,
                'user_message' => (string) ($response['customer_message'] ?: 'STK Push sent. Complete payment on your phone.'),
                'error' => null,
            ];
        }

        $error = (string) ($response['error'] ?? 'STK Push failed');

        if ($this->shouldKeepDarajaPaymentPending($response)) {
            $this->markPaymentPendingVerificationFromDaraja($payment, (array) ($response['raw'] ?? []), $error);

            Log::warning('Daraja STK push needs verification before final status', [
                'payment_id' => $payment->id,
                'error' => $error,
                'flow' => $flow,
            ]);

            return [
                'success' => false,
                'redirect_to_status' => true,
                'user_message' => 'We are verifying this payment request with M-Pesa. If the prompt appears or money is deducted, do not pay again.',
                'error' => null,
            ];
        }

        $this->markPaymentFailedFromDaraja($payment, (array) ($response['raw'] ?? []), $error);

        Log::warning('Daraja STK push failed', [
            'payment_id' => $payment->id,
            'error' => $error,
            'flow' => $flow,
        ]);

        return [
            'success' => false,
            'redirect_to_status' => false,
            'user_message' => null,
            'error' => 'STK Push failed. Try again.',
        ];
    }

    private function shouldKeepDarajaPaymentPending(array $response): bool
    {
        if (($response['success'] ?? false) === true) {
            return false;
        }

        if ((string) ($response['stage'] ?? '') !== 'stk_push') {
            return false;
        }

        $responseCode = trim((string) ($response['response_code'] ?? ''));
        $hasRawPayload = !empty((array) ($response['raw'] ?? []));
        $hasCheckoutRequestId = $this->extractDarajaCheckoutRequestId($response) !== null;

        return $hasCheckoutRequestId || !$hasRawPayload || $responseCode === '';
    }

    private function extractDarajaCheckoutRequestId(array $response): ?string
    {
        $candidates = [
            $response['checkout_request_id'] ?? null,
            data_get($response, 'raw.CheckoutRequestID'),
            data_get($response, 'raw.checkout_request_id'),
        ];

        foreach ($candidates as $candidate) {
            $value = trim((string) $candidate);
            if ($value !== '') {
                return $value;
            }
        }

        return null;
    }

    private function extractDarajaMerchantRequestId(array $response): ?string
    {
        $candidates = [
            $response['merchant_request_id'] ?? null,
            data_get($response, 'raw.MerchantRequestID'),
            data_get($response, 'raw.merchant_request_id'),
        ];

        foreach ($candidates as $candidate) {
            $value = trim((string) $candidate);
            if ($value !== '') {
                return $value;
            }
        }

        return null;
    }

    private function markPaymentPendingVerificationFromDaraja(Payment $payment, array $payload, string $reason): void
    {
        if ($payment->status === 'completed') {
            return;
        }

        $payment->update([
            'status' => 'pending',
            'callback_data' => $payload !== [] ? $payload : $payment->callback_data,
            'failed_at' => null,
            'reconciliation_notes' => $reason,
            'metadata' => array_merge($payment->metadata ?? [], [
                'gateway' => 'daraja',
                'daraja_last_status' => 'pending_verification',
                'daraja_failure_reason' => $reason,
                'daraja_verification_required' => true,
                'daraja_last_uncertain_at' => now()->toIso8601String(),
            ]),
        ]);
    }

    private function markPaymentFailedFromDaraja(Payment $payment, array $payload, string $reason): void
    {
        if ($payment->status === 'completed') {
            return;
        }

        $payment->update([
            'status' => 'failed',
            'callback_data' => $payload,
            'failed_at' => now(),
            'reconciliation_notes' => $reason,
            'metadata' => array_merge($payment->metadata ?? [], [
                'gateway' => 'daraja',
                'daraja_last_status' => 'rejected_by_gateway',
                'daraja_failure_reason' => $reason,
                'daraja_verification_required' => false,
            ]),
        ]);
    }

    private function reconcileDarajaPaymentIfNeeded(Payment $payment, bool $force = false): void
    {
        $status = (string) $payment->status;
        if (!in_array($status, ['initiated', 'pending', 'failed'], true)) {
            return;
        }

        $checkoutRequestId = trim((string) $payment->mpesa_checkout_request_id);
        if ($checkoutRequestId === '') {
            return;
        }

        if (preg_match('/^(CP|EXT|VCH)-/i', $checkoutRequestId) === 1) {
            return;
        }

        $metadata = is_array($payment->metadata) ? $payment->metadata : [];
        if (!$force) {
            $lastQueryAt = isset($metadata['daraja_last_query_at']) ? trim((string) $metadata['daraja_last_query_at']) : '';
            if ($lastQueryAt !== '') {
                try {
                    if (\Illuminate\Support\Carbon::parse($lastQueryAt)->gt(now()->subSeconds(15))) {
                        return;
                    }
                } catch (\Throwable) {
                    // Ignore parse errors and continue.
                }
            }
        }

        if ($status === 'failed' && !$force) {
            $recentFailure = $payment->failed_at?->gt(now()->subMinutes(self::VERIFICATION_WINDOW_MINUTES)) ?? false;
            $callbackResultCode = data_get($payment->callback_data, 'ResultCode');
            $lastStatus = trim((string) ($metadata['daraja_last_status'] ?? ''));

            $hasFinalFailureFromCallback = is_numeric($callbackResultCode) && (int) $callbackResultCode !== 0;
            if (
                !$recentFailure
                || $hasFinalFailureFromCallback
                || in_array($lastStatus, ['failed_via_query', 'rejected_by_gateway'], true)
            ) {
                return;
            }
        }

        try {
            $daraja = app(DarajaService::class);
            $result = $daraja->queryStkStatus($checkoutRequestId);
        } catch (\Throwable $e) {
            Log::warning('Daraja reconciliation failed before query', [
                'payment_id' => $payment->id,
                'checkout_request_id' => $checkoutRequestId,
                'status' => $status,
                'error' => $e->getMessage(),
            ]);
            return;
        }

        $metadata = array_merge($metadata, [
            'daraja_last_query_at' => now()->toIso8601String(),
            'daraja_query_response_code' => $result['response_code'] ?? null,
            'daraja_query_result_code' => $result['result_code'] ?? null,
            'daraja_query_result_desc' => $result['result_desc'] ?? null,
        ]);

        if (!($result['success'] ?? false)) {
            $payment->update(['metadata' => $metadata]);
            Log::warning('Daraja STK query did not succeed for payment', [
                'payment_id' => $payment->id,
                'checkout_request_id' => $checkoutRequestId,
                'status' => $status,
                'error' => $result['error'] ?? null,
            ]);
            return;
        }

        if (($result['is_success'] ?? false) && ($result['final'] ?? false)) {
            $payment->update([
                'status' => 'confirmed',
                'mpesa_receipt_number' => $result['receipt_number'] ?? $payment->mpesa_receipt_number,
                'mpesa_phone' => $result['phone_number'] ?? $payment->mpesa_phone,
                'amount' => $result['amount'] ?? $payment->amount,
                'confirmed_at' => $payment->confirmed_at ?? now(),
                'failed_at' => null,
                'callback_data' => (array) ($result['raw'] ?? []),
                'metadata' => array_merge($metadata, [
                    'daraja_last_status' => 'confirmed_via_query',
                    'daraja_verification_required' => false,
                ]),
            ]);

            Log::info('Payment reconciled via Daraja query', [
                'payment_id' => $payment->id,
                'checkout_request_id' => $checkoutRequestId,
                'receipt' => $result['receipt_number'] ?? null,
                'status_before' => $status,
            ]);

            return;
        }

        if (($result['is_failed'] ?? false) && ($result['final'] ?? false)) {
            $payment->update([
                'status' => 'failed',
                'failed_at' => $payment->failed_at ?? now(),
                'callback_data' => (array) ($result['raw'] ?? []),
                'metadata' => array_merge($metadata, [
                    'daraja_last_status' => 'failed_via_query',
                    'daraja_verification_required' => false,
                ]),
            ]);

            Log::warning('Payment marked failed via Daraja query', [
                'payment_id' => $payment->id,
                'checkout_request_id' => $checkoutRequestId,
                'result_code' => $result['result_code'] ?? null,
                'status_before' => $status,
            ]);

            return;
        }

        $payment->update(['metadata' => $metadata]);
    }

    private function activatePaidAccess(Payment $payment): ?UserSession
    {
        $activeSession = UserSession::where('payment_id', $payment->id)
            ->active()
            ->first();

        if ($activeSession) {
            return $activeSession;
        }

        $routerId = $this->resolveRouterIdForPayment($payment);
        if (!$routerId) {
            Log::warning('Access activation skipped: no router found for tenant', [
                'payment_id' => $payment->id,
                'tenant_id' => $payment->tenant_id,
            ]);
            return null;
        }

        $durationMinutes = max(1, (int) ($payment->package?->duration_in_minutes ?? 60));
        $username = $this->resolveRadiusUsernameFromPhone($payment->phone, $payment->id);
        $expiresAt = now()->copy()->addMinutes($durationMinutes);

        $session = UserSession::firstOrCreate(
            ['payment_id' => $payment->id],
            [
                'tenant_id' => $payment->tenant_id,
                'router_id' => $routerId,
                'package_id' => $payment->package_id,
                'username' => $username,
                'phone' => $payment->phone,
                'status' => 'pending',
                'started_at' => now(),
                'expires_at' => $expiresAt,
            ]
        );

        if ((bool) config('radius.enabled', false) && $payment->package) {
            try {
                $radiusProvisioning = app(FreeRadiusProvisioningService::class);
                $radiusProvisioning->provisionUser(
                    username: $username,
                    password: $username,
                    package: $payment->package,
                    expiresAt: $expiresAt
                );

                $session->update([
                    'status' => 'active',
                    'started_at' => $session->started_at ?? now(),
                    'expires_at' => $expiresAt,
                    'last_synced_at' => now(),
                    'metadata' => array_merge($session->metadata ?? [], [
                        'radius' => [
                            'provisioned' => true,
                            'username' => $username,
                            'provisioned_at' => now()->toIso8601String(),
                            'expires_at' => $expiresAt->toIso8601String(),
                            'auth_hint' => 'password_equals_username',
                        ],
                    ]),
                ]);

                $payment->update([
                    'metadata' => array_merge($payment->metadata ?? [], [
                        'radius' => [
                            'provisioned' => true,
                            'username' => $username,
                            'provisioned_at' => now()->toIso8601String(),
                            'expires_at' => $expiresAt->toIso8601String(),
                            'auth_hint' => 'password_equals_username',
                        ],
                    ]),
                ]);

                Log::info('Paid access provisioned via FreeRADIUS', [
                    'payment_id' => $payment->id,
                    'session_id' => $session->id,
                    'username' => $username,
                ]);

                return $session->fresh();
            } catch (\Throwable $e) {
                Log::error('FreeRADIUS provisioning failed after payment', [
                    'payment_id' => $payment->id,
                    'session_id' => $session->id,
                    'username' => $username,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        if ($payment->package) {
            $sessionManager = app(SessionManager::class);
            $activation = $sessionManager->activateSession($session, $payment->package);

            if (!($activation['success'] ?? false)) {
                Log::warning('MikroTik activation not completed', [
                    'payment_id' => $payment->id,
                    'session_id' => $session->id,
                    'reason' => $activation['error'] ?? 'unknown',
                    'queued' => (bool) ($activation['queued'] ?? false),
                ]);
                return $session->fresh();
            }

            Log::info('Paid access activated on MikroTik', [
                'payment_id' => $payment->id,
                'session_id' => $session->id,
                'username' => $session->username,
            ]);
        }

        return $session->fresh();
    }

    private function resolveRouterIdForPayment(Payment $payment): ?int
    {
        return (int) (\App\Models\Router::query()
            ->where('tenant_id', $payment->tenant_id)
            ->orderByDesc('status')
            ->orderBy('id')
            ->value('id') ?? 0) ?: null;
    }

    private function generateUsername(string $phone): string
    {
        return 'u' . preg_replace('/\D+/', '', $phone) . substr((string) microtime(true), -4);
    }

    private function normalizePhoneForStk(string $phone): string
    {
        $storedPhone = $this->normalizePhoneForStorage($phone);
        if ($storedPhone !== null) {
            return '254' . substr($storedPhone, 1);
        }

        $digits = preg_replace('/\D+/', '', $phone);
        if (is_string($digits) && str_starts_with($digits, '254')) {
            return $digits;
        }

        return is_string($digits) ? $digits : '';
    }

    private function normalizePhoneForStorage(string $phone): ?string
    {
        $digits = preg_replace('/\D+/', '', $phone);
        if (!is_string($digits) || $digits === '') {
            return null;
        }

        if (str_starts_with($digits, '254') && strlen($digits) === 12) {
            $mobilePrefix = substr($digits, 3, 1);
            if (in_array($mobilePrefix, ['1', '7'], true)) {
                return '0' . substr($digits, 3);
            }
        }

        if (str_starts_with($digits, '0') && strlen($digits) === 10) {
            $mobilePrefix = substr($digits, 1, 1);
            if (in_array($mobilePrefix, ['1', '7'], true)) {
                return $digits;
            }
        }

        if (strlen($digits) === 9) {
            $mobilePrefix = substr($digits, 0, 1);
            if (in_array($mobilePrefix, ['1', '7'], true)) {
                return '0' . $digits;
            }
        }

        return null;
    }

    private function resolveRadiusUsernameFromPhone(string $phone, ?int $paymentId = null): string
    {
        $digits = preg_replace('/\D+/', '', $phone);
        if ($digits !== '') {
            return 'cb' . $digits;
        }

        return 'cbu' . (int) $paymentId;
    }

    private function resolveDarajaCallbackUrl(): string
    {
        $configured = trim((string) config('services.mpesa.callback_url', ''));
        if ($configured !== '') {
            return $configured;
        }

        return url('/api/mpesa/callback');
    }

    private function resolvePaymentGateway(): string
    {
        return 'daraja';
    }

    private function isGatewayConfigured(string $gateway): bool
    {
        return app(DarajaService::class)->isConfigured();
    }
}
