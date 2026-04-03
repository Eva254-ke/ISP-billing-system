<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Package;
use App\Models\Payment;
use App\Models\Tenant;
use App\Models\UserSession;
use App\Models\Voucher;
use App\Services\MikroTik\MikroTikService;
use App\Services\IntaSend\IntaSendService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class CaptivePortalController extends Controller
{
    /**
     * Show package selection page (public, no auth required)
     */
    public function packages(Request $request)
    {
        $tenant = $this->resolveTenant($request);
        $phone = session('captive_phone') ?? $request->query('phone');

        if (!$tenant) {
            return response()->view('captive.packages', [
                'packages' => collect(),
                'activeSession' => null,
                'phone' => $phone,
                'tenant' => null,
                'tenantResolutionError' => 'Tenant portal not resolved. Use your tenant domain (e.g. https://your-subdomain.cloudbridge.network/wifi) or include tenant_id in the URL.',
            ], 400);
        }

        session(['captive_tenant_id' => $tenant->id]);
        
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
        $tenantId = (int) session('captive_tenant_id');

        $request->validate([
            'phone' => 'required|regex:/^0[17]\d{8}$/',
            'package_id' => 'required|exists:packages,id'
        ]);

        $package = Package::query()
            ->when($tenantId > 0, fn ($query) => $query->where('tenant_id', $tenantId))
            ->where('is_active', true)
            ->findOrFail($request->package_id);

        if (!$this->isIntaSendConfigured()) {
            return back()->withErrors(['Payment gateway is not configured. Please contact support.']);
        }
        
        $payment = DB::transaction(function () use ($request, $package) {
            return Payment::create([
                'tenant_id' => $package->tenant_id,
                'phone' => $request->phone,
                'package_id' => $package->id,
                'package_name' => $package->name,
                'amount' => $package->price,
                'currency' => $package->currency ?? 'KES',
                'mpesa_checkout_request_id' => 'CP-' . strtoupper(uniqid()),
                'status' => 'pending',
                'initiated_at' => now(),
                'payment_channel' => 'captive_portal',
            ]);
        });
        
        Log::info('Captive payment initiated', [
            'phone' => $request->phone,
            'package' => $package->name,
            'amount' => $package->price,
            'reference' => $payment->mpesa_checkout_request_id,
        ]);
        
        session(['captive_phone' => $request->phone]);
        
        try {
            $intasend = app(IntaSendService::class);
            $response = $intasend->stkPush(
                phone: $this->normalizePhoneForStk($request->phone),
                amount: (float) $package->price,
                accountRef: $payment->mpesa_checkout_request_id,
                narration: 'CloudBridge WiFi - ' . $package->name,
                callbackUrl: (string) (config('services.intasend.callback_url') ?: route('api.payment.callback'))
            );
            
            if ($response['success']) {
                return redirect()->route('wifi.status', ['phone' => $request->phone])
                    ->with('message', 'Check your phone to complete payment');
            }
            
            Log::warning('STK Push failed', ['response' => $response]);
            return back()->withErrors(['Payment initiation failed. Try again.']);
            
        } catch (\Exception $e) {
            Log::error('STK Push exception', [
                'error' => $e->getMessage(),
                'phone' => $request->phone
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
    
    /**
     * Check payment status
     */
    public function status($phone)
    {
        $tenantId = (int) session('captive_tenant_id');

        $payment = Payment::where('phone', $phone)
            ->when($tenantId > 0, fn ($query) => $query->where('tenant_id', $tenantId))
            ->whereIn('payment_channel', ['captive_portal', 'voucher'])
            ->orderBy('created_at', 'desc')
            ->first();
        
        if (!$payment) {
            return redirect()->route('wifi.packages')
                ->withErrors(['No payment found. Please start again.']);
        }
        
        if ($payment->status === 'completed') {
            try {
                $existingSession = UserSession::where('payment_id', $payment->id)
                    ->active()
                    ->first();

                if (!$existingSession) {
                    $routerId = $this->resolveRouterIdForPayment($payment);
                    $durationMinutes = $payment->package?->duration_in_minutes ?? 60;

                    if ($routerId) {
                        UserSession::create([
                            'tenant_id' => $payment->tenant_id,
                            'router_id' => $routerId,
                            'package_id' => $payment->package_id,
                            'username' => $this->resolveRadiusUsernameFromPhone($payment->phone, $payment->id),
                            'phone' => $payment->phone,
                            'status' => 'active',
                            'started_at' => now(),
                            'expires_at' => now()->copy()->addMinutes($durationMinutes),
                            'payment_id' => $payment->id,
                        ]);

                        Log::info('Session activated', ['payment_id' => $payment->id]);
                    } else {
                        Log::warning('Session activation skipped: no router found for tenant', [
                            'payment_id' => $payment->id,
                            'tenant_id' => $payment->tenant_id,
                        ]);
                    }
                }
                
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

        $statusView = (string) $payment->status;
        if ($activeSession && in_array($statusView, ['completed', 'confirmed', 'paid', 'activated'], true)) {
            $statusView = 'activated';
        } elseif (in_array($statusView, ['completed', 'confirmed'], true) && !$activeSession) {
            $statusView = 'paid';
        }

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
        $tenantId = (int) session('captive_tenant_id');

        if ($request->filled('voucher_code')) {
            $request->validate([
                'voucher_code' => 'required|string|max:64',
                'phone' => 'required|regex:/^0[17]\d{8}$/',
            ]);

            $phone = trim($request->phone);
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

            $activeSession = UserSession::where('phone', $phone)->active()->first();
            if ($activeSession) {
                return redirect()->route('wifi.status', ['phone' => $phone])
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
                DB::transaction(function () use ($voucher, $phone, $routerId) {
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
                });

                session(['captive_phone' => $phone]);

                return redirect()->route('wifi.status', ['phone' => $phone])
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
            'mpesa_code' => 'required|string|max:32',
            'phone' => 'required|regex:/^0[17]\d{8}$/'
        ]);
        
        $mpesaCode = strtoupper(trim($request->mpesa_code));
        $phone = trim($request->phone);
        
        $payment = Payment::where(function($query) use ($mpesaCode) {
                $query->where('mpesa_transaction_id', $mpesaCode)
                    ->orWhere('mpesa_receipt_number', $mpesaCode);
            })
            ->when($tenantId > 0, fn ($query) => $query->where('tenant_id', $tenantId))
            ->where('phone', $phone)
            ->whereIn('status', ['completed', 'confirmed'])
            ->first();
        
        if (!$payment) {
            return redirect()->back()
                ->withErrors(['Invalid M-Pesa code or phone number. Please check and try again.'])
                ->withInput();
        }
        
        $activeSession = UserSession::where('phone', $phone)
            ->active()
            ->first();
        
        if ($activeSession) {
            return redirect()->route('wifi.status', ['phone' => $phone])
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
            
            return redirect()->route('wifi.status', ['phone' => $phone])
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
        $request->validate([
            'phone' => 'required|regex:/^0[17]\d{8}$/',
            'package_id' => 'required|exists:packages,id'
        ]);
        
        $phone = trim($request->phone);
        $package = Package::findOrFail($request->package_id);

        if (!$this->isIntaSendConfigured()) {
            return back()->withErrors(['Payment gateway is not configured. Please contact support.']);
        }
        
        $activeSession = UserSession::where('phone', $phone)
            ->active()
            ->first();
        
        if (!$activeSession) {
            return redirect()->route('wifi.packages')
                ->withErrors(['No active session found. Please purchase a package first.']);
        }
        
        $payment = DB::transaction(function () use ($phone, $package, $activeSession) {
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
                    'parent_payment_id' => $activeSession->payment_id,
                ],
            ]);
        });
        
        try {
            $intasend = app(IntaSendService::class);
            $response = $intasend->stkPush(
                phone: $this->normalizePhoneForStk($phone),
                amount: (float) $package->price,
                accountRef: $payment->mpesa_checkout_request_id,
                narration: 'CloudBridge Session Extension - ' . $package->name,
                callbackUrl: (string) (config('services.intasend.callback_url') ?: route('api.payment.callback'))
            );
            
            if ($response['success']) {
                Log::info('Extension STK sent', [
                    'phone' => $phone,
                    'amount' => $package->price,
                    'reference' => $payment->mpesa_checkout_request_id,
                ]);
                
                return redirect()->route('wifi.status', ['phone' => $phone])
                    ->with('message', 'Complete STK Push to extend your session');
            }
            
            return back()->withErrors(['STK Push failed. Try again.']);
            
        } catch (\Exception $e) {
            Log::error('Extension STK failed', [
                'phone' => $phone,
                'error' => $e->getMessage()
            ]);
            return back()->withErrors(['Payment service unavailable.']);
        }
    }
    
    /**
     * AJAX endpoint to check session status (for polling)
     */
    public function checkStatus($phone)
    {
        $payment = Payment::where('phone', $phone)
            ->where('payment_channel', 'captive_portal')
            ->orderBy('created_at', 'desc')
            ->first();
        
        if (!$payment) {
            return response()->json(['status' => 'not_found']);
        }
        
        $session = UserSession::where('phone', $phone)
            ->active()
            ->first();
        
        return response()->json([
            'status' => $payment->status,
            'session_active' => $session ? true : false,
            'expires_at' => $session ? $session->expires_at->toIso8601String() : null,
            'package' => $payment->package ? [
                'name' => $payment->package->name,
                'duration_minutes' => $payment->package->duration_in_minutes,
            ] : null
        ]);
    }
    
    /**
     * IntaSend payment callback webhook
     */
    public function callback(Request $request)
    {
        $data = $request->all();
        
        Log::info('IntaSend callback received', $data);
        
        $reference = $data['reference'] ?? null;
        $status = $data['status'] ?? null;
        
        if (!$reference || !$status) {
            return response()->json(['error' => 'Missing required fields'], 400);
        }
        
        $payment = Payment::where('mpesa_checkout_request_id', $reference)->first();
        
        if (!$payment) {
            Log::warning('Callback for unknown reference', ['reference' => $reference]);
            return response()->json(['error' => 'Payment not found'], 404);
        }
        
        if ($payment->status !== 'pending') {
            Log::info('Duplicate callback ignored', [
                'reference' => $reference,
                'current_status' => $payment->status
            ]);
            return response()->json(['success' => true]);
        }
        
        if ($status === 'SUCCESS') {
            DB::transaction(function () use ($payment, $data) {
                $payment->update([
                    'status' => 'completed',
                    'mpesa_transaction_id' => $data['transaction_id'] ?? $payment->mpesa_transaction_id,
                    'mpesa_receipt_number' => $data['mpesa_code'] ?? $payment->mpesa_receipt_number,
                    'callback_data' => $data,
                    'confirmed_at' => now(),
                    'completed_at' => now(),
                ]);
            });
            
            Log::info('Payment confirmed via callback', [
                'reference' => $reference,
                'payment_id' => $payment->id
            ]);
        } else {
            $payment->update([
                'status' => 'failed',
                'callback_data' => $data,
                'failed_at' => now()
            ]);
            
            Log::warning('Payment failed via callback', [
                'reference' => $reference,
                'status' => $status
            ]);
        }
        
        return response()->json(['success' => true]);
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
        $digits = preg_replace('/\D+/', '', $phone);

        if (str_starts_with($digits, '0')) {
            return '254' . substr($digits, 1);
        }

        if (str_starts_with($digits, '254')) {
            return $digits;
        }

        return $digits;
    }

    private function resolveRadiusUsernameFromPhone(string $phone, ?int $paymentId = null): string
    {
        $digits = preg_replace('/\D+/', '', $phone);
        if ($digits !== '') {
            return 'cb' . $digits;
        }

        return 'cbu' . (int) $paymentId;
    }

    private function isIntaSendConfigured(): bool
    {
        $publicKey = (string) config('services.intasend.public_key', '');
        $secretKey = (string) config('services.intasend.secret_key', '');

        if (trim($publicKey) === '' || trim($secretKey) === '') {
            return false;
        }

        $placeholders = [
            'your_public_key_here',
            'your_secret_key_here',
            'changeme',
        ];

        return !in_array(strtolower(trim($publicKey)), $placeholders, true)
            && !in_array(strtolower(trim($secretKey)), $placeholders, true);
    }
}