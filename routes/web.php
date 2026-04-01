<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;

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
// Authentication (Mock for Frontend Development)
// ----------------------------------------------------------------------------
Route::get('/login', function () {
    return view('admin.auth.login');
})->name('login');

Route::post('/login', function (Request $request) {
    // Mock authentication - replace with real auth later
    if ($request->email === 'admin@cloudbridge.network' && $request->password === 'password') {
        session(['admin_logged_in' => true, 'admin_email' => $request->email]);
        return redirect()->route('admin.dashboard');
    }
    return back()->with('error', 'Invalid credentials. Try: admin@cloudbridge.network / password');
})->name('login.post');

Route::post('/logout', function () {
    session()->forget('admin_logged_in', 'admin_email');
    return redirect()->route('login');
})->name('logout');

// ----------------------------------------------------------------------------
// Customer Portal Routes (Public - Captive Portal)
// ----------------------------------------------------------------------------
Route::prefix('portal')->name('portal.')->group(function () {
    Route::get('/', fn() => view('portal.home'))->name('home');
    Route::get('/package/{package}', fn($package) => view('portal.payment', ['package' => $package]))->name('package.show');
    Route::post('/payment/initiate', fn() => view('portal.payment_status'))->name('payment.initiate');
    Route::get('/payment/status/{checkoutId}', fn($checkoutId) => view('portal.payment_status'))->name('payment.status');
    Route::get('/payment/success/{payment}', fn($payment) => view('portal.success'))->name('payment.success');
    Route::get('/voucher/redeem', fn() => view('portal.voucher'))->name('voucher.redeem');
});

// ----------------------------------------------------------------------------
// Legacy M-Pesa Webhook (Public - kept for older callback URLs / local testing)
// ----------------------------------------------------------------------------
Route::post('/api/mpesa/callback', function (Request $request) {
    // Mock webhook handler - implement real logic later
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
    Route::get('/dashboard', function () {
        return view('admin.dashboard');
    })->name('dashboard');
    
    // ----------------------------------------------------------------------------
    // Routers Management (MikroTik)
    // ----------------------------------------------------------------------------
    Route::prefix('routers')->name('routers.')->group(function () {
        Route::get('/', function () {
            return view('admin.routers.index');
        })->name('index');
        
        Route::get('/create', function () {
            return view('admin.routers.create');
        })->name('create');
        
        Route::get('/{router}', function ($router) {
            return view('admin.routers.show');
        })->name('show');
        
        Route::get('/{router}/edit', function ($router) {
            return view('admin.routers.edit');
        })->name('edit');
    });
    
    // ----------------------------------------------------------------------------
    // Packages Management (WiFi Plans)
    // ----------------------------------------------------------------------------
    Route::prefix('packages')->name('packages.')->group(function () {
        Route::get('/', function () {
            return view('admin.packages.index');
        })->name('index');
        
        Route::get('/create', function () {
            return view('admin.packages.create');
        })->name('create');
        
        Route::get('/{package}', function ($package) {
            return view('admin.packages.show');
        })->name('show');
        
        Route::get('/{package}/edit', function ($package) {
            return view('admin.packages.edit');
        })->name('edit');
    });
    
    // ----------------------------------------------------------------------------
    // Vouchers Management (Offline Sales)
    // ----------------------------------------------------------------------------
    Route::prefix('vouchers')->name('vouchers.')->group(function () {
        Route::get('/', function () {
            return view('admin.vouchers.index');
        })->name('index');
        
        Route::get('/generate', function () {
            return view('admin.vouchers.generate');
        })->name('generate');
        
        Route::get('/print', function () {
            return view('admin.vouchers.print');
        })->name('print');
    });
    
    // ----------------------------------------------------------------------------
    // Payments & Reports
    // ----------------------------------------------------------------------------
    Route::prefix('payments')->name('payments.')->group(function () {
        Route::get('/', function () {
            return view('admin.payments.index');
        })->name('index');
        
        Route::get('/export', function () {
            return response()->json(['message' => 'Export functionality coming soon']);
        })->name('export');
    });
    
    // ----------------------------------------------------------------------------
    // Clients Monitoring (Live Sessions)
    // ----------------------------------------------------------------------------
    Route::prefix('clients')->name('clients.')->group(function () {
        // Hotspot Clients
        Route::get('/hotspot', function () {
            return view('admin.clients.hotspot');
        })->name('hotspot');
        
        // PPPoE Clients ✅
        Route::get('/pppoe', function () {
            return view('admin.clients.pppoe');
        })->name('pppoe');
        
        // Customer List
        Route::get('/customers', function () {
            return view('admin.clients.customers');
        })->name('customers');
    });
    
    // ----------------------------------------------------------------------------
    // Settings & Configuration
    // ----------------------------------------------------------------------------
    Route::get('/settings', function () {
        return view('admin.settings.index');
    })->name('settings');
    
    Route::prefix('settings')->name('settings.')->group(function () {
        Route::get('/mpesa', function () {
            return view('admin.settings.mpesa');
        })->name('mpesa');
        
        Route::get('/sms', function () {
            return view('admin.settings.sms');
        })->name('sms');
        
        Route::get('/branding', function () {
            return view('admin.settings.branding');
        })->name('branding');
        
        Route::get('/account', function () {
            return view('admin.settings.account');
        })->name('account');
    });
    
    // ----------------------------------------------------------------------------
    // API Routes for AJAX (Mock Responses for Frontend)
    // ----------------------------------------------------------------------------
    Route::prefix('api')->name('api.')->group(function () {
        
        // Router Status & Testing
        Route::get('/routers/status', function () {
            return response()->json([
                'success' => true,
                'data' => [
                    ['id' => 1, 'name' => 'Main Hotspot', 'ip' => '192.168.88.1', 'status' => 'online', 'users' => 180, 'cpu' => 45, 'memory' => 62],
                    ['id' => 2, 'name' => 'PPPoE Server', 'ip' => '192.168.88.2', 'status' => 'online', 'users' => 54, 'cpu' => 32, 'memory' => 48],
                    ['id' => 3, 'name' => 'Backup Router', 'ip' => '192.168.88.3', 'status' => 'offline', 'users' => 0, 'cpu' => null, 'memory' => null],
                    ['id' => 4, 'name' => 'Karen Branch', 'ip' => '192.168.88.4', 'status' => 'online', 'users' => 45, 'cpu' => 28, 'memory' => 41],
                ]
            ]);
        })->name('routers.status');
        
        Route::post('/routers/test', function (Request $request) {
            // Mock connection test
            return response()->json([
                'success' => true,
                'message' => 'Connection successful',
                'latency' => rand(5, 50) . 'ms',
                'router' => $request->input('router_id')
            ]);
        })->name('routers.test');
        
        // Package Stats
        Route::get('/packages/stats', function () {
            return response()->json([
                'total' => 12,
                'active' => 8,
                'revenue_today' => 12500,
                'revenue_week' => 45200
            ]);
        })->name('packages.stats');
        
        // Client Stats (Live)
        Route::get('/clients/stats', function () {
            return response()->json([
                'hotspot_active' => 180,
                'pppoe_active' => 54,
                'total_bandwidth' => '2.5 GB/s'
            ]);
        })->name('clients.stats');
        
        // Disconnect Client (Mock)
        Route::post('/clients/disconnect', function (Request $request) {
            return response()->json([
                'success' => true,
                'message' => "Client {$request->input('username')} disconnected",
                'router' => $request->input('router_id')
            ]);
        })->name('clients.disconnect');
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
