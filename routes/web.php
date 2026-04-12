<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Admin\AdminPageController;
use App\Models\Router;
use App\Models\Package;
use App\Models\Payment;
use App\Models\UserSession;
use App\Models\Tenant;
use App\Services\MikroTik\MikroTikService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/*
|--------------------------------------------------------------------------
| Web Routes - CloudBridge Networks WiFi SaaS
|--------------------------------------------------------------------------
|
| Frontend-first development routes with mock authentication.
| Replace mock auth with real Laravel Sanctum/Jetstream when ready.
|
*/

// ============================================================================
// PUBLIC ROUTES
// ============================================================================

// Root redirect to login
Route::get('/', fn() => redirect()->route('login'));

// ----------------------------------------------------------------------------
// Authentication (Admin only)
// ----------------------------------------------------------------------------
Route::get('/login', function () {
    if (Auth::check() && in_array(Auth::user()?->role, ['super_admin', 'tenant_admin'], true)) {
        return redirect()->route('admin.dashboard');
    }

    return view('admin.auth.login');
})->name('login');

Route::post('/login', function (Request $request) {
    $credentials = $request->validate([
        'email' => ['required', 'email'],
        'password' => ['required', 'string'],
    ]);

    if (Auth::attempt($credentials, (bool) $request->boolean('remember'))) {
        $request->session()->regenerate();

        $user = Auth::user();
        $isAdmin = in_array($user?->role, ['super_admin', 'tenant_admin'], true);
        $isActive = (bool) ($user?->is_active ?? true);

        if (!$isAdmin || !$isActive) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return back()->with('error', 'Access denied. Admin account required.')->onlyInput('email');
        }

        if (method_exists($user, 'recordLogin')) {
            $user->recordLogin($request);
        }

        return redirect()->route('admin.dashboard');
    }

    return back()->with('error', 'Invalid email or password.')->onlyInput('email');
})->name('login.post');

Route::post('/logout', function (Request $request) {
    Auth::logout();
    $request->session()->invalidate();
    $request->session()->regenerateToken();

    return redirect()->route('login');
})->name('logout');

// ----------------------------------------------------------------------------
// Customer Portal Routes (Public - Legacy Captive Portal)
// ----------------------------------------------------------------------------
Route::prefix('portal')->name('portal.')->group(function () {
    Route::get('/', fn() => view('portal.home'))->name('home');
    Route::get('/package/{package}', fn($package) => view('portal.payment', ['package' => $package]))->name('package.show');
    Route::post('/payment/initiate', fn() => view('portal.payment_status'))->name('payment.initiate');
    Route::get('/payment/status/{checkoutId}', fn($checkoutId) => view('portal.payment_status'))->name('payment.status');
    Route::get('/payment/success/{payment?}', fn($payment = null) => view('portal.success', ['payment' => $payment]))->name('payment.success');
    Route::get('/voucher/redeem', fn() => view('portal.voucher'))->name('voucher.redeem');
});

// ----------------------------------------------------------------------------
// Captive Portal Routes (Public - New WiFi Payment Flow)
// No authentication required - for users connecting to WiFi
// ----------------------------------------------------------------------------
Route::prefix('wifi')->name('wifi.')->group(function () {
    // Package selection and payment initiation
    Route::get('/', [\App\Http\Controllers\CaptivePortalController::class, 'packages'])->name('packages');
    Route::post('/pay', [\App\Http\Controllers\CaptivePortalController::class, 'pay'])->name('pay');
    
    // Payment and session status
    Route::get('/status/{phone}', [\App\Http\Controllers\CaptivePortalController::class, 'status'])->name('status');
    Route::get('/status/{phone}/check', [\App\Http\Controllers\CaptivePortalController::class, 'checkStatus'])->name('status.check');
    
    // Reconnection flows
    Route::get('/reconnect', fn() => view('captive.reconnect'))->name('reconnect.form');
    Route::post('/reconnect', [\App\Http\Controllers\CaptivePortalController::class, 'reconnect'])->name('reconnect');
    
    // Session extension
    Route::post('/extend', [\App\Http\Controllers\CaptivePortalController::class, 'extend'])->name('extend');
});

// Compatibility redirect: some users access captive via /admin/wifi by mistake.
Route::get('/admin/wifi', function () {
    $tenantId = (int) (Auth::user()?->tenant_id ?? 0);
    if ($tenantId > 0) {
        return redirect()->route('wifi.packages', ['tenant_id' => $tenantId]);
    }

    return redirect()->route('wifi.packages');
});

// Legacy M-Pesa Webhook (Public - kept for older callback URLs / local testing)
Route::post('/api/mpesa/callback', function (Request $request) {
    \Log::info('M-Pesa Callback Received', $request->all());
    return response()->json(['ResultCode' => 0, 'ResultDesc' => 'Accepted']);
})->name('api.mpesa.callback.legacy');

// ============================================================================
// ADMIN ROUTES (Protected)
// ============================================================================

