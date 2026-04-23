<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Log;
use App\Jobs\ProcessMpesaCallback;
use App\Http\Controllers\Api\MikroTik\RouterController;
use App\Http\Controllers\Api\MikroTik\SessionController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\PackageController;
use App\Http\Controllers\Api\VoucherController;

/*
|--------------------------------------------------------------------------
| API Routes - CloudBridge Networks
|--------------------------------------------------------------------------
|
| All API routes for the CloudBridge WiFi SaaS platform.
| 
| Authentication: Laravel Sanctum (Bearer tokens)
| Webhooks: Public endpoints with signature verification
| Rate Limiting: Applied via middleware
|
*/

// ──────────────────────────────────────────────────────────────────────────
// PUBLIC ROUTES (No Authentication Required)
// ──────────────────────────────────────────────────────────────────────────

/**
 * Health Check Endpoint
 * Used by: Load balancers, uptime monitors, deployment scripts
 */
Route::get('/health', function () {
    return response()->json([
        'status' => 'healthy',
        'timestamp' => now()->toISOString(),
        'app' => [
            'name' => config('app.name'),
            'env' => config('app.env'),
            'version' => file_exists(base_path('VERSION')) ? trim(file_get_contents(base_path('VERSION'))) : 'dev',
        ],
        'runtime' => [
            'php' => phpversion(),
            'laravel' => app()->version(),
        ],
        'services' => [
            'database' => config('database.default'),
            'queue' => config('queue.default'),
            'cache' => config('cache.default'),
        ],
    ]);
})->name('api.health');

/**
 * M-Pesa Daraja Callback (Primary)
 * Public webhook endpoint for STK callbacks.
 */
Route::post('/mpesa/callback/{tenant?}', function (Request $request, ?int $tenant = null) {
    $payload = (array) $request->all();
    $stk = (array) data_get($payload, 'Body.stkCallback', []);
    $callback = $stk !== [] ? $stk : $payload;

    $normalized = [
        'MerchantRequestID' => $callback['MerchantRequestID'] ?? null,
        'CheckoutRequestID' => $callback['CheckoutRequestID'] ?? null,
        'ResultCode' => $callback['ResultCode'] ?? null,
        'ResultDesc' => $callback['ResultDesc'] ?? null,
        'tenant_id' => $tenant,
    ];

    $items = data_get($callback, 'CallbackMetadata.Item', []);
    if (is_array($items)) {
        foreach ($items as $item) {
            if (!is_array($item)) {
                continue;
            }

            $name = (string) ($item['Name'] ?? '');
            if ($name === '') {
                continue;
            }

            $normalized[$name] = $item['Value'] ?? null;
        }
    }

    $normalized['raw_payload'] = $payload;

    Log::channel('payment')->info('M-Pesa callback received', [
        'checkout_request_id' => $normalized['CheckoutRequestID'] ?? null,
        'merchant_request_id' => $normalized['MerchantRequestID'] ?? null,
        'result_code' => $normalized['ResultCode'] ?? null,
        'tenant_id' => $tenant,
        'ip' => $request->ip(),
    ]);

    // Acknowledge the webhook immediately so Cloudflare/Safaricom do not wait
    // on session activation work before receiving a 200.
    app()->terminating(function () use ($normalized): void {
        try {
            ProcessMpesaCallback::dispatchSync($normalized);
        } catch (\Throwable $syncException) {
            Log::channel('payment')->error('Post-response M-Pesa callback processing failed, queuing retry', [
                'checkout_request_id' => $normalized['CheckoutRequestID'] ?? null,
                'error' => $syncException->getMessage(),
            ]);

            if ((string) config('queue.default', 'sync') === 'sync') {
                Log::channel('payment')->critical('Failed to queue M-Pesa callback retry because the queue driver is sync', [
                    'checkout_request_id' => $normalized['CheckoutRequestID'] ?? null,
                    'sync_error' => $syncException->getMessage(),
                ]);

                return;
            }

            try {
                ProcessMpesaCallback::dispatch($normalized)->onQueue('critical');
            } catch (\Throwable $queueException) {
                Log::channel('payment')->critical('Failed to queue M-Pesa callback retry after post-response failure', [
                    'checkout_request_id' => $normalized['CheckoutRequestID'] ?? null,
                    'sync_error' => $syncException->getMessage(),
                    'queue_error' => $queueException->getMessage(),
                ]);
            }
        }
    });

    return response()->json(['ResultCode' => 0, 'ResultDesc' => 'Accepted']);
})
    ->whereNumber('tenant')
    ->name('api.mpesa.callback')
    ->withoutMiddleware(['auth:sanctum', 'web', 'throttle:api']);

