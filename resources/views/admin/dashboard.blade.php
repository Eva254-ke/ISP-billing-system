@extends('admin.layouts.app')

@section('page-title', 'Dashboard')

@section('content')
<!-- Stats Cards Row -->
<div class="row">
    <!-- Revenue Today -->
    <div class="col-lg-3 col-6">
        <div class="small-box bg-primary position-relative">
            <div class="inner">
                <h3>KES 12,500</h3>
                <p>Revenue Today</p>
            </div>
            <div class="icon">
                <i class="fas fa-money-bill-wave"></i>
            </div>
            <div class="small-box-footer">
                View Details <i class="fas fa-arrow-circle-right"></i>
            </div>
            <a href="{{ route('admin.payments.index') }}" class="stretched-link" aria-label="View payment details"></a>
        </div>
    </div>

    <!-- Active Sessions -->
    <div class="col-lg-3 col-6">
        <div class="small-box bg-success position-relative">
            <div class="inner">
                <h3>234</h3>
                <p>Active Sessions</p>
            </div>
            <div class="icon">
                <i class="fas fa-wifi"></i>
            </div>
            <div class="small-box-footer">
                View Clients <i class="fas fa-arrow-circle-right"></i>
            </div>
            <a href="{{ route('admin.clients.hotspot') }}" class="stretched-link" aria-label="View clients"></a>
        </div>
    </div>

    <!-- Total Packages -->
    <div class="col-lg-3 col-6">
        <div class="small-box bg-warning position-relative">
            <div class="inner">
                <h3>12</h3>
                <p>Packages</p>
            </div>
            <div class="icon">
                <i class="fas fa-box"></i>
            </div>
            <div class="small-box-footer">
                Manage <i class="fas fa-arrow-circle-right"></i>
            </div>
            <a href="{{ route('admin.packages.index') }}" class="stretched-link" aria-label="Manage packages"></a>
        </div>
    </div>

    <!-- Routers Online -->
    <div class="col-lg-3 col-6">
        <div class="small-box bg-danger position-relative">
            <div class="inner">
                <h3>3/4</h3>
                <p>Routers Online</p>
            </div>
            <div class="icon">
                <i class="fas fa-server"></i>
            </div>
            <div class="small-box-footer">
                Check Status <i class="fas fa-arrow-circle-right"></i>
            </div>
            <a href="{{ route('admin.routers.index') }}" class="stretched-link" aria-label="Check router status"></a>
        </div>
    </div>
</div>

<!-- Charts Row -->
<div class="row">
    <!-- Revenue Chart -->
    <div class="col-md-8">
        <div class="card card-primary">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-chart-area me-2"></i>
                    Revenue (Last 7 Days)
                </h3>
                <div class="card-tools">
                    <button type="button" class="btn btn-tool" data-card-widget="collapse">
                        <i class="fas fa-minus"></i>
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div id="revenueChart" style="min-height: 350px;"></div>
            </div>
            <div class="card-footer">
                <div class="row">
                    <div class="col-sm-3 col-6">
                        <div class="description-block border-end">
                            <h5 class="description-header">KES 45,200</h5>
                            <span class="description-text">This Week</span>
                        </div>
                    </div>
                    <div class="col-sm-3 col-6">
                        <div class="description-block border-end">
                            <h5 class="description-header">+15%</h5>
                            <span class="description-text">vs Last Week</span>
                        </div>
                    </div>
                    <div class="col-sm-3 col-6">
                        <div class="description-block border-end">
                            <h5 class="description-header">412</h5>
                            <span class="description-text">Transactions</span>
                        </div>
                    </div>
                    <div class="col-sm-3 col-6">
                        <div class="description-block">
                            <h5 class="description-header">98%</h5>
                            <span class="description-text">Success Rate</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Package Distribution -->
    <div class="col-md-4">
        <div class="card card-primary">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-chart-pie me-2"></i>
                    Package Sales
                </h3>
            </div>
            <div class="card-body">
                <div id="packageChart" style="min-height: 300px;"></div>
            </div>
        </div>
    </div>
</div>

