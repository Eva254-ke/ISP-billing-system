<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Package;
use App\Models\Payment;
use App\Models\Router;
use App\Models\Tenant;
use App\Models\UserSession;
use App\Models\Voucher;
use App\Services\Admin\AdminSettingsService;
use App\Services\Admin\PaymentInvoiceService;
use App\Services\Admin\SystemLogExplorer;
use App\Services\Admin\TenantBackupService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\Support\Facades\Auth;

class AdminPageController extends Controller
{
    public function dashboard(): View
    {
        $tenant = $this->resolveTenant();
        UserSession::expireStaleSessions($tenant?->id);
        $successStatuses = ['completed', 'confirmed', 'activated'];

        $payments = Payment::query();
        $packages = Package::query();
        $routers = Router::query();
        $sessions = UserSession::query();

        if ($tenant) {
            $payments->where('tenant_id', $tenant->id);
            $packages->where('tenant_id', $tenant->id);
            $routers->where('tenant_id', $tenant->id);
            $sessions->where('tenant_id', $tenant->id);
        }

        $weeklyRevenue = collect();
        for ($i = 6; $i >= 0; $i--) {
            $day = now()->subDays($i);
            $weeklyRevenue->push([
                'date' => $day->toDateString(),
                'label' => $day->format('D'),
                'amount' => (float) (clone $payments)
                    ->whereDate('created_at', $day->toDateString())
                    ->whereIn('status', $successStatuses)
                    ->sum('amount'),
            ]);
        }

        $weeklyTransactions = (clone $payments)->where('created_at', '>=', now()->startOfWeek());
        $successfulTransactionsWeek = (clone $weeklyTransactions)
            ->whereIn('status', $successStatuses)
            ->count();
        $transactionsWeek = (clone $weeklyTransactions)->count();

        $routerRows = (clone $routers)->get();
        $routerStatusCounts = [
            'online' => 0,
            'warning' => 0,
            'offline' => 0,
            'error' => 0,
            'unknown' => 0,
        ];

        foreach ($routerRows as $router) {
            $status = strtolower((string) ($router->status ?? 'unknown'));
            if (!array_key_exists($status, $routerStatusCounts)) {
                $status = 'unknown';
            }

            $routerStatusCounts[$status]++;
        }

        $stats = [
            'revenue_today' => (float) (clone $payments)
                ->whereDate('created_at', now()->toDateString())
                ->whereIn('status', $successStatuses)
                ->sum('amount'),
            'active_sessions' => (clone $sessions)->active()->count(),
            'packages_total' => (clone $packages)->count(),
            'routers_online' => $routerStatusCounts['online'],
            'routers_total' => $routerRows->count(),
            'revenue_week' => (float) (clone $payments)
                ->where('created_at', '>=', now()->startOfWeek())
                ->whereIn('status', $successStatuses)
                ->sum('amount'),
        ];

        $recentPayments = (clone $payments)
            ->with(['package', 'session'])
            ->latest('created_at')
            ->limit(8)
            ->get();

        $routerStatuses = $routerRows
            ->sortByDesc(fn (Router $router) => $router->updated_at?->timestamp ?? 0)
            ->take(8)
            ->values();

        return view('admin.dashboard', [
            'tenant' => $tenant,
            'stats' => $stats,
            'recentPayments' => $recentPayments,
            'routerStatuses' => $routerStatuses,
            'weeklyRevenue' => $weeklyRevenue,
            'transactionsWeek' => $transactionsWeek,
            'successRateWeek' => $transactionsWeek > 0
                ? (int) round(($successfulTransactionsWeek / $transactionsWeek) * 100)
                : 0,
            'routerStatusBreakdown' => [
                ['key' => 'online', 'label' => 'Online', 'count' => $routerStatusCounts['online'], 'color' => '#198754'],
                ['key' => 'warning', 'label' => 'Warning', 'count' => $routerStatusCounts['warning'], 'color' => '#f59e0b'],
                ['key' => 'offline', 'label' => 'Offline', 'count' => $routerStatusCounts['offline'], 'color' => '#dc3545'],
                ['key' => 'error', 'label' => 'Error', 'count' => $routerStatusCounts['error'], 'color' => '#6c757d'],
                ['key' => 'unknown', 'label' => 'Unknown', 'count' => $routerStatusCounts['unknown'], 'color' => '#adb5bd'],
            ],
        ]);
    }

