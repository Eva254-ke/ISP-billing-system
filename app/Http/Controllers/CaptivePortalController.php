<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Models\Package;
use App\Models\Payment;
use App\Models\Tenant;
use App\Models\Voucher;
use App\Services\MikroTik\MikroTikService;
use App\Services\Mpesa\DarajaService;
use Illuminate\Support\Facades\Cache; // Changed from Redis to universal Cache
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
        // ULTIMATE SAFETY NET: Catch any unexpected error to prevent 500 crash
        try {
            $tenant = $this->resolveTenant($request);
            if (!$tenant) {
                return response('Network configuration error. Please contact support.', 400);
            }

            $mac = $this->cleanMac($request->query('mac') ?? $request->query('mac-address') ?? '');
            $ip = $request->query('ip') ?? $request->query('ip-address') ?? $request->ip();

            // STRICT CHECK: If MAC is missing, return simple text (No missing Blade view crash)
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
                $pMac = $payment->metadata['mac'] ?? '';
                $pIp = $payment->metadata['ip'] ?? '';
                
                if ($pMac && !$this->isDeviceAuthorized($pMac, $pIp)) {
                    $this->grantNetworkAccess($pMac, $pIp, $payment->tenant_id);
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

            $voucher = Voucher::where('code', $code)->where('is_used', false)->first();
            if ($voucher) {
                $voucher->update(['is_used' => true, 'used_by_mac' => $mac, 'used_at' => now()]);
                $this->grantNetworkAccess($mac, $ip, $voucher->tenant_id, ($voucher->duration_minutes ?? 1440) * 60);
                return response()->json(['status' => 'connected', 'message' => 'Voucher redeemed!']);
            }

            $payment = Payment::where('mpesa_receipt_number', $code)
                ->whereIn('status', ['completed', 'confirmed'])
                ->first();
                
            if ($payment) {
                $this->grantNetworkAccess($mac, $ip, $payment->tenant_id);
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

        $payment = Payment::create([
            'tenant_id' => $package->tenant_id,
            'phone' => $normalizedPhone,
            'package_id' => $package->id,
            'amount' => $package->price,
            'status' => 'pending',
            'type' => 'captive_portal',
            'metadata' => ['mac' => $mac, 'ip' => $ip, 'package_name' => $package->name]
        ]);

        try {
            $triggered = false;
            
            if (method_exists($this->daraja, 'initiateStkPush')) {
                try {
                    $this->daraja->initiateStkPush($payment, $normalizedPhone, $package->price);
                    $triggered = true;
                } catch (\ArgumentCountError $e) {
                    $this->daraja->initiateStkPush($normalizedPhone, $package->price, $payment->id);
                    $triggered = true;
                }
            } elseif (method_exists($this->daraja, 'stkPush')) {
                $this->daraja->stkPush($normalizedPhone, $package->price, $payment->id);
                $triggered = true;
            } elseif (method_exists($this->daraja, 'sendStkPush')) {
                $this->daraja->sendStkPush($normalizedPhone, $package->price);
                $triggered = true;
            }
            
            if (!$triggered) {
                throw new \Exception("DarajaService is missing a recognized STK push method.");
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
        $voucher = Voucher::where('code', strtoupper(trim($code)))->where('is_used', false)->first();

        if (!$voucher) {
            return response()->json(['status' => 'failed', 'message' => 'Invalid or used voucher.'], 400);
        }

        $voucher->update(['is_used' => true, 'used_by_mac' => $mac, 'used_at' => now()]);
        $this->grantNetworkAccess($mac, $ip, $voucher->tenant_id, ($voucher->duration_minutes ?? 1440) * 60);

        return response()->json(['status' => 'connected', 'message' => 'Voucher redeemed!']);
    }

    private function grantNetworkAccess(string $mac, string $ip, int $tenantId, int $ttlSeconds = 86400): void
    {
        // 1. Set Cache FIRST (Using universal Cache facade instead of Redis to prevent 500s if Redis is down)
        try {
            Cache::put("wifi:auth:{$mac}", $ip, $ttlSeconds);
            Cache::put("wifi:auth:{$ip}", $mac, $ttlSeconds);
        } catch (\Throwable $e) {
            Log::warning('Cache set failed', ['error' => $e->getMessage()]);
        }

        // 2. Attempt to provision on MikroTik
        try {
            $this->mikrotik->authorizeDevice($mac, $ip, $ttlSeconds);
        } catch (\Throwable $e) {
            Log::error('MikroTik Router API Timeout/Failed', [
                'error' => $e->getMessage(), 
                'mac' => $mac, 
                'ip' => $ip,
                'tenant_id' => $tenantId
            ]);
        }
    }

    private function isDeviceAuthorized(string $mac, string $ip): bool
    {
        if (!$mac && !$ip) return false;
        try {
            return ($mac && Cache::get("wifi:auth:{$mac}")) 
                || ($ip && Cache::get("wifi:auth:{$ip}"));
        } catch (\Throwable $e) {
            return false; // Fail safely if cache is down
        }
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