<!-- Recent Activity Row -->
<div class="row">
    <!-- Recent Payments -->
    <div class="col-md-6">
        <div class="card">
            <div class="card-header border-0">
                <h3 class="card-title">
                    <i class="fas fa-clock me-2"></i>
                    Recent Payments
                </h3>
                <div class="card-tools">
                    <a href="{{ route('admin.payments.index') }}" class="btn btn-sm btn-primary">
                        View All
                    </a>
                </div>
            </div>
            <div class="card-body table-responsive p-0">
                <table class="table table-hover table-striped">
                    <thead>
                        <tr>
                            <th>Time</th>
                            <th>Customer</th>
                            <th>Package</th>
                            <th>Amount</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>10:45 AM</td>
                            <td>0712***678</td>
                            <td>1 Hour</td>
                            <td>KES 50</td>
                            <td><span class="badge bg-success">Success</span></td>
                        </tr>
                        <tr>
                            <td>10:32 AM</td>
                            <td>0723***789</td>
                            <td>3 Hours</td>
                            <td>KES 100</td>
                            <td><span class="badge bg-success">Success</span></td>
                        </tr>
                        <tr>
                            <td>10:15 AM</td>
                            <td>0734***890</td>
                            <td>24 Hours</td>
                            <td>KES 400</td>
                            <td><span class="badge bg-success">Success</span></td>
                        </tr>
                        <tr>
                            <td>09:58 AM</td>
                            <td>0745***901</td>
                            <td>1 Hour</td>
                            <td>KES 50</td>
                            <td><span class="badge bg-danger">Failed</span></td>
                        </tr>
                        <tr>
                            <td>09:45 AM</td>
                            <td>0756***012</td>
                            <td>Weekly</td>
                            <td>KES 2,000</td>
                            <td><span class="badge bg-success">Success</span></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Router Status -->
    <div class="col-md-6">
        <div class="card">
            <div class="card-header border-0">
                <h3 class="card-title">
                    <i class="fas fa-server me-2"></i>
                    Router Status
                </h3>
                <div class="card-tools">
                    <a href="{{ route('admin.routers.index') }}" class="btn btn-sm btn-primary">
                        Manage
                    </a>
                </div>
            </div>
            <div class="card-body">
                <ul class="products-list product-list-in-card ps-2 pe-2">
                    <li class="item">
                        <div class="product-img">
                            <i class="fas fa-circle text-success fa-2x"></i>
                        </div>
                        <div class="product-info">
                            <a href="#" class="product-title">Main Hotspot
                                <span class="badge bg-success float-end">Online</span></a>
                            <span class="product-description">192.168.88.1 • 180 users</span>
                        </div>
                    </li>
                    <li class="item">
                        <div class="product-img">
                            <i class="fas fa-circle text-success fa-2x"></i>
                        </div>
                        <div class="product-info">
                            <a href="#" class="product-title">PPPoE Server
                                <span class="badge bg-success float-end">Online</span></a>
                            <span class="product-description">192.168.88.2 • 54 users</span>
                        </div>
                    </li>
                    <li class="item">
                        <div class="product-img">
                            <i class="fas fa-circle text-danger fa-2x"></i>
                        </div>
                        <div class="product-info">
                            <a href="#" class="product-title">Backup Router
                                <span class="badge bg-danger float-end">Offline</span></a>
                            <span class="product-description">192.168.88.3 • 0 users</span>
                        </div>
                    </li>
                    <li class="item">
                        <div class="product-img">
                            <i class="fas fa-circle text-success fa-2x"></i>
                        </div>
                        <div class="product-info">
                            <a href="#" class="product-title">Karen Branch
                                <span class="badge bg-success float-end">Online</span></a>
                            <span class="product-description">192.168.88.4 • 45 users</span>
                        </div>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', async function () {
        // Ensure stretched-link works even if AdminLTE overrides positions
        document.querySelectorAll('.small-box').forEach(box => {
            box.classList.add('position-relative');
        });

        // Utility: load ApexCharts from CDN if Vite bundle not ready
        async function ensureApex() {
            if (window.ApexCharts) return;
            await new Promise((resolve, reject) => {
                const s = document.createElement('script');
                s.src = 'https://cdn.jsdelivr.net/npm/apexcharts';
                s.onload = resolve;
                s.onerror = reject;
                document.head.appendChild(s);
            });
        }

        // Utility: fetch JSON with graceful fallback
        async function fetchJson(url, fallback) {
            try {
                const res = await fetch(url, { headers: { 'Accept': 'application/json' }});
                if (!res.ok) throw new Error(res.statusText);
                const data = await res.json();
                return data;
            } catch (e) {
                return fallback;
            }
        }

        await ensureApex();

        // -------- Revenue (Last 7 Days) --------
        const summaryPayload = await fetchJson('/admin/api/dashboard/summary', {
            success: false,
            data: {
                revenue_today: 0,
                revenue_week: 0,
                active_sessions: 0,
                packages_total: 0,
                routers_online: 0,
                routers_total: 0,
                transactions_week: 0,
                success_rate_week: 0,
                weekly_revenue: [
                    { label: 'Mon', amount: 0 },
                    { label: 'Tue', amount: 0 },
                    { label: 'Wed', amount: 0 },
                    { label: 'Thu', amount: 0 },
                    { label: 'Fri', amount: 0 },
                    { label: 'Sat', amount: 0 },
                    { label: 'Sun', amount: 0 }
                ]
            }
        });
        const summary = summaryPayload?.data ?? {};
        const revenueData = (summary.weekly_revenue ?? []).map(day => Number(day.amount || 0));
        const revenueLabels = (summary.weekly_revenue ?? []).map(day => day.label || '');
        const resolvedRevenueData = revenueData.length ? revenueData : [0, 0, 0, 0, 0, 0, 0];
        const resolvedRevenueLabels = revenueLabels.length ? revenueLabels : ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];

        const cards = document.querySelectorAll('.small-box .inner h3');
        if (cards.length >= 4) {
            cards[0].textContent = `KES ${Number(summary.revenue_today || 0).toLocaleString()}`;
            cards[1].textContent = Number(summary.active_sessions || 0).toLocaleString();
            cards[2].textContent = Number(summary.packages_total || 0).toLocaleString();
            cards[3].textContent = `${Number(summary.routers_online || 0)}/${Number(summary.routers_total || 0)}`;
        }

        const footerBlocks = document.querySelectorAll('.description-block .description-header');
        if (footerBlocks.length >= 4) {
            footerBlocks[0].textContent = `KES ${Number(summary.revenue_week || 0).toLocaleString()}`;
            footerBlocks[2].textContent = Number(summary.transactions_week || 0).toLocaleString();
            footerBlocks[3].textContent = `${Number(summary.success_rate_week || 0)}%`;
        }

        const revenueMax = Math.max(2000, Math.ceil((Math.max(...resolvedRevenueData) + 1500) / 1000) * 1000);
        new ApexCharts(document.querySelector("#revenueChart"), {
            chart: {
                type: 'area',
                height: 350,
                toolbar: { show: false },
                animations: { enabled: true },
                foreColor: '#E2E8F0',
                dropShadow: { enabled: true, top: 6, left: 0, blur: 12, opacity: 0.15 }
            },
            noData: { text: 'No revenue yet', style: { color: '#E2E8F0' } },
            series: [{ name: 'Revenue (KES)', data: resolvedRevenueData }],
            colors: ['#7DD3FC'],
            dataLabels: { enabled: false },
            stroke: { curve: 'smooth', width: 3 },
            markers: { size: 4, strokeWidth: 0, hover: { size: 6 } },
            xaxis: {
                categories: resolvedRevenueLabels,
                labels: { style: { colors: '#E2E8F0', fontSize: '12px' } },
                axisBorder: { show: false }, axisTicks: { show: false }
            },
            yaxis: {
                min: 0, max: revenueMax, tickAmount: 5, forceNiceScale: true,
                labels: { formatter: val => 'KES ' + val.toLocaleString(), style: { colors: '#E2E8F0', fontSize: '12px' } }
            },
            fill: {
                type: 'gradient',
                gradient: { shadeIntensity: 0.6, opacityFrom: 0.55, opacityTo: 0.08, stops: [0, 90, 100] }
            },
            grid: { borderColor: 'rgba(226, 232, 240, 0.2)', strokeDashArray: 4, padding: { left: 8, right: 8 } },
            tooltip: { theme: 'dark', y: { formatter: val => 'KES ' + val.toLocaleString() } }
        }).render();

        // -------- Package Sales Donut --------
        const packageStatsPayload = await fetchJson('/admin/api/packages/stats', {
            total: Number(summary.packages_total || 0),
            active: Number(summary.packages_total || 0),
        });
        const packageLabels = ['Active Packages', 'Inactive Packages'];
        const activePackages = Number(packageStatsPayload.active || 0);
        const inactivePackages = Math.max(0, Number(packageStatsPayload.total || 0) - activePackages);
        const packageData = [Math.max(activePackages, 0), Math.max(inactivePackages, 0)].map(v => Math.max(1, v));

        new ApexCharts(document.querySelector("#packageChart"), {
            chart: { type: 'donut', height: 300, toolbar: { show: false }, foreColor: '#E2E8F0' },
            noData: { text: 'No package sales yet', style: { color: '#E2E8F0' } },
            series: packageData,
            labels: packageLabels,
            colors: ['#38BDF8', '#22D3EE', '#34D399', '#FBBF24', '#F87171'],
            dataLabels: { enabled: false },
            legend: { position: 'bottom', labels: { colors: '#E2E8F0' }, markers: { width: 10, height: 10, radius: 12 } },
            plotOptions: {
                pie: {
                    donut: {
                        size: '65%',
                        labels: {
                            show: true,
                            name: { color: '#E2E8F0' },
                            value: { color: '#F8FAFC', fontSize: '22px', fontWeight: 700, formatter: val => `${val}%` },
                            total: {
                                show: true,
                                label: 'Total Sales',
                                color: '#CBD5E1',
                                formatter: function() { return packageData.reduce((a,b)=>a+b,0); }
                            }
                        }
                    }
                }
            },
            tooltip: { theme: 'dark' }
        }).render();
    });
</script>
@endpush