    public function routers(): View
    {
        $tenant = $this->resolveTenant();

        $routers = Router::query();
        if ($tenant) {
            $routers->where('tenant_id', $tenant->id);
        }

        $rows = $routers->orderBy('name')->get();

        $stats = [
            'online' => $rows->filter(fn ($router) => in_array((string) $router->status, ['online', 'warning'], true))->count(),
            'offline' => $rows->where('status', 'offline')->count(),
            'total_users' => (int) $rows->sum(fn ($router) => (int) ($router->active_sessions ?? 0)),
            'total' => $rows->count(),
        ];

        return view('admin.routers.index', [
            'tenant' => $tenant,
            'stats' => $stats,
            'routers' => $rows,
        ]);
    }

    public function packages(): View
    {
        $tenant = $this->resolveTenant();
        $user = Auth::user();
        $isSuperAdmin = (($user?->role ?? null) === 'super_admin');
        $selectedTenantId = (int) request()->query('tenant_id', 0);
        $tenants = $isSuperAdmin
            ? Tenant::query()->active()->orderBy('name')->get(['id', 'name'])
            : collect();

        $packages = Package::query();
        $payments = Payment::query();

        if ($tenant) {
            $packages->where('tenant_id', $tenant->id);
            $payments->where('tenant_id', $tenant->id);
        }

        $rows = $packages->orderBy('sort_order')->orderBy('price')->get();

        $stats = [
            'total' => $rows->count(),
            'active' => $rows->where('is_active', true)->count(),
            'inactive' => $rows->where('is_active', false)->count(),
            'revenue_week' => (float) (clone $payments)
                ->where('created_at', '>=', now()->startOfWeek())
                ->whereIn('status', ['completed', 'confirmed', 'activated'])
                ->sum('amount'),
        ];

        return view('admin.packages.index', [
            'tenant' => $tenant,
            'stats' => $stats,
            'packages' => $rows,
            'isSuperAdmin' => $isSuperAdmin,
            'tenants' => $tenants,
            'selectedTenantId' => $selectedTenantId,
        ]);
    }

    public function payments(): View
    {
        $tenant = $this->resolveTenant();

        $basePayments = Payment::query()
            ->when($tenant, fn ($query) => $query->where('tenant_id', $tenant->id));

        $packages = Package::query()
            ->when($tenant, fn ($query) => $query->where('tenant_id', $tenant->id))
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        $rows = (clone $basePayments)
            ->with(['package', 'session'])
            ->latest('created_at')
            ->limit(100)
            ->get();

        $dailyRevenue = collect();
        for ($i = 6; $i >= 0; $i--) {
            $day = now()->subDays($i);
            $dailyRevenue->push([
                'date' => $day->toDateString(),
                'label' => $day->format('D'),
                'amount' => (float) (clone $basePayments)
                    ->whereDate('created_at', $day->toDateString())
                    ->whereIn('status', ['completed', 'confirmed', 'activated'])
                    ->sum('amount'),
            ]);
        }

        $stats = [
            'revenue_total' => (float) (clone $basePayments)
                ->whereIn('status', ['completed', 'confirmed', 'activated'])
                ->sum('amount'),
            'revenue_today' => (float) (clone $basePayments)
                ->whereDate('created_at', now()->toDateString())
                ->whereIn('status', ['completed', 'confirmed', 'activated'])
                ->sum('amount'),
            'pending' => (clone $basePayments)->where('status', 'pending')->count(),
            'failed' => (clone $basePayments)->where('status', 'failed')->count(),
        ];

        return view('admin.payments.index', [
            'tenant' => $tenant,
            'stats' => $stats,
            'payments' => $rows,
            'packages' => $packages,
            'dailyRevenue' => $dailyRevenue,
        ]);
    }

