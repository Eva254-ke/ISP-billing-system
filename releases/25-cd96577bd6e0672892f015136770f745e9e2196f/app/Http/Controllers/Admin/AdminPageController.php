<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Package;
use App\Models\Payment;
use App\Models\Router;
use App\Models\Tenant;
use App\Models\UserSession;
use App\Models\Voucher;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\Support\Facades\Auth;

class AdminPageController extends Controller
{
    public function dashboard(): View
    {
        $tenant = $this->resolveTenant();

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

        $stats = [
            'revenue_today' => (float) (clone $payments)
                ->whereDate('created_at', now()->toDateString())
                ->whereIn('status', ['completed', 'confirmed'])
                ->sum('amount'),
            'active_sessions' => (clone $sessions)->active()->count(),
            'packages_total' => (clone $packages)->count(),
            'routers_online' => (clone $routers)->where('status', 'online')->count(),
            'routers_total' => (clone $routers)->count(),
            'revenue_week' => (float) (clone $payments)
                ->where('created_at', '>=', now()->startOfWeek())
                ->whereIn('status', ['completed', 'confirmed'])
                ->sum('amount'),
        ];

        $recentPayments = (clone $payments)
            ->with('package')
            ->latest('created_at')
            ->limit(8)
            ->get();

        $routerStatuses = (clone $routers)
            ->latest('updated_at')
            ->limit(8)
            ->get();

        return view('admin.dashboard', [
            'tenant' => $tenant,
            'stats' => $stats,
            'recentPayments' => $recentPayments,
            'routerStatuses' => $routerStatuses,
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
            'online' => $rows->where('status', 'online')->count(),
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
                ->whereIn('status', ['completed', 'confirmed'])
                ->sum('amount'),
        ];

        return view('admin.packages.index', [
            'tenant' => $tenant,
            'stats' => $stats,
            'packages' => $rows,
        ]);
    }

    public function payments(): View
    {
        $tenant = $this->resolveTenant();

        $basePayments = Payment::query()
            ->when($tenant, fn ($query) => $query->where('tenant_id', $tenant->id));

        $rows = (clone $basePayments)
            ->with('package')
            ->latest('created_at')
            ->limit(100)
            ->get();

        $stats = [
            'revenue_total' => (float) (clone $basePayments)
                ->whereIn('status', ['completed', 'confirmed'])
                ->sum('amount'),
            'revenue_today' => (float) (clone $basePayments)
                ->whereDate('created_at', now()->toDateString())
                ->whereIn('status', ['completed', 'confirmed'])
                ->sum('amount'),
            'pending' => (clone $basePayments)->where('status', 'pending')->count(),
            'failed' => (clone $basePayments)->where('status', 'failed')->count(),
        ];

        return view('admin.payments.index', [
            'tenant' => $tenant,
            'stats' => $stats,
            'payments' => $rows,
        ]);
    }

    public function paymentsExport(): StreamedResponse
    {
        $tenant = $this->resolveTenant();

        $payments = Payment::query()
            ->when($tenant, fn ($query) => $query->where('tenant_id', $tenant->id))
            ->latest('created_at')
            ->limit(1000)
            ->get();

        $filename = 'payments-export-' . now()->format('Ymd-His') . '.csv';

        return response()->streamDownload(function () use ($payments) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['date', 'phone', 'package', 'amount', 'currency', 'status', 'receipt']);

            foreach ($payments as $payment) {
                fputcsv($out, [
                    $payment->created_at?->toDateTimeString(),
                    $payment->phone,
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
            ->whereIn('status', ['completed', 'confirmed'])
            ->count();

        $revenue = (float) Payment::query()
            ->where('package_id', $package->id)
            ->whereIn('status', ['completed', 'confirmed'])
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

        $baseSessions = UserSession::query()
            ->when($tenant, fn ($query) => $query->where('tenant_id', $tenant->id));

        $sessions = (clone $baseSessions)
            ->with(['package', 'router'])
            ->latest('created_at')
            ->limit(150)
            ->get();

        $stats = [
            'total' => (clone $baseSessions)->count(),
            'active' => (clone $baseSessions)->where('status', 'active')->count(),
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

    private function clientsView(string $mode): View
    {
        $tenant = $this->resolveTenant();

        $baseSessions = UserSession::query()
            ->when($tenant, fn ($query) => $query->where('tenant_id', $tenant->id));

        if ($mode === 'pppoe') {
            $baseSessions->where('username', 'like', 'pppoe%');
        }

        $sessions = (clone $baseSessions)
            ->with(['package', 'router'])
            ->latest('created_at')
            ->limit(150)
            ->get();

        $stats = [
            'active_sessions' => (clone $baseSessions)->where('status', 'active')->count(),
            'total_bandwidth' => round(((int) (clone $baseSessions)->sum('bytes_total')) / (1024 * 1024 * 1024), 2),
            'new_last_hour' => (clone $baseSessions)->where('created_at', '>=', now()->subHour())->count(),
            'routers_online' => Router::query()
                ->when($tenant, fn ($query) => $query->where('tenant_id', $tenant->id))
                ->where('status', 'online')
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

        $tenantStats = [
            'routers_total' => Router::query()
                ->when($tenant, fn ($query) => $query->where('tenant_id', $tenant->id))
                ->count(),
            'packages_total' => Package::query()
                ->when($tenant, fn ($query) => $query->where('tenant_id', $tenant->id))
                ->count(),
            'active_sessions' => UserSession::query()
                ->when($tenant, fn ($query) => $query->where('tenant_id', $tenant->id))
                ->where('status', 'active')
                ->count(),
            'payments_today' => (float) Payment::query()
                ->when($tenant, fn ($query) => $query->where('tenant_id', $tenant->id))
                ->whereDate('created_at', now()->toDateString())
                ->whereIn('status', ['completed', 'confirmed'])
                ->sum('amount'),
        ];

        return view('admin.settings.index', [
            'tenant' => $tenant,
            'activeTab' => $tab,
            'tenantStats' => $tenantStats,
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
