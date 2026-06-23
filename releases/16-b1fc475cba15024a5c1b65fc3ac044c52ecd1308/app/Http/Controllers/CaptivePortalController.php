<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Package;
use App\Models\Payment;
use App\Models\Tenant;
use App\Models\UserSession;
use App\Services\MikroTik\MikroTikService;
use App\Services\IntaSend\IntaSendService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CaptivePortalController extends Controller
{
    /**
     * Show package selection page (public, no auth required)
     */
    public function packages(Request $request)
    {
        $tenant = $this->resolveTenant($request);

        if (!$tenant) {
            return back()->withErrors(['No active tenant was found for this portal request.']);
        }

        session(['captive_tenant_id' => $tenant->id]);

        $phone = session('captive_phone') ?? $request->query('phone');
        
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

        if (Schema::hasColumn('packages', 'captive_portal_visible')) {
            $packagesQuery->where('captive_portal_visible', true)
                ->orderBy('captive_portal_priority');
        }

        $packages = $packagesQuery
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
            $response = $intasend->sendStk(
                $request->phone,
                $package->price,
                $payment->mpesa_checkout_request_id
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
        $tenantId = (int) ($request->query('tenant_id') ?? session('captive_tenant_id'));
        if ($tenantId > 0) {
            return Tenant::active()->find($tenantId);
        }

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

        return Tenant::active()->orderBy('id')->first();
    }
    
    /**
     * Check payment status
     */
    public function status($phone)
    {
        $tenantId = (int) session('captive_tenant_id');

        $payment = Payment::where('phone', $phone)
            ->when($tenantId > 0, fn ($query) => $query->where('tenant_id', $tenantId))
            ->where('payment_channel', 'captive_portal')
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
                            'username' => $this->generateUsername($payment->phone),
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
        
        return view('captive.status', compact('payment', 'phone'));
    }
    
    /**
     * Reconnect with M-Pesa transaction code
     */
    public function reconnect(Request $request)
    {
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
            $response = $intasend->sendStk($phone, $package->price, $payment->mpesa_checkout_request_id);
            
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
}