    public function paymentsExport(): StreamedResponse
    {
        $tenant = $this->resolveTenant();

        $payments = Payment::query()
            ->when($tenant, fn ($query) => $query->where('tenant_id', $tenant->id))
            ->with(['package', 'session'])
            ->latest('created_at')
            ->limit(1000)
            ->get();

        $filename = 'payments-export-' . now()->format('Ymd-His') . '.csv';

        return response()->streamDownload(function () use ($payments) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['date', 'phone', 'customer', 'package', 'amount', 'currency', 'status', 'receipt']);

            foreach ($payments as $payment) {
                fputcsv($out, [
                    $payment->created_at?->toDateTimeString(),
                    $payment->phone,
                    $payment->display_customer_name,
                    $payment->package_name,
                    $payment->amount,
                    $payment->currency,
                    $payment->status,
                    $payment->mpesa_receipt_number ?: $payment->mpesa_checkout_request_id,
                ]);
            }

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    public function paymentInvoice(Payment $payment, PaymentInvoiceService $invoiceService): View
    {
        $tenant = $this->resolveTenant();
        $payment = $this->scopedPayment($payment, $tenant);

        return view('admin.payments.invoice', [
            'tenant' => $tenant,
            'invoice' => $invoiceService->buildInvoice($payment),
        ]);
    }

    public function routersCreate(): View
    {
        return view('admin.routers.create', [
            'tenant' => $this->resolveTenant(),
        ]);
    }

    public function routersShow(Router $router): View
    {
        $tenant = $this->resolveTenant();
        $router = $this->scopedRouter($router, $tenant);

        $activeSessions = UserSession::query()
            ->where('router_id', $router->id)
            ->active()
            ->limit(20)
            ->get();

        return view('admin.routers.show', [
            'tenant' => $tenant,
            'router' => $router,
            'activeSessions' => $activeSessions,
        ]);
    }

    public function routersEdit(Router $router): View
    {
        $tenant = $this->resolveTenant();

        return view('admin.routers.edit', [
            'tenant' => $tenant,
            'router' => $this->scopedRouter($router, $tenant),
        ]);
    }

    public function packagesCreate(): View
    {
        $user = Auth::user();
        $isSuperAdmin = (($user?->role ?? null) === 'super_admin');

        return view('admin.packages.create', [
            'tenant' => $this->resolveTenant(),
            'isSuperAdmin' => $isSuperAdmin,
            'tenants' => $isSuperAdmin
                ? Tenant::query()->active()->orderBy('name')->get(['id', 'name'])
                : collect(),
        ]);
    }

    public function packagesShow(Package $package): View
    {
        $tenant = $this->resolveTenant();
        $package = $this->scopedPackage($package, $tenant);

        $sales = Payment::query()
            ->where('package_id', $package->id)
            ->whereIn('status', ['completed', 'confirmed', 'activated'])
            ->count();

        $revenue = (float) Payment::query()
            ->where('package_id', $package->id)
            ->whereIn('status', ['completed', 'confirmed', 'activated'])
            ->sum('amount');

        return view('admin.packages.show', [
            'tenant' => $tenant,
            'package' => $package,
            'sales' => $sales,
            'revenue' => $revenue,
        ]);
    }

    public function packagesEdit(Package $package): View
    {
        $tenant = $this->resolveTenant();

        return view('admin.packages.edit', [
            'tenant' => $tenant,
            'package' => $this->scopedPackage($package, $tenant),
        ]);
    }

    public function vouchersIndex(): View
    {
        $tenant = $this->resolveTenant();

        $packages = Package::query()
            ->when($tenant, fn ($query) => $query->where('tenant_id', $tenant->id))
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'price']);

        $baseVouchers = Voucher::query()
            ->when($tenant, fn ($query) => $query->where('tenant_id', $tenant->id))
            ->with('package');

        $vouchers = (clone $baseVouchers)
            ->with('package')
            ->latest('created_at')
            ->limit(100)
            ->get();