// Protected admin routes group
Route::middleware('admin.auth')->prefix('admin')->name('admin.')->group(function () {
    
    // ----------------------------------------------------------------------------
    // Dashboard
    // ----------------------------------------------------------------------------
    Route::get('/dashboard', [AdminPageController::class, 'dashboard'])->name('dashboard');
    
    // ----------------------------------------------------------------------------
    // Routers Management (MikroTik)
    // ----------------------------------------------------------------------------
    Route::prefix('routers')->name('routers.')->group(function () {
        Route::get('/', [AdminPageController::class, 'routers'])->name('index');
        
        Route::get('/create', [AdminPageController::class, 'routersCreate'])->name('create');
        
        Route::get('/{router}', [AdminPageController::class, 'routersShow'])->name('show');
        
        Route::get('/{router}/edit', [AdminPageController::class, 'routersEdit'])->name('edit');
    });
    
    // ----------------------------------------------------------------------------
    // Packages Management (WiFi Plans)
    // ----------------------------------------------------------------------------
    Route::prefix('packages')->name('packages.')->group(function () {
        Route::get('/', [AdminPageController::class, 'packages'])->name('index');
        
        Route::get('/create', [AdminPageController::class, 'packagesCreate'])->name('create');
        
        Route::get('/{package}', [AdminPageController::class, 'packagesShow'])->name('show');
        
        Route::get('/{package}/edit', [AdminPageController::class, 'packagesEdit'])->name('edit');
    });
    
    // ----------------------------------------------------------------------------
    // Vouchers Management (Offline Sales)
    // ----------------------------------------------------------------------------
    Route::prefix('vouchers')->name('vouchers.')->group(function () {
        Route::get('/', [AdminPageController::class, 'vouchersIndex'])->name('index');
        
        Route::get('/generate', [AdminPageController::class, 'vouchersGenerate'])->name('generate');
        
        Route::get('/print', [AdminPageController::class, 'vouchersPrint'])->name('print');
    });
    
    // ----------------------------------------------------------------------------
    // Payments & Reports
    // ----------------------------------------------------------------------------
    Route::prefix('payments')->name('payments.')->group(function () {
        Route::get('/', [AdminPageController::class, 'payments'])->name('index');
        
        Route::get('/export', [AdminPageController::class, 'paymentsExport'])->name('export');
    });
    
    // ----------------------------------------------------------------------------
    // Clients Monitoring (Live Sessions)
    // ----------------------------------------------------------------------------
    Route::prefix('clients')->name('clients.')->group(function () {
        Route::get('/hotspot', [AdminPageController::class, 'clientsHotspot'])->name('hotspot');
        
        Route::get('/pppoe', [AdminPageController::class, 'clientsPppoe'])->name('pppoe');
        
        Route::get('/customers', [AdminPageController::class, 'clientsCustomers'])->name('customers');
    });
    
    // ----------------------------------------------------------------------------
    // Settings & Configuration
    // ----------------------------------------------------------------------------
    Route::get('/settings', [AdminPageController::class, 'settingsIndex'])->name('settings');
    
    Route::prefix('settings')->name('settings.')->group(function () {
        Route::get('/mpesa', [AdminPageController::class, 'settingsMpesa'])->name('mpesa');
        
        Route::get('/sms', [AdminPageController::class, 'settingsSms'])->name('sms');
        
        Route::get('/branding', [AdminPageController::class, 'settingsBranding'])->name('branding');
        
        Route::get('/account', [AdminPageController::class, 'settingsAccount'])->name('account');
    });
    
    // ----------------------------------------------------------------------------
    // API Routes for AJAX (Mock Responses for Frontend)
    // ----------------------------------------------------------------------------
    Route::prefix('api')->name('api.')->group(function () {

        $resolveTenant = function () {
            $user = Auth::user();

            if (!$user) {
                return null;
            }

            if ($user->tenant_id) {
                return Tenant::find($user->tenant_id);
            }

            if (($user->role ?? null) === 'super_admin') {
                $requestedTenantId = (int) request()->input('tenant_id', request()->query('tenant_id', 0));
                if ($requestedTenantId > 0) {
                    return Tenant::query()->active()->find($requestedTenantId);
                }
                return null; // Super admin can view aggregate data when tenant is not specified.
            }

            return null;
        };

        $buildMikrotikCommands = function (array $settings): array {
            $radiusServer = trim((string) ($settings['radius_server'] ?? config('radius.server_ip', '127.0.0.1')));
            $radiusAuthPort = (int) ($settings['radius_port'] ?? config('radius.auth_port', 1812));
            $radiusAcctPort = (int) ($settings['radius_acct_port'] ?? config('radius.acct_port', 1813));
            $radiusSecret = trim((string) ($settings['radius_secret'] ?? config('radius.shared_secret', '')));

            $safeServer = $radiusServer !== '' ? $radiusServer : 'YOUR_RADIUS_SERVER_IP';
            $safeSecret = $radiusSecret !== '' ? $radiusSecret : 'YOUR_SHARED_SECRET';

            return [
                '/radius add service=hotspot,ppp address=' . $safeServer . ' protocol=udp authentication-port=' . max(1, $radiusAuthPort) . ' accounting-port=' . max(1, $radiusAcctPort) . ' secret=' . $safeSecret . ' timeout=300ms',
                '/ip hotspot profile set [find] use-radius=yes',
                '/ppp aaa set use-radius=yes accounting=yes interim-update=1m',
                '/radius incoming set accept=yes port=3799',
                '/radius monitor 0 once',
            ];
        };

        Route::get('/settings', function () use ($resolveTenant, $buildMikrotikCommands) {
            $tenant = $resolveTenant();

            $settings = [];
            if ($tenant) {
                $tenantSettings = (array) ($tenant->settings ?? []);
                $settings = (array) ($tenantSettings['admin_settings'] ?? []);
            } else {
                $settings = (array) cache()->get('admin_settings_global', []);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'settings' => $settings,
                    'mikrotik_commands' => $buildMikrotikCommands($settings),
                ],
            ]);
        })->name('settings.show');

        Route::post('/settings', function (Request $request) use ($resolveTenant, $buildMikrotikCommands) {
            $settings = (array) $request->input('settings', []);

            $tenant = $resolveTenant();
            if ($tenant) {
                $tenantSettings = (array) ($tenant->settings ?? []);
                $tenantSettings['admin_settings'] = $settings;
                $tenant->settings = $tenantSettings;
                $tenant->save();
            } else {
                cache()->forever('admin_settings_global', $settings);
            }

            return response()->json([
                'success' => true,
                'message' => 'Settings saved successfully',
                'data' => [
                    'mikrotik_commands' => $buildMikrotikCommands($settings),
                ],
            ]);
        })->name('settings.save');

        Route::post('/settings/mikrotik/test', function (Request $request, MikroTikService $mikroTikService) use ($resolveTenant, $buildMikrotikCommands) {
            $tenant = $resolveTenant();

            $settings = (array) $request->input('settings', []);
            $commands = $buildMikrotikCommands($settings);

            $router = Router::query()
                ->when($tenant, fn ($query) => $query->where('tenant_id', $tenant->id))
                ->orderByDesc('status')
                ->orderBy('id')
                ->first();

            if (!$router) {
                return response()->json([
                    'success' => false,
                    'message' => 'No router found for this tenant. Add a router first.',
                    'commands' => $commands,
                ], 404);
            }

            $isOnline = $mikroTikService->pingRouter($router);

            if (!$isOnline) {
                return response()->json([
                    'success' => false,
                    'message' => 'Router is offline or unreachable',
                    'router' => [
                        'id' => $router->id,
                        'name' => $router->name,
                        'ip_address' => $router->ip_address,
                    ],
                    'commands' => $commands,
                ], 503);
            }

            $systemInfo = $mikroTikService->getRouterSystemInfo($router);

            return response()->json([
                'success' => true,
                'message' => 'MikroTik API connection successful',
                'router' => [
                    'id' => $router->id,
                    'name' => $router->name,
                    'ip_address' => $router->ip_address,
                    'status' => $router->status,
                ],
                'data' => [
                    'cpu' => $systemInfo['cpu_load'] ?? null,
                    'memory' => $systemInfo['memory_usage'] ?? null,
                    'uptime' => $systemInfo['uptime'] ?? null,
                    'version' => $systemInfo['version'] ?? null,
                ],
                'commands' => $commands,
            ]);
        })->name('settings.mikrotik.test');
        
        // Router Status & Testing
        Route::get('/routers/status', function (Request $request, MikroTikService $mikroTikService) use ($resolveTenant) {
            $tenant = $resolveTenant();
            $live = $request->boolean('live', true);

            $query = Router::query();
            if ($tenant) {
                $query->where('tenant_id', $tenant->id);
            }

            $routers = $query
                ->orderBy('name')
                ->limit(100)
                ->get()
                ->map(function (Router $router) use ($live, $mikroTikService) {
                    $status = (string) ($router->status ?? Router::STATUS_OFFLINE);
                    $cpu = $router->cpu_usage;
                    $memory = $router->memory_usage;

                    if ($live) {
                        $isOnline = $mikroTikService->pingRouter($router);
                        $status = $isOnline ? Router::STATUS_ONLINE : Router::STATUS_OFFLINE;

                        if ($isOnline) {
                            $systemInfo = $mikroTikService->getRouterSystemInfo($router);
                            $cpu = isset($systemInfo['cpu_load']) ? (int) $systemInfo['cpu_load'] : $cpu;
                            $memory = isset($systemInfo['memory_usage']) ? (int) $systemInfo['memory_usage'] : $memory;

                            if ((int) $cpu >= Router::HEALTHY_CPU_THRESHOLD || (int) $memory >= Router::HEALTHY_MEMORY_THRESHOLD) {
                                $router->markWarning('High resource usage');
                                $status = Router::STATUS_WARNING;
                            }
                        }
                    }

                    return [
                        'id' => $router->id,
                        'name' => $router->name,
                        'ip' => $router->ip_address,
                        'status' => $status,
                        'users' => (int) ($router->active_sessions ?? 0),
                        'cpu' => $cpu,
                        'memory' => $memory,
                        'last_seen_at' => $router->last_seen_at?->toIso8601String(),
                    ];
                });

            $online = $routers->filter(fn ($router) => in_array($router['status'], [Router::STATUS_ONLINE, Router::STATUS_WARNING], true))->count();
            $offline = $routers->where('status', Router::STATUS_OFFLINE)->count();

            return response()->json([
                'success' => true,
                'data' => $routers,
                'summary' => [
                    'online' => $online,
                    'offline' => $offline,
                    'total' => $routers->count(),
                    'live' => $live,
                ],
            ]);
        })->name('routers.status');
        
        Route::post('/routers/test', function (Request $request, MikroTikService $mikroTikService) use ($resolveTenant) {
            $tenant = $resolveTenant();

            $router = Router::query()
                ->when($tenant, fn ($q) => $q->where('tenant_id', $tenant->id))
                ->find($request->input('router_id'));

            if (!$router) {
                return response()->json([
                    'success' => false,
                    'message' => 'Router not found',
                ], 404);
            }

            $isOnline = $mikroTikService->pingRouter($router);

            if (!$isOnline) {
                return response()->json([
                    'success' => false,
                    'message' => 'Router is offline or unreachable',
                    'router' => $router->id,
                ], 503);
            }

            $systemInfo = $mikroTikService->getRouterSystemInfo($router);

            return response()->json([
                'success' => true,
                'message' => 'Router is online',
                'router' => $router->id,
                'data' => [
                    'status' => $router->status,
                    'cpu' => $systemInfo['cpu_load'] ?? $router->cpu_usage,
                    'memory' => $systemInfo['memory_usage'] ?? $router->memory_usage,
                    'uptime' => $systemInfo['uptime'] ?? null,
                ],
            ]);
        })->name('routers.test');
        
        // Package Stats
        Route::get('/packages/stats', function () use ($resolveTenant) {
            $tenant = $resolveTenant();

            $packages = Package::query()->when($tenant, fn ($q) => $q->where('tenant_id', $tenant->id));
            $payments = Payment::query()->when($tenant, fn ($q) => $q->where('tenant_id', $tenant->id));

            return response()->json([
                'total' => (clone $packages)->count(),
                'active' => (clone $packages)->where('is_active', true)->count(),
                'revenue_today' => (float) (clone $payments)
                    ->whereDate('created_at', now()->toDateString())
                    ->whereIn('status', ['completed', 'confirmed'])
                    ->sum('amount'),
                'revenue_week' => (float) (clone $payments)
                    ->where('created_at', '>=', now()->startOfWeek())
                    ->whereIn('status', ['completed', 'confirmed'])
                    ->sum('amount'),
            ]);
        })->name('packages.stats');

        Route::get('/dashboard/summary', function () use ($resolveTenant) {
            $tenant = $resolveTenant();

            $payments = Payment::query()->when($tenant, fn ($query) => $query->where('tenant_id', $tenant->id));
            $packages = Package::query()->when($tenant, fn ($query) => $query->where('tenant_id', $tenant->id));
            $routers = Router::query()->when($tenant, fn ($query) => $query->where('tenant_id', $tenant->id));
            $sessions = UserSession::query()->when($tenant, fn ($query) => $query->where('tenant_id', $tenant->id));

            $successStatuses = ['completed', 'confirmed'];
            $weeklyRevenue = [];
            for ($i = 6; $i >= 0; $i--) {
                $day = now()->subDays($i);
                $weeklyRevenue[] = [
                    'date' => $day->toDateString(),
                    'label' => $day->format('D'),
                    'amount' => (float) (clone $payments)
                        ->whereDate('created_at', $day->toDateString())
                        ->whereIn('status', $successStatuses)
                        ->sum('amount'),
                ];
            }

            $thisWeekTx = (clone $payments)->where('created_at', '>=', now()->startOfWeek());
            $successThisWeek = (clone $thisWeekTx)->whereIn('status', $successStatuses)->count();
            $totalThisWeek = (clone $thisWeekTx)->count();

            return response()->json([
                'success' => true,
                'data' => [
                    'revenue_today' => (float) (clone $payments)
                        ->whereDate('created_at', now()->toDateString())
                        ->whereIn('status', $successStatuses)
                        ->sum('amount'),
                    'revenue_week' => (float) (clone $payments)
                        ->where('created_at', '>=', now()->startOfWeek())
                        ->whereIn('status', $successStatuses)
                        ->sum('amount'),
                    'active_sessions' => (clone $sessions)->where('status', 'active')->count(),
                    'packages_total' => (clone $packages)->count(),
                    'routers_online' => (clone $routers)->where('status', 'online')->count(),
                    'routers_total' => (clone $routers)->count(),
                    'transactions_week' => $totalThisWeek,
                    'success_rate_week' => $totalThisWeek > 0
                        ? round(($successThisWeek / $totalThisWeek) * 100)
                        : 0,
                    'weekly_revenue' => $weeklyRevenue,
                ],
            ]);
        })->name('dashboard.summary');

        Route::get('/packages', function () use ($resolveTenant) {
            $tenant = $resolveTenant();

            $packages = Package::query()
                ->when($tenant, fn ($query) => $query->where('tenant_id', $tenant->id))
                ->orderBy('sort_order')
                ->orderBy('price')
                ->limit(200)
                ->get()
                ->map(fn (Package $package) => [
                    'id' => $package->id,
                    'name' => $package->name,
                    'description' => $package->description,
                    'price' => (float) $package->price,
                    'duration_value' => $package->duration_value,
                    'duration_unit' => $package->duration_unit,
                    'download_limit_mbps' => $package->download_limit_mbps,
                    'upload_limit_mbps' => $package->upload_limit_mbps,
                    'mikrotik_profile_name' => $package->mikrotik_profile_name,
                    'is_active' => (bool) $package->is_active,
                    'total_sales' => (int) ($package->total_sales ?? 0),
                    'sort_order' => $package->sort_order,
                ]);

            return response()->json([
                'success' => true,
                'data' => $packages,
            ]);
        })->name('packages.index');

        Route::post('/packages', function (Request $request) use ($resolveTenant) {
            $tenant = $resolveTenant();

            if (!$tenant) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tenant not found',
                ], 404);
            }

            $validated = $request->validate([
                'name' => 'required|string|max:120',
                'description' => 'nullable|string|max:255',
                'price' => 'required|numeric|min:0',
                'duration_value' => 'required|integer|min:1|max:100000',
                'duration_unit' => 'required|in:minutes,hours,days,weeks,months',
                'download_limit_mbps' => 'nullable|integer|min:1|max:100000',
                'upload_limit_mbps' => 'nullable|integer|min:1|max:100000',
                'mikrotik_profile_name' => 'nullable|string|max:120',
                'is_active' => 'nullable|boolean',
            ]);

            $codeBase = strtoupper(preg_replace('/[^A-Z0-9]+/', '-', (string) $validated['name']));
            $codeBase = trim($codeBase, '-');
            if ($codeBase === '') {
                $codeBase = 'PKG';
            }

            $code = $codeBase;
            $attempt = 1;
            while (Package::query()->where('code', $code)->exists()) {
                $attempt++;
                $code = $codeBase . '-' . $attempt;
            }

            $sortOrder = (int) (Package::query()
                ->where('tenant_id', $tenant->id)
                ->max('sort_order') ?? 0) + 1;

            $package = Package::create([
                'tenant_id' => $tenant->id,
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null,
                'code' => $code,
                'price' => $validated['price'],
                'currency' => 'KES',
                'duration_value' => $validated['duration_value'],
                'duration_unit' => $validated['duration_unit'],
                'download_limit_mbps' => $validated['download_limit_mbps'] ?? null,
                'upload_limit_mbps' => $validated['upload_limit_mbps'] ?? null,
                'mikrotik_profile_name' => $validated['mikrotik_profile_name'] ?? null,
                'is_active' => (bool) ($validated['is_active'] ?? true),
                'sort_order' => $sortOrder,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Package created successfully',
                'data' => [
                    'id' => $package->id,
                    'name' => $package->name,
                ],
            ], 201);
        })->name('packages.create');

        Route::put('/packages/{package}', function (Request $request, Package $package) use ($resolveTenant) {
            $tenant = $resolveTenant();

            if (!$tenant || (int) $package->tenant_id !== (int) $tenant->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Package not found',
                ], 404);
            }

            $validated = $request->validate([
                'name' => 'required|string|max:120',
                'description' => 'nullable|string|max:255',
                'price' => 'required|numeric|min:0',
                'duration_value' => 'required|integer|min:1|max:100000',
                'duration_unit' => 'required|in:minutes,hours,days,weeks,months',
                'download_limit_mbps' => 'nullable|integer|min:1|max:100000',
                'upload_limit_mbps' => 'nullable|integer|min:1|max:100000',
                'mikrotik_profile_name' => 'nullable|string|max:120',
                'is_active' => 'nullable|boolean',
            ]);

            $package->update([
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null,
                'price' => $validated['price'],
                'duration_value' => $validated['duration_value'],
                'duration_unit' => $validated['duration_unit'],
                'download_limit_mbps' => $validated['download_limit_mbps'] ?? null,
                'upload_limit_mbps' => $validated['upload_limit_mbps'] ?? null,
                'mikrotik_profile_name' => $validated['mikrotik_profile_name'] ?? null,
                'is_active' => (bool) ($validated['is_active'] ?? false),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Package updated successfully',
            ]);
        })->name('packages.update');

        Route::patch('/packages/{package}/status', function (Request $request, Package $package) use ($resolveTenant) {
            $tenant = $resolveTenant();

            if (!$tenant || (int) $package->tenant_id !== (int) $tenant->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Package not found',
                ], 404);
            }

            $validated = $request->validate([
                'is_active' => 'required|boolean',
            ]);

            $package->update([
                'is_active' => (bool) $validated['is_active'],
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Package status updated',
            ]);
        })->name('packages.status');

        Route::delete('/packages/{package}', function (Package $package) use ($resolveTenant) {
            $tenant = $resolveTenant();

            if (!$tenant || (int) $package->tenant_id !== (int) $tenant->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Package not found',
                ], 404);
            }

            $package->delete();

            return response()->json([
                'success' => true,
                'message' => 'Package deleted successfully',
            ]);
        })->name('packages.delete');
        
        // Client Stats (Live)
        Route::get('/clients/stats', function () use ($resolveTenant) {
            $tenant = $resolveTenant();

            $mode = request()->query('mode');

            $sessions = UserSession::query()
                ->when($tenant, fn ($q) => $q->where('tenant_id', $tenant->id))
                ->when($mode === 'pppoe', fn ($q) => $q->where('username', 'like', 'pppoe%'))
                ->when($mode === 'hotspot', fn ($q) => $q->where(function ($inner) {
                    $inner->whereNull('username')->orWhere('username', 'not like', 'pppoe%');
                }));

            $activeSessions = (clone $sessions)->where('status', 'active')->count();
            $totalBytes = (int) (clone $sessions)->sum('bytes_total');
            $totalGb = round($totalBytes / (1024 * 1024 * 1024), 2);

            return response()->json([
                'hotspot_active' => $activeSessions,
                'pppoe_active' => 0,
                'total_bandwidth' => $totalGb . ' GB',
            ]);
        })->name('clients.stats');

        Route::get('/clients/sessions', function (Request $request) use ($resolveTenant) {
            $tenant = $resolveTenant();

            $limit = min(max((int) $request->integer('limit', 150), 1), 500);
            $status = $request->string('status')->toString();
            $search = trim($request->string('search')->toString());
            $mode = $request->string('mode')->toString();

            $sessions = UserSession::query()
                ->when($tenant, fn ($query) => $query->where('tenant_id', $tenant->id))
                ->when($mode === 'pppoe', fn ($query) => $query->where('username', 'like', 'pppoe%'))
                ->when($mode === 'hotspot', fn ($query) => $query->where(function ($inner) {
                    $inner->whereNull('username')->orWhere('username', 'not like', 'pppoe%');
                }))
                ->when($status !== '', fn ($query) => $query->where('status', $status))
                ->when($search !== '', function ($query) use ($search) {
                    $query->where(function ($inner) use ($search) {
                        $inner->where('username', 'like', "%{$search}%")
                            ->orWhere('phone', 'like', "%{$search}%")
                            ->orWhere('ip_address', 'like', "%{$search}%")
                            ->orWhere('mac_address', 'like', "%{$search}%");
                    });
                })
                ->with(['package', 'router'])
                ->latest('created_at')
                ->limit($limit)
                ->get()
                ->map(function (UserSession $session) {
                    return [
                        'id' => $session->id,
                        'username' => $session->username,
                        'phone' => $session->phone,
                        'status' => $session->status,
                        'ip_address' => $session->ip_address,
                        'mac_address' => $session->mac_address,
                        'router' => $session->router?->name,
                        'package' => $session->package?->name,
                        'expires_at' => $session->expires_at?->toIso8601String(),
                        'started_at' => $session->started_at?->toIso8601String(),
                        'bytes_total' => (int) ($session->bytes_total ?? 0),
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => $sessions,
            ]);
        })->name('clients.sessions');
        
        // Disconnect Client
        Route::post('/clients/disconnect', function (Request $request) use ($resolveTenant) {
            $tenant = $resolveTenant();

            $session = UserSession::query()
                ->when($tenant, fn ($q) => $q->where('tenant_id', $tenant->id))
                ->where('username', $request->input('username'))
                ->where('status', 'active')
                ->first();

            if (!$session) {
                return response()->json([
                    'success' => false,
                    'message' => 'Active session not found',
                ], 404);
            }

            $session->update([
                'status' => 'terminated',
                'terminated_at' => now(),
                'termination_reason' => 'admin_disconnect',
            ]);

            return response()->json([
                'success' => true,
                'message' => "Client {$request->input('username')} disconnected",
                'router' => $request->input('router_id'),
            ]);
        })->name('clients.disconnect');

        // Payments Data
        Route::get('/payments', function (Request $request) use ($resolveTenant) {
            $tenant = $resolveTenant();

            $limit = min(max((int) $request->integer('limit', 100), 1), 500);
            $status = $request->string('status')->toString();

            $payments = Payment::query()
                ->when($tenant, fn ($query) => $query->where('tenant_id', $tenant->id))
                ->when($status !== '', fn ($query) => $query->where('status', $status))
                ->with('package')
                ->latest('created_at')
                ->limit($limit)
                ->get()
                ->map(function (Payment $payment) {
                    return [
                        'id' => $payment->id,
                        'phone' => $payment->phone,
                        'package_name' => $payment->package_name,
                        'package_id' => $payment->package_id,
                        'amount' => (float) $payment->amount,
                        'currency' => $payment->currency,
                        'status' => $payment->status,
                        'reference' => $payment->mpesa_receipt_number ?: $payment->mpesa_checkout_request_id,
                        'created_at' => $payment->created_at?->toIso8601String(),
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => $payments,
            ]);
        })->name('payments.index');

        Route::get('/payments/stats', function () use ($resolveTenant) {
            $tenant = $resolveTenant();

            $payments = Payment::query()->when($tenant, fn ($query) => $query->where('tenant_id', $tenant->id));
            $successStatuses = ['completed', 'confirmed'];

            $daily = [];
            for ($i = 6; $i >= 0; $i--) {
                $day = now()->subDays($i);
                $daily[] = [
                    'date' => $day->toDateString(),
                    'label' => $day->format('D'),
                    'amount' => (float) (clone $payments)
                        ->whereDate('created_at', $day->toDateString())
                        ->whereIn('status', $successStatuses)
                        ->sum('amount'),
                ];
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'revenue_total' => (float) (clone $payments)
                        ->whereIn('status', $successStatuses)
                        ->sum('amount'),
                    'revenue_today' => (float) (clone $payments)
                        ->whereDate('created_at', now()->toDateString())
                        ->whereIn('status', $successStatuses)
                        ->sum('amount'),
                    'pending' => (clone $payments)->where('status', 'pending')->count(),
                    'failed' => (clone $payments)->where('status', 'failed')->count(),
                    'daily_revenue' => $daily,
                ],
            ]);
        })->name('payments.stats');

        // Vouchers Data
        Route::post('/vouchers/generate', function (Request $request) use ($resolveTenant) {
            $tenant = $resolveTenant();

            if (!$tenant) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tenant not found',
                ], 404);
            }

            $validated = $request->validate([
                'package_id' => 'required|integer|exists:packages,id',
                'quantity' => 'required|integer|min:1|max:1000',
                'validity_hours' => 'required|integer|min:1|max:8760',
                'prefix' => 'nullable|string|max:20',
                'batch_label' => 'nullable|string|max:120',
            ]);

            $package = Package::query()
                ->where('tenant_id', $tenant->id)
                ->where('is_active', true)
                ->find($validated['package_id']);

            if (!$package) {
                return response()->json([
                    'success' => false,
                    'message' => 'Selected package is not available for this tenant',
                ], 422);
            }

            $prefix = strtoupper(trim((string) ($validated['prefix'] ?? 'CB-WIFI')));
            $prefix = preg_replace('/[^A-Z0-9-]/', '', $prefix);
            $prefix = trim((string) $prefix, '-');
            if ($prefix === '') {
                $prefix = 'CB-WIFI';
            }

            $batchId = (string) Str::uuid();
            $batchName = trim((string) ($validated['batch_label'] ?? ''));
            if ($batchName === '') {
                $batchName = 'Batch-' . now()->format('Ymd-His');
            }

            $quantity = (int) $validated['quantity'];
            $validityHours = (int) $validated['validity_hours'];

            $vouchers = DB::transaction(function () use ($tenant, $package, $quantity, $validityHours, $prefix, $batchId, $batchName) {
                $created = collect();

                for ($i = 0; $i < $quantity; $i++) {
                    do {
                        $code = strtoupper(Str::random(6));
                        $exists = \App\Models\Voucher::query()
                            ->where('tenant_id', $tenant->id)
                            ->where('prefix', $prefix)
                            ->where('code', $code)
                            ->exists();
                    } while ($exists);

                    $created->push(\App\Models\Voucher::create([
                        'tenant_id' => $tenant->id,
                        'package_id' => $package->id,
                        'code' => $code,
                        'prefix' => $prefix,
                        'status' => 'unused',
                        'valid_from' => now(),
                        'valid_until' => now()->copy()->addHours($validityHours),
                        'validity_hours' => $validityHours,
                        'batch_id' => $batchId,
                        'batch_name' => $batchName,
                        'captive_portal_redeemable' => true,
                    ]));
                }

                return $created;
            });

            return response()->json([
                'success' => true,
                'message' => "Generated {$quantity} voucher(s)",
                'data' => [
                    'batch_id' => $batchId,
                    'batch_name' => $batchName,
                    'package' => [
                        'id' => $package->id,
                        'name' => $package->name,
                    ],
                    'validity_hours' => $validityHours,
                    'vouchers' => $vouchers->map(fn ($voucher) => [
                        'id' => $voucher->id,
                        'code' => $voucher->code,
                        'code_display' => $voucher->code_display,
                        'created_at' => $voucher->created_at?->toIso8601String(),
                        'valid_until' => $voucher->valid_until?->toIso8601String(),
                    ]),
                ],
            ], 201);
        })->name('vouchers.generate');

        Route::get('/vouchers/{voucher}', function (\App\Models\Voucher $voucher) use ($resolveTenant) {
            $tenant = $resolveTenant();

            if (!$tenant || (int) $voucher->tenant_id !== (int) $tenant->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Voucher not found',
                ], 404);
            }

            $voucher->load(['package', 'router']);

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $voucher->id,
                    'code' => $voucher->code,
                    'code_display' => $voucher->code_display,
                    'status' => $voucher->status,
                    'package_name' => $voucher->package?->name,
                    'created_at' => $voucher->created_at?->toIso8601String(),
                    'valid_until' => $voucher->valid_until?->toIso8601String(),
                    'used_by_phone' => $voucher->used_by_phone,
                    'used_at' => $voucher->used_at?->toIso8601String(),
                    'router_name' => $voucher->router?->name,
                ],
            ]);
        })->name('vouchers.show');

        Route::delete('/vouchers/{voucher}', function (\App\Models\Voucher $voucher) use ($resolveTenant) {
            $tenant = $resolveTenant();

            if (!$tenant || (int) $voucher->tenant_id !== (int) $tenant->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Voucher not found',
                ], 404);
            }

            if (strtolower((string) $voucher->status) === 'used') {
                return response()->json([
                    'success' => false,
                    'message' => 'Used vouchers cannot be deleted',
                ], 422);
            }

            $voucher->delete();

            return response()->json([
                'success' => true,
                'message' => 'Voucher deleted successfully',
            ]);
        })->name('vouchers.delete');

        Route::post('/vouchers/bulk-delete', function (Request $request) use ($resolveTenant) {
            $tenant = $resolveTenant();

            if (!$tenant) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tenant not found',
                ], 404);
            }

            $validated = $request->validate([
                'voucher_ids' => 'required|array|min:1',
                'voucher_ids.*' => 'integer',
            ]);

            $vouchers = \App\Models\Voucher::query()
                ->where('tenant_id', $tenant->id)
                ->whereIn('id', $validated['voucher_ids'])
                ->get();

            if ($vouchers->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No matching vouchers found',
                ], 404);
            }

            $blocked = $vouchers->filter(fn ($voucher) => strtolower((string) $voucher->status) === 'used')->count();
            $toDelete = $vouchers->reject(fn ($voucher) => strtolower((string) $voucher->status) === 'used');

            foreach ($toDelete as $voucher) {
                $voucher->delete();
            }

            return response()->json([
                'success' => true,
                'message' => "Deleted {$toDelete->count()} voucher(s)" . ($blocked > 0 ? "; skipped {$blocked} used voucher(s)" : ''),
                'data' => [
                    'deleted' => $toDelete->count(),
                    'skipped_used' => $blocked,
                ],
            ]);
        })->name('vouchers.bulk-delete');

        Route::get('/vouchers', function (Request $request) use ($resolveTenant) {
            $tenant = $resolveTenant();

            $limit = min(max((int) $request->integer('limit', 100), 1), 500);
            $status = $request->string('status')->toString();
            $packageId = $request->integer('package_id');
            $search = trim($request->string('search')->toString());

            $vouchers = \App\Models\Voucher::query()
                ->when($tenant, fn ($query) => $query->where('tenant_id', $tenant->id))
                ->when($status !== '', fn ($query) => $query->where('status', $status))
                ->when($packageId > 0, fn ($query) => $query->where('package_id', $packageId))
                ->when($search !== '', fn ($query) => $query->where('code', 'like', "%{$search}%"))
                ->with('package')
                ->latest('created_at')
                ->limit($limit)
                ->get()
                ->map(function (\App\Models\Voucher $voucher) {
                    return [
                        'id' => $voucher->id,
                        'code' => $voucher->code,
                        'code_display' => $voucher->code_display,
                        'status' => $voucher->status,
                        'package_id' => $voucher->package_id,
                        'package_name' => $voucher->package?->name,
                        'used_by_phone' => $voucher->used_by_phone,
                        'valid_until' => $voucher->valid_until?->toIso8601String(),
                        'used_at' => $voucher->used_at?->toIso8601String(),
                        'created_at' => $voucher->created_at?->toIso8601String(),
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => $vouchers,
            ]);
        })->name('vouchers.index');
    });
});

// ============================================================================
// FALLBACK & ERROR HANDLING
// ============================================================================

// 404 Handler
Route::fallback(function () {
    if (request()->is('admin*')) {
        return response()->view('admin.errors.404', [], 404);
    }
    return response()->view('errors.404', [], 404);
});

// Health Check (for monitoring)
Route::get('/health', function () {
    return response()->json([
        'status' => 'healthy',
        'app' => config('app.name'),
        'env' => config('app.env'),
        'php' => phpversion(),
        'laravel' => app()->version(),
        'timestamp' => now()->toISOString()
    ]);
});
