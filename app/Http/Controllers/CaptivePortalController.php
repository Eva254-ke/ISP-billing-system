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
use Illuminate\Support\Facades\Cache;

class CaptivePortalController extends Controller
{
    private const STATUS_CHANNELS = ['captive_portal', 'session_extension', 'voucher'];
    private const STATUS_PRIORITIES = ['initiated', 'pending', 'confirmed', 'completed', 'activated'];
    private const KENYA_PHONE_REGEX = '/^(?:0[17]\d{8}|(?:\+?254)[17]\d{8})$/';
    private const VERIFICATION_WINDOW_MINUTES = 20;
    private const DUPLICATE_PAYMENT_WINDOW_MINUTES = 20;
    private const PAYMENT_LOCK_SECONDS = 20;

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
        $clientContext = $this->resolveClientContext($request);
        $clientMac = $clientContext['mac'];
        $clientIp = $clientContext['ip'];
        $mode = strtolower(trim((string) $request->query('mode', '')));
        $showReconnectScreen = $mode === 'reconnect';

        if (!$tenant) {
            return response()->view($showReconnectScreen ? 'captive.reconnect' : 'captive.packages', [
                'packages' => collect(),
                'activeSession' => null,
                'phone' => $phone,
                'tenant' => null,
                'clientMac' => $clientMac,
                'clientIp' => $clientIp,
                'tenantResolutionError' => 'Tenant portal not resolved. Use your tenant domain (e.g. https://your-subdomain.cloudbridge.network/wifi) or include tenant_id in the URL.',
            ], 400);
        }

        session(['captive_tenant_id' => $tenant->id]);

        if ($showReconnectScreen) {
            return view('captive.reconnect', compact('phone', 'tenant', 'clientMac', 'clientIp'));
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

        return view('captive.packages', compact('packages', 'activeSession', 'phone', 'tenant', 'clientMac', 'clientIp'));
    }
    