        $stats = [
            'total' => (clone $baseVouchers)->count(),
            'unused' => (clone $baseVouchers)->where('status', 'unused')->count(),
            'used' => (clone $baseVouchers)->where('status', 'used')->count(),
            'expired' => (clone $baseVouchers)
                ->where(function ($query) {
                    $query->where('status', 'expired')
                        ->orWhere('valid_until', '<', now());
                })
                ->count(),
        ];

        return view('admin.vouchers.index', [
            'tenant' => $tenant,
            'vouchers' => $vouchers,
            'stats' => $stats,
            'packages' => $packages,
        ]);
    }

    public function vouchersGenerate(): View
    {
        $tenant = $this->resolveTenant();

        $packages = Package::query()
            ->when($tenant, fn ($query) => $query->where('tenant_id', $tenant->id))
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('admin.vouchers.generate', [
            'tenant' => $tenant,
            'packages' => $packages,
        ]);
    }

    public function vouchersPrint(): View
    {
        $tenant = $this->resolveTenant();

        $latestVouchers = Voucher::query()
            ->when($tenant, fn ($query) => $query->where('tenant_id', $tenant->id))
            ->with('package')
            ->latest('created_at')
            ->limit(40)
            ->get();

        return view('admin.vouchers.print', [
            'tenant' => $tenant,
            'latestVouchers' => $latestVouchers,
        ]);
    }

    public function clientsHotspot(): View
    {
        return $this->clientsView('hotspot');
    }

    public function clientsPppoe(): View
    {
        return $this->clientsView('pppoe');
    }

    public function clientsCustomers(): View
    {
        $tenant = $this->resolveTenant();
        UserSession::expireStaleSessions($tenant?->id);

        $baseSessions = UserSession::query()
            ->when($tenant, fn ($query) => $query->where('tenant_id', $tenant->id));

        $sessions = (clone $baseSessions)
            ->with(['package', 'router'])
            ->latest('created_at')
            ->limit(150)
            ->get();

        $stats = [
            'total' => (clone $baseSessions)->count(),
            'active' => (clone $baseSessions)->active()->count(),
            'suspended' => (clone $baseSessions)->where('status', 'suspended')->count(),
            'new_month' => (clone $baseSessions)->where('created_at', '>=', now()->startOfMonth())->count(),
        ];

        return view('admin.clients.customers', [
            'tenant' => $tenant,
            'sessions' => $sessions,
            'stats' => $stats,
        ]);
    }

    public function settingsIndex(): View
    {
        return $this->settingsView('general');
    }

    public function settingsMpesa(): View
    {
        return $this->settingsView('mpesa');
    }

    public function settingsSms(): View
    {
        return $this->settingsView('sms');
    }

    public function settingsBranding(): View
    {
        return $this->settingsView('branding');
    }

    public function settingsAccount(): View
    {
        return $this->settingsView('account');
    }

    public function logsIndex(Request $request, SystemLogExplorer $logExplorer): View
    {
        return view('admin.logs.index', [
            'tenant' => $this->resolveTenant(),
            'logSnapshot' => $logExplorer->snapshot($request->only([
                'source',
                'level',
                'search',
                'limit',
                'file',
            ])),
        ]);
    }

    private function clientsView(string $mode): View
    {
        $tenant = $this->resolveTenant();
        UserSession::expireStaleSessions($tenant?->id);

        $baseSessions = UserSession::query()
            ->when($tenant, fn ($query) => $query->where('tenant_id', $tenant->id));

        if ($mode === 'pppoe') {
            $baseSessions->where('username', 'like', 'pppoe%');
        } elseif ($mode === 'hotspot') {
            $baseSessions->where(function ($query) {
                $query->whereNull('username')
                    ->orWhere('username', 'not like', 'pppoe%');
            });
        }

        $sessions = (clone $baseSessions)
            ->live()
            ->with(['package', 'router'])
            ->orderByDesc('last_activity_at')
            ->orderByDesc('started_at')
            ->limit(150)
            ->get();

        $stats = [
            'active_sessions' => (clone $baseSessions)->active()->count(),
            'total_bandwidth' => round(((int) (clone $baseSessions)->sum('bytes_total')) / (1024 * 1024 * 1024), 2),
            'new_last_hour' => (clone $baseSessions)->where('created_at', '>=', now()->subHour())->count(),
            'routers_online' => Router::query()
                ->when($tenant, fn ($query) => $query->where('tenant_id', $tenant->id))
                ->whereIn('status', ['online', 'warning'])
                ->count(),
        ];

        return view($mode === 'pppoe' ? 'admin.clients.pppoe' : 'admin.clients.hotspot', [
            'tenant' => $tenant,
            'stats' => $stats,
            'sessions' => $sessions,
        ]);
    }

    private function settingsView(string $tab): View
    {
        $tenant = $this->resolveTenant();
        UserSession::expireStaleSessions($tenant?->id);
        $settingsService = app(AdminSettingsService::class);
        $backupService = app(TenantBackupService::class);
        $settings = $settingsService->getSettings($tenant);

        $tenantStats = [
            'routers_total' => Router::query()
                ->when($tenant, fn ($query) => $query->where('tenant_id', $tenant->id))
                ->count(),
            'packages_total' => Package::query()
                ->when($tenant, fn ($query) => $query->where('tenant_id', $tenant->id))
                ->count(),
            'active_sessions' => UserSession::query()
                ->when($tenant, fn ($query) => $query->where('tenant_id', $tenant->id))
                ->active()
                ->count(),
            'payments_today' => (float) Payment::query()
                ->when($tenant, fn ($query) => $query->where('tenant_id', $tenant->id))
                ->whereDate('created_at', now()->toDateString())
                ->whereIn('status', ['completed', 'confirmed', 'activated'])
                ->sum('amount'),
        ];

        $successfulPayments = Payment::query()
            ->when($tenant, fn ($query) => $query->where('tenant_id', $tenant->id))
            ->whereIn('status', ['confirmed', 'completed', 'activated']);

        $successfulSalesTotal = (float) (clone $successfulPayments)->sum('amount');
        $successfulSalesThisMonth = (float) (clone $successfulPayments)
            ->where('created_at', '>=', now()->startOfMonth())
            ->sum('amount');
        $salesLevyRate = !empty($settings['tax_enabled']) ? (float) ($settings['tax_rate'] ?? 0) : 0.0;

        return view('admin.settings.index', [
            'tenant' => $tenant,
            'activeTab' => $tab,
            'tenantStats' => $tenantStats,
            'billingSummary' => [
                'successful_sales_total' => $successfulSalesTotal,
                'successful_sales_this_month' => $successfulSalesThisMonth,
                'levy_rate' => $salesLevyRate,
                'levy_total' => round($successfulSalesTotal * ($salesLevyRate / 100), 2),
                'levy_this_month' => round($successfulSalesThisMonth * ($salesLevyRate / 100), 2),
            ],
            'systemStatus' => $settingsService->runtimeStatus(),
            'backupStatus' => $backupService->latestBackupMetadata($tenant),
            'settingsPreview' => $settingsService->invoicePreviewContext($tenant),
        ]);
    }

    private function scopedRouter(Router $router, ?Tenant $tenant): Router
    {
        if ($tenant && $router->tenant_id !== $tenant->id) {
            abort(404);
        }

        return $router;
    }

    private function scopedPackage(Package $package, ?Tenant $tenant): Package
    {
        if ($tenant && $package->tenant_id !== $tenant->id) {
            abort(404);
        }

        return $package;
    }

    private function scopedPayment(Payment $payment, ?Tenant $tenant): Payment
    {
        if ($tenant && $payment->tenant_id !== $tenant->id) {
            abort(404);
        }

        return $payment;
    }

    private function resolveTenant(): ?Tenant
    {
        $user = Auth::user();
        if (!$user) {
            return null;
        }

        if ($user->tenant_id) {
            return Tenant::find($user->tenant_id);
        }

        if (($user->role ?? null) === 'super_admin') {
            return null;
        }

        return null;
    }
}
