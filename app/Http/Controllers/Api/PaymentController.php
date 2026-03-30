<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Package;
use App\Models\UserSession;
use App\Services\Payment\PaymentRouter;
use App\Services\MikroTik\SessionManager;
use App\Services\IntaSend\IntaSendService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class PaymentController extends Controller
{
    public function __construct(
        protected PaymentRouter $paymentRouter,
        protected SessionManager $sessionManager,
        protected IntaSendService $intasend
    ) {}

    /**
     * List all payments for tenant
     */
    public function index(Request $request): JsonResponse
    {
        $tenant = $request->user()->tenant;
        
        $payments = $tenant->payments()
            ->with(['package', 'session'])
            ->latest()
            ->paginate(50);

        return response()->json([
            'success' => true,
            'data' => $payments->items(),
            'pagination' => [
                'current_page' => $payments->currentPage(),
                'per_page' => $payments->perPage(),
                'total' => $payments->total(),
                'last_page' => $payments->lastPage(),
            ],
        ]);
    }

    /**
     * Get single payment details
     */
    public function show(Request $request, Payment $payment): JsonResponse
    {
        $tenant = $request->user()->tenant;
        
        if ($payment->tenant_id !== $tenant->id) {
            return response()->json([
                'success' => false,
                'message' => 'Payment not found',
            ], 404);
        }

        $payment->load(['package', 'session']);

        return response()->json([
            'success' => true,
            'data' => $payment,
        ]);
    }

    /**
     * Initiate IntaSend STK Push payment
     */
    public function initiate(Request $request): JsonResponse
    {
        $request->validate([
            'phone' => 'required|string|regex:/^254[0-9]{9}$/',
            'package_id' => 'required|exists:packages,id',
            'session_code' => 'required|string|exists:user_sessions,account_code',
        ]);

        $tenant = $request->user()->tenant;
        $package = Package::findOrFail($request->package_id);
        $session = UserSession::where('account_code', $request->session_code)->first();

        // Authorization checks
        if ($package->tenant_id !== $tenant->id || $session->tenant_id !== $tenant->id) {
            return response()->json(['success' => false, 'message' => 'Not authorized'], 403);
        }

        // Check tenant has IntaSend configured
        if (!$tenant->intasend_public_key || !$tenant->intasend_secret_key) {
            return response()->json([
                'success' => false,
                'message' => 'Payment method not configured. Contact support.',
            ], 400);
        }

        DB::beginTransaction();
        
        try {
            // Create payment record (idempotent)
            $payment = Payment::firstOrCreate(
                [
                    'tenant_id' => $tenant->id,
                    'session_id' => $session->id,
                    'reference' => 'INTA-' . Str::uuid()->toString(),
                ],
                [
                    'phone' => $request->phone,
                    'package_id' => $package->id,
                    'package_name' => $package->name,
                    'amount' => $package->price,
                    'currency' => 'KES',
                    'status' => 'pending',
                    'payment_channel' => 'intasend',
                    'initiated_at' => now(),
                ]
            );

            // If already paid, return success
            if ($payment->status === 'completed') {
                DB::commit();
                return response()->json([
                    'success' => true,
                    'message' => 'Payment already completed',
                    'data' => $payment->only(['id', 'status', 'amount']),
                ]);
            }

            // Initiate STK Push via IntaSend
            $result = $this->intasend->stkPush(
                phone: $request->phone,
                amount: $package->price,
                accountRef: $payment->reference,
                narration: "CloudBridge WiFi - {$package->name}",
                callbackUrl: route('api.payment.callback', absolute: false)
            );

            if (!$result['success']) {
                DB::rollBack();
                
                Log::channel('payment')->error('IntaSend STK Push failed', [
                    'payment_id' => $payment->id,
                    'error' => $result['error'] ?? 'Unknown',
                    'phone' => $request->phone,
                ]);
                
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to send STK Push',
                    'error' => config('app.debug') ? ($result['error'] ?? null) : null,
                ], 500);
            }

            // Update payment with IntaSend reference
            $payment->update([
                'intasend_reference' => $result['reference'] ?? null,
                'checkout_request_id' => $result['checkout_request_id'] ?? null,
                'status' => 'awaiting_payment',
            ]);

            DB::commit();

            Log::channel('payment')->info('IntaSend STK Push initiated', [
                'payment_id' => $payment->id,
                'reference' => $payment->reference,
                'phone' => $request->phone,
                'amount' => $package->price,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'STK Push sent! Check your phone to complete payment.',
                'data' => [
                    'payment_id' => $payment->id,
                    'reference' => $payment->reference,
                    'phone' => $request->phone,
                    'amount' => $package->price,
                    'package' => $package->name,
                    'status' => 'awaiting_payment',
                    'expires_in_seconds' => 120,
                ],
            ], 202);

        } catch (\Illuminate\Database\QueryException $e) {
            DB::rollBack();
            
            if ($e->getCode() == 23000) {
                return response()->json([
                    'success' => false,
                    'message' => 'Payment already in progress',
                    'retry_after' => 30,
                ], 409);
            }
            throw $e;
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::channel('payment')->error('Payment initiation failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'phone' => $request->phone,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to initiate payment',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Handle IntaSend Webhook Callback
     * Route: POST /api/payment/callback
     */
    public function callback(Request $request): JsonResponse
    {
        $data = $request->all();
        
        // Log raw callback (sanitize sensitive data)
        Log::channel('payment')->info('IntaSend webhook received', [
            'reference' => $data['reference'] ?? null,
            'status' => $data['status'] ?? null,
            'amount' => $data['amount'] ?? null,
            'ip' => $request->ip(),
        ]);

        // ──────────────────────────────────────────────────────────────────
        // SECURITY: Verify webhook signature (production only)
        // ──────────────────────────────────────────────────────────────────
        if (config('app.env') === 'production') {
            $signature = $request->header('X-IntaSend-Signature');
            if (!$this->verifyIntaSendSignature($request, $signature)) {
                Log::channel('payment')->warning('Invalid webhook signature', [
                    'ip' => $request->ip(),
                    'signature' => $signature,
                ]);
                return response()->json(['error' => 'Invalid signature'], 401);
            }
        }

        // ──────────────────────────────────────────────────────────────────
        // RATE LIMIT: Prevent webhook abuse
        // ──────────────────────────────────────────────────────────────────
        $rateLimitKey = "webhook:intasend:" . $request->ip();
        if (Cache::get($rateLimitKey, 0) > 20) {
            return response()->json(['error' => 'Rate limit exceeded'], 429);
        }
        Cache::increment($rateLimitKey, 1, 60);

        // ──────────────────────────────────────────────────────────────────
        // PROCESS: Queue for async handling (idempotent)
        // ──────────────────────────────────────────────────────────────────
        \App\Jobs\ProcessIntaSendCallback::dispatch($data)
            ->onQueue('critical')
            ->delay(now()->addSeconds(2));

        // ──────────────────────────────────────────────────────────────────
        // ACK: Return immediate success to IntaSend
        // ──────────────────────────────────────────────────────────────────
        return response()->json(['status' => 'received'], 200);
    }

    /**
     * Verify IntaSend webhook signature
     */
    private function verifyIntaSendSignature(Request $request, ?string $signature): bool
    {
        if (!$signature) return false;
        
        $secret = config('services.intasend.webhook_secret');
        if (!$secret) return true; // Skip if not configured
        
        $payload = $request->getContent();
        $expectedSignature = hash_hmac('sha256', $payload, $secret);
        
        return hash_equals($expectedSignature, $signature);
    }

    /**
     * Get payment statistics
     */
    public function stats(Request $request): JsonResponse
    {
        $tenant = $request->user()->tenant;
        
        $today = now()->startOfDay();
        $week = now()->startOfWeek();
        $month = now()->startOfMonth();

        $stats = [
            'today' => [
                'total' => $tenant->payments()->whereDate('created_at', today())->count(),
                'revenue' => $tenant->payments()
                    ->whereDate('created_at', today())
                    ->where('status', 'completed')
                    ->sum('amount'),
                'commission_owed' => $tenant->payments()
                    ->whereDate('created_at', today())
                    ->where('status', 'completed')
                    ->get()
                    ->sum(fn($p) => $tenant->calculateCommission($p->amount)),
            ],
            'week' => [
                'total' => $tenant->payments()->where('created_at', '>=', $week)->count(),
                'revenue' => $tenant->payments()
                    ->where('created_at', '>=', $week)
                    ->where('status', 'completed')
                    ->sum('amount'),
            ],
            'month' => [
                'total' => $tenant->payments()->where('created_at', '>=', $month)->count(),
                'revenue' => $tenant->payments()
                    ->where('created_at', '>=', $month)
                    ->where('status', 'completed')
                    ->sum('amount'),
            ],
            'pending' => $tenant->payments()->where('status', 'pending')->count(),
            'failed' => $tenant->payments()->where('status', 'failed')->count(),
            'completed' => $tenant->payments()->where('status', 'completed')->count(),
            'total_revenue' => $tenant->payments()
                ->where('status', 'completed')
                ->sum('amount'),
            'total_commission_owed' => $tenant->commission_owed,
        ];

        return response()->json(['success' => true, 'data' => $stats]);
    }
}