    /**
     * Process payment request
     */
    public function pay(Request $request)
    {
        $tenantId = $this->resolveTenantId($request);
        $gateway = $this->resolvePaymentGateway();
        $payment = null;

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
        $clientContext = $this->resolveClientContext($request);
        $clientContextMeta = $this->buildClientContextMeta($clientContext, $request);

        $package = Package::query()
            ->when($tenantId > 0, fn ($query) => $query->where('tenant_id', $tenantId))
            ->where('is_active', true)
            ->findOrFail($request->package_id);

        if (!$this->isGatewayConfigured($gateway)) {
            return back()->withErrors(['Payment gateway is not configured. Please contact support.']);
        }

        $lock = null;
        try {
            $lock = Cache::lock($this->buildPaymentInitiationLockKey($tenantId, $phone, (int) $package->id), self::PAYMENT_LOCK_SECONDS);
        } catch (\Throwable $lockError) {
            Log::warning('Payment initiation lock unavailable; continuing without lock', [
                'tenant_id' => $tenantId,
                'phone' => $phone,
                'package_id' => $package->id,
                'error' => $lockError->getMessage(),
            ]);
        }

        if ($lock && !$lock->get()) {
            $existing = $this->findReusableCaptivePaymentAttempt($tenantId, $phone, (int) $package->id);
            if ($existing) {
                session([
                    'captive_phone' => $phone,
                    'captive_tenant_id' => $package->tenant_id,
                    'captive_payment_id' => $existing->id,
                ]);

                return redirect()->route('wifi.status', $this->buildStatusRouteParameters(
                    phone: $phone,
                    payment: $existing,
                    tenantId: $package->tenant_id
                ))->with('message', 'A payment request is already being processed. Please wait for confirmation.');
            }

            return back()->withErrors(['Payment request is being processed. Wait for the STK prompt, then check status.']);
        }

        try {
            $existing = $this->findReusableCaptivePaymentAttempt($tenantId, $phone, (int) $package->id);
            if ($existing && $this->shouldReuseCaptivePaymentAttempt($existing)) {
                if ($clientContextMeta !== []) {
                    $existingMetadata = is_array($existing->metadata) ? $existing->metadata : [];
                    $existingClientMeta = is_array($existingMetadata['client_context'] ?? null) ? $existingMetadata['client_context'] : [];
                    $existingMetadata['client_context'] = array_merge($existingClientMeta, $clientContextMeta);
                    $existing->update(['metadata' => $existingMetadata]);
                    $existing = $existing->fresh();
                }

                $this->reconcileDarajaPaymentIfNeeded($existing, false);
                $existing = $existing->fresh();

                if ($existing && $this->shouldReuseCaptivePaymentAttempt($existing)) {
                    if (in_array((string) $existing->status, ['completed', 'confirmed'], true)) {
                        try {
                            $this->activatePaidAccess($existing);
                        } catch (\Throwable $activationError) {
                            Log::warning('Activation retry failed while reusing recent captive payment', [
                                'payment_id' => $existing->id,
                                'error' => $activationError->getMessage(),
                            ]);
                        }
                    }

                    session([
                        'captive_phone' => $phone,
                        'captive_tenant_id' => $package->tenant_id,
                        'captive_payment_id' => $existing->id,
                    ]);

                    $message = in_array((string) $existing->status, ['completed', 'confirmed'], true)
                        ? 'Payment already confirmed. Connecting you now.'
                        : 'Payment request already in progress. Complete the M-Pesa prompt and wait for confirmation.';

                    return redirect()->route('wifi.status', $this->buildStatusRouteParameters(
                            phone: $phone,
                            payment: $existing,
                            tenantId: $package->tenant_id
                        ))
                        ->with('message', $message);
                }
            }

            $payment = DB::transaction(function () use ($phone, $package, $gateway, $clientContextMeta) {
                return Payment::create([
                    'tenant_id' => $package->tenant_id,
                    'phone' => $phone,
                    'package_id' => $package->id,
                    'package_name' => $package->name,
                    'amount' => $package->price,
                    'currency' => $package->currency ?? 'KES',
                    'mpesa_checkout_request_id' => 'CP-' . strtoupper(uniqid()),
                    'status' => 'pending',
                    'type' => Payment::TYPE_CAPTIVE_PORTAL,
                    'initiated_at' => now(),
                    'payment_channel' => Payment::CHANNEL_CAPTIVE_PORTAL,
                    'metadata' => [
                        'gateway' => $gateway,
                        'created_via' => 'captive_portal',
                        'client_context' => $clientContextMeta,
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
            
        } catch (\Throwable $e) {
            Log::error('Captive portal STK initiation exception', [
                'error' => $e->getMessage(),
                'phone' => $phone,
                'gateway' => $gateway,
                'payment_id' => $payment?->id,
            ]);

            if ($payment instanceof Payment) {
                session([
                    'captive_phone' => $phone,
                    'captive_tenant_id' => $payment->tenant_id,
                    'captive_payment_id' => $payment->id,
                ]);

                return redirect()->route('wifi.status', $this->buildStatusRouteParameters(
                    phone: $phone,
                    payment: $payment,
                    tenantId: (int) $payment->tenant_id
                ))->with('message', 'We are verifying your payment request. If the M-Pesa prompt appears or money is deducted, do not pay again.');
            }

            return back()->withErrors(['Payment service unavailable. Please try again.']);
        } finally {
            try {
                $lock?->release();
            } catch (\Throwable) {
                // Ignore lock release failures; lock auto-expires.
            }
        }
    }

    private function resolveTenant(Request $request): ?Tenant
    {
        // 1) Explicit tenant_id from query/form body has highest priority.
        $explicitTenantId = $this->extractExplicitTenantId($request);
        if ($explicitTenantId > 0) {
            return Tenant::active()->find($explicitTenantId);
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

        if ($this->extractExplicitTenantId($request) > 0) {
            return 0;
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
        $hasFinalFailureFromCallback = $this->hasFinalFailureFromCallback($payment, $metadata);
        $recentFailure = $payment->failed_at?->gt(now()->subMinutes(self::VERIFICATION_WINDOW_MINUTES)) ?? false;
        $likelyStillProcessing = $this->isPaymentFailureLikelyStillProcessing($payment, $metadata);

        if ($likelyStillProcessing && $recentFailure) {
            return true;
        }

        if (
            $hasFinalFailureFromCallback
            || $lastStatus === 'underpaid_amount'
            || ($lastStatus === 'failed_via_query' && !$likelyStillProcessing)
        ) {
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

        if ($statusView === 'pending' && in_array(trim((string) ($metadata['daraja_last_status'] ?? '')), ['pending_verification', 'query_pending'], true)) {
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

        try {
            $payment = $this->captureClientContextForPayment($request, $payment);
        } catch (\Throwable $e) {
            Log::warning('Captive client context capture skipped after transient payment update failure', [
                'payment_id' => $payment->id,
                'phone' => $phone,
                'error' => $e->getMessage(),
            ]);
        }

        try {
            $this->reconcileDarajaPaymentIfNeeded($payment, $request->boolean('recheck'));
        } catch (\Throwable $e) {
            Log::warning('Captive status reconciliation skipped after transient payment update failure', [
                'payment_id' => $payment->id,
                'phone' => $phone,
                'error' => $e->getMessage(),
            ]);
        }

        $payment = $payment->fresh() ?? $payment;

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
        $clientContext = $this->resolveClientContext($request);
        $clientContextMeta = $this->buildClientContextMeta($clientContext, $request);

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
                $payment = DB::transaction(function () use ($voucher, $phone, $routerId, $clientContextMeta) {
                    $payment = Payment::create([
                        'tenant_id' => $voucher->tenant_id,
                        'phone' => $phone,
                        'package_id' => $voucher->package_id,
                        'package_name' => $voucher->package?->name,
                        'amount' => 0,
                        'currency' => 'KES',
                        'mpesa_checkout_request_id' => 'VCH-' . strtoupper(uniqid()),
                        'status' => Payment::STATUS_COMPLETED,
                        'type' => Payment::TYPE_VOUCHER,
                        'initiated_at' => now(),
                        'confirmed_at' => now(),
                        'completed_at' => now(),
                        'payment_channel' => Payment::CHANNEL_VOUCHER,
                        'metadata' => [
                            'voucher_id' => $voucher->id,
                            'voucher_code' => $voucher->code_display,
                            'redeemed_via' => 'captive_portal',
                            'client_context' => $clientContextMeta,
                        ],
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

                $activationMessage = 'Voucher redeemed successfully! Activation is in progress.';
                try {
                    $activatedSession = $this->activatePaidAccess($payment->fresh());
                    if ($activatedSession && $activatedSession->status === 'active') {
                        $activationMessage = 'Voucher redeemed successfully! You are now connected.';
                    }
                } catch (\Throwable $activationError) {
                    Log::warning('Voucher redemption succeeded but router activation is still pending', [
                        'payment_id' => $payment->id,
                        'error' => $activationError->getMessage(),
                    ]);
                }

                return redirect()->route('wifi.status', $this->buildStatusRouteParameters(
                    phone: $phone,
                    payment: $payment,
                    tenantId: $voucher->tenant_id
                ))
                    ->with('success', $activationMessage);
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

            if ($clientContextMeta !== []) {
                $paymentMetadata = is_array($payment->metadata) ? $payment->metadata : [];
                $existingClientMeta = is_array($paymentMetadata['client_context'] ?? null) ? $paymentMetadata['client_context'] : [];
                $paymentMetadata['client_context'] = array_merge($existingClientMeta, $clientContextMeta);
                $payment->update(['metadata' => $paymentMetadata]);
                $payment = $payment->fresh();
            }

            $activatedSession = $this->activatePaidAccess($payment);
            
            $payment->increment('reconnect_count');
            
            Log::info('User reconnected via M-Pesa code', [
                'phone' => $phone,
                'mpesa_code' => $mpesaCode,
                'payment_id' => $payment->id
            ]);
            
            session(['captive_payment_id' => $payment->id]);

            $successMessage = ($activatedSession && $activatedSession->status === 'active')
                ? 'Reconnected successfully! You are now connected.'
                : 'Payment verified. Activation is in progress.';

            return redirect()->route('wifi.status', $this->buildStatusRouteParameters(
                phone: $phone,
                payment: $payment,
                tenantId: $payment->tenant_id
            ))
                ->with('success', $successMessage);
                
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
                'type' => Payment::TYPE_SESSION_EXTENSION,
                'initiated_at' => now(),
                'payment_channel' => Payment::CHANNEL_SESSION_EXTENSION,
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

        $payment = $this->captureClientContextForPayment($request, $payment);
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
            callbackUrl: $this->resolveDarajaCallbackUrl((int) $payment->tenant_id)
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
            $lastStatus = trim((string) ($metadata['daraja_last_status'] ?? ''));
            $likelyStillProcessing = $this->isPaymentFailureLikelyStillProcessing($payment, $metadata);
            $hasFinalFailureFromCallback = $this->hasFinalFailureFromCallback($payment, $metadata);

            if (
                !$recentFailure
                || $hasFinalFailureFromCallback
                || $lastStatus === 'underpaid_amount'
                || ($lastStatus === 'failed_via_query' && !$likelyStillProcessing)
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

        if ($this->shouldTreatDarajaQueryResultAsPending($result)) {
            $payment->update([
                'status' => 'pending',
                'failed_at' => null,
                'callback_data' => (array) ($result['raw'] ?? []),
                'metadata' => array_merge($metadata, [
                    'daraja_last_status' => 'query_pending',
                    'daraja_verification_required' => true,
                    'daraja_query_pending_at' => now()->toIso8601String(),
                ]),
            ]);

            Log::info('Daraja STK query indicates payment is still processing', [
                'payment_id' => $payment->id,
                'checkout_request_id' => $checkoutRequestId,
                'result_code' => $result['result_code'] ?? null,
                'result_desc' => $result['result_desc'] ?? null,
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

    private function shouldTreatDarajaQueryResultAsPending(array $result): bool
    {
        if (($result['is_pending'] ?? false) === true) {
            return true;
        }

        return $this->isDarajaQueryResultLikelyStillProcessing(
            $this->normalizeDarajaResultCode($result['result_code'] ?? null),
            (string) ($result['result_desc'] ?? '')
        );
    }

    private function isPaymentFailureLikelyStillProcessing(Payment $payment, array $metadata = []): bool
    {
        $metadata = $metadata !== [] ? $metadata : (is_array($payment->metadata) ? $payment->metadata : []);
        $queryResultCode = $this->normalizeDarajaResultCode(
            $metadata['daraja_query_result_code'] ?? data_get($payment->callback_data, 'ResultCode')
        );
        $queryResultDesc = (string) (
            $metadata['daraja_query_result_desc']
            ?? data_get($payment->callback_data, 'ResultDesc')
            ?? ''
        );

        if ($this->isDarajaQueryResultLikelyStillProcessing($queryResultCode, $queryResultDesc)) {
            return true;
        }

        $failureText = strtolower(trim((string) (
            $payment->reconciliation_notes
            ?? ($metadata['daraja_failure_reason'] ?? null)
            ?? data_get($payment->callback_data, 'ResultDesc')
            ?? ''
        )));

        if ($failureText === '') {
            return false;
        }

        return str_contains($failureText, 'still under process')
            || str_contains($failureText, 'still under processing')
            || str_contains($failureText, 'under process')
            || str_contains($failureText, 'under processing')
            || str_contains($failureText, 'being processed')
            || str_contains($failureText, 'in progress')
            || str_contains($failureText, 'processing');
    }

    private function hasFinalFailureFromCallback(Payment $payment, array $metadata = []): bool
    {
        $metadata = $metadata !== [] ? $metadata : (is_array($payment->metadata) ? $payment->metadata : []);

        if (trim((string) ($metadata['daraja_last_status'] ?? '')) !== 'failed_callback') {
            return false;
        }

        $callbackResultCode = $this->normalizeDarajaResultCode(data_get($payment->callback_data, 'ResultCode'));

        return $callbackResultCode !== null && $callbackResultCode !== 0;
    }

    private function normalizeDarajaResultCode(mixed $value): ?int
    {
        return is_numeric($value) ? (int) $value : null;
    }

    private function isDarajaQueryResultLikelyStillProcessing(?int $resultCode, string $resultDesc): bool
    {
        $resultDesc = strtolower(trim($resultDesc));

        if (
            str_contains($resultDesc, 'still under process')
            || str_contains($resultDesc, 'still under processing')
            || str_contains($resultDesc, 'under process')
            || str_contains($resultDesc, 'under processing')
            || str_contains($resultDesc, 'being processed')
            || str_contains($resultDesc, 'in progress')
            || str_contains($resultDesc, 'processing')
            || str_contains($resultDesc, 'gateway timeout')
            || str_contains($resultDesc, 'timed out')
            || str_contains($resultDesc, 'timeout')
        ) {
            return true;
        }

        return in_array($resultCode, [1, 2002], true);
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
        $paymentMetadata = is_array($payment->metadata) ? $payment->metadata : [];
        $paymentClientContext = is_array($paymentMetadata['client_context'] ?? null) ? $paymentMetadata['client_context'] : [];
        $clientMac = $this->normalizeMacAddress((string) ($paymentClientContext['mac'] ?? ''));
        $clientIp = $this->normalizeClientIpAddress((string) ($paymentClientContext['ip'] ?? ''));
        $radiusEnabled = (bool) config('radius.enabled', false);

        if ($radiusEnabled && $clientMac === null && $clientIp === null) {
            Log::warning('Paid access activation missing captive client MAC/IP context in RADIUS mode', [
                'payment_id' => $payment->id,
                'tenant_id' => $payment->tenant_id,
                'router_id' => $routerId,
                'phone' => $payment->phone,
                'status' => $payment->status,
            ]);
        }

        $session = UserSession::firstOrCreate(
            ['payment_id' => $payment->id],
            [
                'tenant_id' => $payment->tenant_id,
                'router_id' => $routerId,
                'package_id' => $payment->package_id,
                'username' => $username,
                'phone' => $payment->phone,
                'mac_address' => $clientMac,
                'ip_address' => $clientIp,
                'status' => 'idle',
                'started_at' => now(),
                'expires_at' => $expiresAt,
            ]
        );

        $sessionUpdates = [];
        if ((int) $session->router_id !== (int) $routerId) {
            $sessionUpdates['router_id'] = $routerId;
        }
        if ((int) $session->package_id !== (int) ($payment->package_id ?? 0) && (int) ($payment->package_id ?? 0) > 0) {
            $sessionUpdates['package_id'] = (int) $payment->package_id;
        }
        if ($clientMac !== null && $session->mac_address !== $clientMac) {
            $sessionUpdates['mac_address'] = $clientMac;
        }
        if ($clientIp !== null && $session->ip_address !== $clientIp) {
            $sessionUpdates['ip_address'] = $clientIp;
        }
        if ($session->status !== 'active') {
            // Keep session credentials aligned with RADIUS provisioning username.
            if ((string) ($session->username ?? '') !== $username) {
                $sessionUpdates['username'] = $username;
            }
            if (!empty($payment->phone) && (string) ($session->phone ?? '') !== (string) $payment->phone) {
                $sessionUpdates['phone'] = $payment->phone;
            }
            $sessionUpdates['status'] = 'pending';
            $sessionUpdates['expires_at'] = $expiresAt;
        }
        if ($sessionUpdates !== []) {
            $session->update($sessionUpdates);
            $session->refresh();
        }

        if (!$payment->package) {
            Log::warning('Access activation skipped: package missing on payment', [
                'payment_id' => $payment->id,
                'session_id' => $session->id,
            ]);
            return $session->fresh();
        }

        if ($radiusEnabled && $payment->package) {
            try {
                $radiusProvisioning = app(FreeRadiusProvisioningService::class);
                $radiusProvisioning->provisionUser(
                    username: $username,
                    password: $username,
                    package: $payment->package,
                    expiresAt: $expiresAt
                );

                $radiusMetadata = [
                    'provisioned' => true,
                    'username' => $username,
                    'provisioned_at' => now()->toIso8601String(),
                    'expires_at' => $expiresAt->toIso8601String(),
                    'auth_hint' => 'password_equals_username',
                ];

                $session->update([
                    'started_at' => $session->started_at ?? now(),
                    'expires_at' => $expiresAt,
                    'metadata' => array_merge($session->metadata ?? [], [
                        'radius' => $radiusMetadata,
                    ]),
                ]);

                $payment->update([
                    'metadata' => array_merge($payment->metadata ?? [], [
                        'radius' => $radiusMetadata,
                    ]),
                ]);

                Log::info('Paid access provisioned via FreeRADIUS', [
                    'payment_id' => $payment->id,
                    'session_id' => $session->id,
                    'username' => $username,
                ]);
            } catch (\Throwable $e) {
                Log::error('FreeRADIUS provisioning failed after payment', [
                    'payment_id' => $payment->id,
                    'session_id' => $session->id,
                    'username' => $username,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $sessionManager = app(SessionManager::class);
        $activation = $sessionManager->activateSession($session->fresh(), $payment->package);

        if (!($activation['success'] ?? false)) {
            $session->update([
                'status' => 'idle',
                'expires_at' => $expiresAt,
                'metadata' => array_merge($session->metadata ?? [], [
                    'activation' => array_merge(
                        (array) (($session->metadata ?? [])['activation'] ?? []),
                        [
                            'last_failed_at' => now()->toIso8601String(),
                            'last_error' => (string) ($activation['error'] ?? 'unknown'),
                            'queued' => (bool) ($activation['queued'] ?? false),
                        ]
                    ),
                ]),
            ]);

            Log::warning('MikroTik activation not completed', [
                'payment_id' => $payment->id,
                'session_id' => $session->id,
                'reason' => $activation['error'] ?? 'unknown',
                'queued' => (bool) ($activation['queued'] ?? false),
            ]);
            return $session->fresh();
        }

        if ($payment->activated_at === null) {
            $payment->update(['activated_at' => now()]);
        }

        Log::info('Paid access activated on MikroTik', [
            'payment_id' => $payment->id,
            'session_id' => $session->id,
            'username' => $session->username,
        ]);

        return $session->fresh();
    }

    private function resolveRouterIdForPayment(Payment $payment): ?int
    {
        return (int) (\App\Models\Router::query()
            ->where('tenant_id', $payment->tenant_id)
            ->orderByRaw(
                "CASE WHEN status = ? THEN 0 WHEN status = ? THEN 1 ELSE 2 END",
                [\App\Models\Router::STATUS_ONLINE, \App\Models\Router::STATUS_WARNING]
            )
            ->orderByDesc('last_seen_at')
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

    private function extractExplicitTenantId(Request $request): int
    {
        foreach ([
            $request->query('tenant_id'),
            $request->input('tenant_id'),
        ] as $candidate) {
            $tenantId = (int) $candidate;
            if ($tenantId > 0) {
                return $tenantId;
            }
        }

        return 0;
    }

    private function resolveClientContext(Request $request): array
    {
        $macInput = '';
        foreach ([
            (string) $request->input('mac', ''),
            (string) $request->input('mac-address', ''),
            (string) $request->input('client_mac', ''),
            (string) $request->input('clientMac', ''),
            (string) $request->query('mac', ''),
            (string) $request->query('mac-address', ''),
            (string) $request->query('client_mac', ''),
            (string) $request->query('clientMac', ''),
            (string) session('captive_client_mac', ''),
        ] as $candidate) {
            if (trim($candidate) !== '') {
                $macInput = $candidate;
                break;
            }
        }

        $ipInput = '';
        foreach ([
            (string) $request->input('ip', ''),
            (string) $request->input('ip-address', ''),
            (string) $request->input('client_ip', ''),
            (string) $request->input('clientIp', ''),
            (string) $request->query('ip', ''),
            (string) $request->query('ip-address', ''),
            (string) $request->query('client_ip', ''),
            (string) $request->query('clientIp', ''),
            (string) session('captive_client_ip', ''),
        ] as $candidate) {
            if (trim($candidate) !== '') {
                $ipInput = $candidate;
                break;
            }
        }

        $mac = $this->normalizeMacAddress($macInput);
        $ip = $this->normalizeClientIpAddress($ipInput);

        if ($mac !== null) {
            session(['captive_client_mac' => $mac]);
        }

        if ($ip !== null) {
            session(['captive_client_ip' => $ip]);
        }

        return [
            'mac' => $mac,
            'ip' => $ip,
        ];
    }

    private function buildClientContextMeta(array $clientContext, Request $request): array
    {
        $meta = array_filter([
            'mac' => $clientContext['mac'] ?? null,
            'ip' => $clientContext['ip'] ?? null,
        ], static fn ($value) => $value !== null && $value !== '');

        $userAgent = trim((string) $request->userAgent());
        if ($userAgent !== '') {
            $meta['user_agent'] = $userAgent;
        }

        $requestIp = $this->normalizeClientIpAddress((string) $request->ip());
        if ($requestIp !== null) {
            $meta['request_ip'] = $requestIp;
        }

        return $meta;
    }

    private function captureClientContextForPayment(Request $request, Payment $payment): Payment
    {
        $clientContext = $this->resolveClientContext($request);
        $clientContextMeta = $this->buildClientContextMeta($clientContext, $request);

        if ($clientContextMeta === []) {
            return $payment;
        }

        $paymentMetadata = is_array($payment->metadata) ? $payment->metadata : [];
        $existingClientMeta = is_array($paymentMetadata['client_context'] ?? null) ? $paymentMetadata['client_context'] : [];

        foreach (['user_agent', 'request_ip'] as $stickyKey) {
            if (!empty($existingClientMeta[$stickyKey]) && !empty($clientContextMeta[$stickyKey])) {
                $clientContextMeta[$stickyKey] = $existingClientMeta[$stickyKey];
            }
        }

        $mergedClientMeta = array_merge($existingClientMeta, $clientContextMeta);

        if ($mergedClientMeta !== $existingClientMeta) {
            $paymentMetadata['client_context'] = $mergedClientMeta;

            try {
                $payment->update(['metadata' => $paymentMetadata]);
                $payment->refresh();
            } catch (\Throwable $e) {
                Log::warning('Captive client context update skipped after transient payment lock', [
                    'payment_id' => $payment->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $session = UserSession::query()->where('payment_id', $payment->id)->first();
        if ($session && (string) $session->status !== 'active') {
            $sessionUpdates = [];
            $clientMac = $clientContext['mac'] ?? null;
            $clientIp = $clientContext['ip'] ?? null;

            if ($clientMac !== null && $session->mac_address !== $clientMac) {
                $sessionUpdates['mac_address'] = $clientMac;
            }

            if ($clientIp !== null && $session->ip_address !== $clientIp) {
                $sessionUpdates['ip_address'] = $clientIp;
            }

            if ($sessionUpdates !== []) {
                try {
                    $session->update($sessionUpdates);
                } catch (\Throwable $e) {
                    Log::warning('Captive session context update skipped after transient lock', [
                        'payment_id' => $payment->id,
                        'session_id' => $session->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }

        return $payment;
    }

    private function normalizeMacAddress(string $mac): ?string
    {
        $normalized = strtoupper(preg_replace('/[^0-9A-Fa-f]/', '', $mac) ?? '');
        if (strlen($normalized) !== 12) {
            return null;
        }

        return implode(':', str_split($normalized, 2));
    }

    private function normalizeClientIpAddress(string $ipAddress): ?string
    {
        $candidate = trim($ipAddress);
        if ($candidate === '') {
            return null;
        }

        return filter_var($candidate, FILTER_VALIDATE_IP) ? $candidate : null;
    }

    private function resolveRadiusUsernameFromPhone(string $phone, ?int $paymentId = null): string
    {
        $digits = preg_replace('/\D+/', '', $phone);
        if ($digits !== '') {
            return 'cb' . $digits;
        }

        return 'cbu' . (int) $paymentId;
    }

    private function resolveDarajaCallbackUrl(?int $tenantId = null): string
    {
        $configured = trim((string) config('services.mpesa.callback_url', ''));
        if ($configured !== '') {
            return $configured;
        }

        if (($tenantId ?? 0) > 0 && \Route::has('api.mpesa.callback')) {
            return route('api.mpesa.callback', ['tenant' => $tenantId]);
        }

        return url('/api/mpesa/callback');
    }

    private function resolvePaymentGateway(): string
    {
        return 'daraja';
    }

    private function findReusableCaptivePaymentAttempt(int $tenantId, string $phone, int $packageId): ?Payment
    {
        $candidates = Payment::query()
            ->where('tenant_id', $tenantId)
            ->where('phone', $phone)
            ->where('package_id', $packageId)
            ->where('payment_channel', 'captive_portal')
            ->whereIn('status', ['initiated', 'pending', 'failed', 'confirmed', 'completed'])
            ->where('created_at', '>=', now()->subMinutes(self::DUPLICATE_PAYMENT_WINDOW_MINUTES))
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->limit(6)
            ->get();

        foreach ($candidates as $candidate) {
            if ($this->shouldReuseCaptivePaymentAttempt($candidate)) {
                return $candidate;
            }
        }

        return null;
    }

    private function shouldReuseCaptivePaymentAttempt(Payment $payment): bool
    {
        $status = (string) $payment->status;

        if (in_array($status, ['initiated', 'pending', 'confirmed', 'completed'], true)) {
            return true;
        }

        if ($status === 'failed' && $this->paymentNeedsVerification($payment)) {
            return true;
        }

        return false;
    }

    private function buildPaymentInitiationLockKey(int $tenantId, string $phone, int $packageId): string
    {
        return sprintf('captive-pay:%d:%s:%d', $tenantId, preg_replace('/\D+/', '', $phone) ?: $phone, $packageId);
    }

    private function isGatewayConfigured(string $gateway): bool
    {
        return app(DarajaService::class)->isConfigured();
    }
}