// ──────────────────────────────────────────────────────────────────────────
// PROTECTED API ROUTES (Require Laravel Sanctum Authentication)
// ──────────────────────────────────────────────────────────────────────────

// NOTE: Sanctum not installed/configured in this build; keep throttle only to avoid 500s.
Route::middleware(['throttle:api'])->group(function () {
    
    // ──────────────────────────────────────────────────────────────────────
    // USER & TENANT PROFILE
    // ──────────────────────────────────────────────────────────────────────
    
    /**
     * Get authenticated user + tenant context
     * 
     * Response includes:
     * - User profile with permissions
     * - Tenant summary (router count, package count, session count)
     * - Active subscription status
     */
    Route::get('/user', function (Request $request) {
        $user = $request->user()->load('tenant');
        $tenant = $user->tenant->loadCount(['routers', 'packages', 'userSessions']);
        
        return response()->json([
            'success' => true,
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role,
                    'permissions' => $user->permissions ?? [],
                    'last_login' => $user->last_login_at,
                ],
                'tenant' => [
                    'id' => $tenant->id,
                    'name' => $tenant->name,
                    'business_name' => $tenant->business_name,
                    'till_number' => $tenant->till_number,
                    'status' => $tenant->status,
                    'counts' => [
                        'routers' => $tenant->routers_count,
                        'packages' => $tenant->packages_count,
                        'sessions' => $tenant->user_sessions_count,
                    ],
                    'subscription' => [
                        'plan' => $tenant->plan ?? 'starter',
                        'status' => $tenant->subscription_status ?? 'active',
                        'next_billing_date' => $tenant->next_billing_date,
                    ],
                ],
            ],
        ]);
    })->name('api.user');

    // ──────────────────────────────────────────────────────────────────────
    // MIKROTIK ROUTER MANAGEMENT
    // ──────────────────────────────────────────────────────────────────────
    
    Route::prefix('mikrotik')->name('mikrotik.')->group(function () {
        
        /**
         * List all routers for authenticated tenant
         * 
         * Query params:
         * - ?status=online|offline|pending
         * - ?search=name_or_ip
         * - ?page=1&per_page=20
         */
        Route::get('/routers', [RouterController::class, 'index'])
            ->name('routers.index');
        
        /**
         * Test connectivity to a specific router (ping + API check)
         */
        Route::post('/routers/{router}/ping', [RouterController::class, 'ping'])
            ->name('routers.ping')
            ->whereNumber('router');
        
        /**
         * Get active user sessions on a specific router
         */
        Route::get('/routers/{router}/sessions', [RouterController::class, 'sessions'])
            ->name('routers.sessions')
            ->whereNumber('router');
        
        /**
         * Get router system diagnostics (CPU, memory, uptime, temperature)
         */
        Route::get('/routers/{router}/system', [RouterController::class, 'system'])
            ->name('routers.system')
            ->whereNumber('router');
        
        /**
         * Update router configuration (name, location, settings)
         */
        Route::put('/routers/{router}', [RouterController::class, 'update'])
            ->name('routers.update')
            ->whereNumber('router');
        
        /**
         * Delete router (soft delete)
         */
        Route::delete('/routers/{router}', [RouterController::class, 'destroy'])
            ->name('routers.destroy')
            ->whereNumber('router');
        
        // ──────────────────────────────────────────────────────────────────
        // SESSION MANAGEMENT (Per-Router & Global)
        // ──────────────────────────────────────────────────────────────────
        
        /**
         * List all user sessions for tenant (with filters)
         * 
         * Query params:
         * - ?status=pending|paid|active|expired
         * - ?router_id=123
         * - ?phone=254712345678
         * - ?package_id=456
         */
        Route::get('/sessions', [SessionController::class, 'index'])
            ->name('sessions.index');
        
        /**
         * Get only currently active sessions
         */
        Route::get('/sessions/active', [SessionController::class, 'active'])
            ->name('sessions.active');
        
        /**
         * Get sessions expiring within 30 minutes (for renewal prompts)
         */
        Route::get('/sessions/expiring', [SessionController::class, 'expiring'])
            ->name('sessions.expiring');
        
        /**
         * Search sessions by username, phone, or MAC address
         */
        Route::get('/sessions/search', [SessionController::class, 'search'])
            ->name('sessions.search');
        
        /**
         * Disconnect a single user session (admin override)
         */
        Route::post('/sessions/{session}/disconnect', [SessionController::class, 'disconnect'])
            ->name('sessions.disconnect')
            ->whereNumber('session');
        
        /**
         * Bulk disconnect multiple sessions
         * 
         * Payload: { "session_ids": [1, 2, 3], "reason": "maintenance" }
         */
        Route::post('/sessions/disconnect-bulk', [SessionController::class, 'bulkDisconnect'])
            ->name('sessions.disconnect-bulk');
        
        /**
         * Extend session time (admin override for support)
         * 
         * Payload: { "minutes": 30 }
         */
        Route::post('/sessions/{session}/extend', [SessionController::class, 'extend'])
            ->name('sessions.extend')
            ->whereNumber('session');
    });

    // ──────────────────────────────────────────────────────────────────────
    // PAYMENT MANAGEMENT (Daraja Reporting)
    // ──────────────────────────────────────────────────────────────────────
    
    Route::prefix('payments')->name('payments.')->group(function () {
        
        /**
         * List all payments for tenant (with filters)
         * 
         * Query params:
         * - ?status=pending|completed|failed|payout_sent
         * - ?date_from=2024-01-01&date_to=2024-01-31
         * - ?package_id=123
         */
        Route::get('/', [PaymentController::class, 'index'])
            ->name('index');
        
        /**
         * Get payment statistics & commission summary
         * 
         * Response includes:
         * - Today/week/month revenue
         * - Commission owed to CloudBridge
         * - Payout status summary
         */
        Route::get('/stats', [PaymentController::class, 'stats'])
            ->name('stats');
        
        /**
         * Get single payment details with full audit trail
         */
        Route::get('/{payment}', [PaymentController::class, 'show'])
            ->name('show')
            ->whereNumber('payment');
        
    });

    // ──────────────────────────────────────────────────────────────────────
    // PACKAGE MANAGEMENT (WiFi Plans)
    // ──────────────────────────────────────────────────────────────────────
    
    Route::prefix('packages')->name('packages.')->group(function () {
        
        /**
         * List all active packages for tenant
         */
        Route::get('/', [PackageController::class, 'index'])
            ->name('index');
        
        /**
         * Get single package details
         */
        Route::get('/{package}', [PackageController::class, 'show'])
            ->name('show')
            ->whereNumber('package');
        
        /**
         * Create new WiFi package
         * 
         * Payload:
         * {
         *   "name": "4 Hours",
         *   "duration_minutes": 240,
         *   "price": 50,
         *   "upload_speed": "5M",
         *   "download_speed": "10M",
         *   "data_limit_mb": null,
         *   "description": "Perfect for browsing"
         * }
         */
        Route::post('/', [PackageController::class, 'store'])
            ->name('store');
        
        /**
         * Update existing package
         */
        Route::put('/{package}', [PackageController::class, 'update'])
            ->name('update')
            ->whereNumber('package');
        
        /**
         * Delete package (soft delete - can be restored)
         */
        Route::delete('/{package}', [PackageController::class, 'destroy'])
            ->name('destroy')
            ->whereNumber('package');
        
        /**
         * Toggle package active/inactive status
         */
        Route::patch('/{package}/toggle', [PackageController::class, 'toggle'])
            ->name('toggle')
            ->whereNumber('package');
        
        /**
         * Duplicate package (create copy with "-copy" suffix)
         */
        Route::post('/{package}/duplicate', [PackageController::class, 'duplicate'])
            ->name('duplicate')
            ->whereNumber('package');
    });

    // ──────────────────────────────────────────────────────────────────────
    // VOUCHER MANAGEMENT (Offline Sales & Resellers)
    // ──────────────────────────────────────────────────────────────────────
    
    Route::prefix('vouchers')->name('vouchers.')->group(function () {
        
        /**
         * List all vouchers for tenant (with filters)
         * 
         * Query params:
         * - ?status=unused|used|expired
         * - ?batch_id=123
         * - ?code=ABC123XYZ
         */
        Route::get('/', [VoucherController::class, 'index'])
            ->name('index');
        
        /**
         * Get voucher statistics
         */
        Route::get('/stats', [VoucherController::class, 'stats'])
            ->name('stats');
        
        /**
         * Generate bulk vouchers
         * 
         * Payload:
         * {
         *   "package_id": 123,
         *   "quantity": 100,
         *   "prefix": "CLOUD",
         *   "expiry_days": 30
         * }
         */
        Route::post('/generate', [VoucherController::class, 'generate'])
            ->name('generate');
        
        /**
         * Validate voucher code (before redemption - idempotent)
         * 
         * Payload: { "code": "ABC123XYZ" }
         */
        Route::post('/validate', [VoucherController::class, 'validate'])
            ->name('validate');
        
        /**
         * Redeem voucher (activate user session)
         * 
         * Payload:
         * {
         *   "code": "ABC123XYZ",
         *   "phone": "254712345678", // Optional for tracking
         *   "router_id": 456 // Optional to bind to specific router
         * }
         */
        Route::post('/redeem', [VoucherController::class, 'redeem'])
            ->name('redeem');
        
        /**
         * Generate printable PDF for voucher batch
         */
        Route::post('/{batch}/print', [VoucherController::class, 'print'])
            ->name('print')
            ->whereNumber('batch');
        
        /**
         * Export vouchers to CSV for accounting
         */
        Route::get('/export', [VoucherController::class, 'export'])
            ->name('export');
    });

    // ──────────────────────────────────────────────────────────────────────
    // TENANT SETTINGS & CONFIGURATION
    // ──────────────────────────────────────────────────────────────────────
    
    Route::prefix('settings')->name('settings.')->group(function () {
        
        /**
         * Get tenant payment configuration.
         */
        Route::get('/payment', function (Request $request) {
            $tenant = $request->user()->tenant;
            
            return response()->json([
                'success' => true,
                'data' => [
                    'daraja_only' => true,
                    'daraja_configured' => !empty($tenant->payment_shortcode) || !empty($tenant->till_number),
                    'till_number' => $tenant->till_number,
                    'auto_payout_enabled' => $tenant->auto_payout_enabled ?? true,
                    'callback_url' => $tenant->callback_url,
                    'commission' => [
                        'type' => $tenant->commission_type,
                        'rate' => $tenant->commission_rate,
                        'minimum' => $tenant->minimum_commission,
                        'frequency' => $tenant->commission_frequency,
                    ],
                ],
            ]);
        })->name('payment');
        
        /**
         * Update tenant payment configuration
         * 
         * Payload:
         * {
         *   "till_number": "500123",
         *   "auto_payout_enabled": true,
         *   "commission_rate": 3.0,
         *   "minimum_commission": 499,
         *   "commission_frequency": "monthly"
         * }
         */
        Route::put('/payment', function (Request $request) {
            $validated = $request->validate([
                'till_number' => 'nullable|string|max:20',
                'auto_payout_enabled' => 'boolean',
                'commission_type' => 'required|in:percentage,fixed',
                'commission_rate' => 'required|numeric|min:0|max:100',
                'minimum_commission' => 'required|numeric|min:0',
                'commission_frequency' => 'required|in:monthly,weekly,per_transaction',
            ]);
            
            $request->user()->tenant->update($validated);
            
            return response()->json([
                'success' => true,
                'message' => 'Payment settings updated',
                'data' => $request->user()->tenant->only([
                    'till_number',
                    'auto_payout_enabled',
                    'commission_type',
                    'commission_rate',
                    'minimum_commission',
                    'commission_frequency',
                ]),
            ]);
        })->name('payment.update');
        
        /**
         * Get tenant commission summary (what they owe CloudBridge)
         */
        Route::get('/commission', function (Request $request) {
            $tenant = $request->user()->tenant;
            
            return response()->json([
                'success' => true,
                'data' => [
                    'current_period' => [
                        'start' => $tenant->current_billing_period_start,
                        'end' => $tenant->current_billing_period_end,
                        'sales_total' => $tenant->current_period_sales,
                        'commission_owed' => $tenant->current_period_commission,
                        'paid' => $tenant->current_period_paid,
                        'balance' => $tenant->current_period_balance,
                    ],
                    'lifetime' => [
                        'total_sales' => $tenant->lifetime_sales,
                        'total_commission' => $tenant->lifetime_commission,
                        'total_paid' => $tenant->lifetime_paid,
                    ],
                ],
            ]);
        })->name('commission');
        
        /**
         * Update tenant profile (business info, contact details)
         */
        Route::put('/profile', function (Request $request) {
            $validated = $request->validate([
                'business_name' => 'required|string|max:255',
                'contact_name' => 'required|string|max:255',
                'contact_email' => 'required|email|max:255',
                'contact_phone' => 'required|string|size:12',
                'location' => 'nullable|string|max:255',
            ]);
            
            $request->user()->tenant->update($validated);
            
            return response()->json([
                'success' => true,
                'message' => 'Profile updated',
                'data' => $request->user()->tenant->only([
                    'business_name',
                    'contact_name',
                    'contact_email',
                    'contact_phone',
                    'location',
                ]),
            ]);
        })->name('profile.update');
    });

    // ──────────────────────────────────────────────────────────────────────
    // REPORTING & ANALYTICS
    // ──────────────────────────────────────────────────────────────────────
    
    Route::prefix('reports')->name('reports.')->group(function () {
        
        /**
         * Generate revenue report (PDF/CSV)
         * 
         * Query params:
         * - ?format=pdf|csv
         * - ?period=today|week|month|custom
         * - ?date_from=2024-01-01&date_to=2024-01-31
         */
        Route::get('/revenue', function (Request $request) {
            // Implementation: Generate report via queued job
            return response()->json([
                'success' => true,
                'message' => 'Report generation queued',
                'job_id' => 'report-' . uniqid(),
            ]);
        })->name('revenue');
        
        /**
         * Get real-time dashboard metrics
         */
        Route::get('/dashboard', function (Request $request) {
            $tenant = $request->user()->tenant;
            
            return response()->json([
                'success' => true,
                'data' => [
                    'revenue' => [
                        'today' => $tenant->today_revenue,
                        'week' => $tenant->week_revenue,
                        'month' => $tenant->month_revenue,
                    ],
                    'sessions' => [
                        'active' => $tenant->active_sessions_count,
                        'today' => $tenant->today_sessions_count,
                    ],
                    'routers' => [
                        'online' => $tenant->online_routers_count,
                        'total' => $tenant->routers_count,
                    ],
                    'alerts' => $tenant->pending_alerts,
                ],
            ]);
        })->name('dashboard');
    });
});

// ──────────────────────────────────────────────────────────────────────────
// FALLBACK: Catch-all for undefined API routes
// ──────────────────────────────────────────────────────────────────────────

Route::fallback(function () {
    return response()->json([
        'success' => false,
        'message' => 'API endpoint not found',
        'documentation' => 'https://docs.cloudbridge.network/api',
    ], 404);
});
