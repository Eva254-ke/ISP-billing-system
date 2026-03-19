@extends('admin.layouts.app')

@section('page-title', 'Dashboard')

@section('content')
<!-- Stats Cards Row -->
<div class="row">
    <!-- Revenue Today -->
    <div class="col-lg-3 col-6">
        <div class="small-box bg-primary">
            <div class="inner">
                <h3>KES 12,500</h3>
                <p>Revenue Today</p>
            </div>
            <div class="icon">
                <i class="fas fa-money-bill-wave"></i>
            </div>
            <a href="{{ route('admin.payments.index') }}" class="small-box-footer">
                View Details <i class="fas fa-arrow-circle-right"></i>
            </a>
        </div>
    </div>

    <!-- Active Sessions -->
    <div class="col-lg-3 col-6">
        <div class="small-box bg-success">
            <div class="inner">
                <h3>234</h3>
                <p>Active Sessions</p>
            </div>
            <div class="icon">
                <i class="fas fa-wifi"></i>
            </div>
            <a href="{{ route('admin.clients.hotspot') }}" class="small-box-footer">
                View Clients <i class="fas fa-arrow-circle-right"></i>
            </a>
        </div>
    </div>

    <!-- Total Packages -->
    <div class="col-lg-3 col-6">
        <div class="small-box bg-warning">
            <div class="inner">
                <h3>12</h3>
                <p>Packages</p>
            </div>
            <div class="icon">
                <i class="fas fa-box"></i>
            </div>
            <a href="{{ route('admin.packages.index') }}" class="small-box-footer">
                Manage <i class="fas fa-arrow-circle-right"></i>
            </a>
        </div>
    </div>

    <!-- Routers Online -->
    <div class="col-lg-3 col-6">
        <div class="small-box bg-danger">
            <div class="inner">
                <h3>3/4</h3>
                <p>Routers Online</p>
            </div>
            <div class="icon">
                <i class="fas fa-server"></i>
            </div>
            <a href="{{ route('admin.routers.index') }}" class="small-box-footer">
                Check Status <i class="fas fa-arrow-circle-right"></i>
            </a>
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
    document.addEventListener('DOMContentLoaded', function () {
        // Revenue Chart (Area)
        const revenueData = [5000, 8000, 6000, 12000, 9000, 15000, 12500];
        const revenueMax = Math.ceil((Math.max(...revenueData) + 1500) / 1000) * 1000;
        var revenueOptions = {
            chart: {
                type: 'area',
                height: 350,
                toolbar: { show: false },
                animations: { enabled: true },
                foreColor: '#E2E8F0',
                dropShadow: {
                    enabled: true,
                    top: 6,
                    left: 0,
                    blur: 12,
                    opacity: 0.15
                }
            },
            series: [{
                name: 'Revenue (KES)',
                data: revenueData
            }],
            colors: ['#7DD3FC'],
            dataLabels: { enabled: false },
            stroke: { curve: 'smooth', width: 3 },
            markers: { size: 4, strokeWidth: 0, hover: { size: 6 } },
            xaxis: {
                categories: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
                labels: { style: { colors: '#E2E8F0', fontSize: '12px' } },
                axisBorder: { show: false },
                axisTicks: { show: false },
                tooltip: { enabled: false }
            },
            yaxis: {
                min: 0,
                max: revenueMax,
                tickAmount: 5,
                forceNiceScale: true,
                labels: {
                    formatter: function(val) { return 'KES ' + val.toLocaleString(); },
                    style: { colors: '#E2E8F0', fontSize: '12px' }
                }
            },
            fill: {
                type: 'gradient',
                gradient: {
                    shadeIntensity: 0.6,
                    opacityFrom: 0.55,
                    opacityTo: 0.08,
                    stops: [0, 90, 100]
                }
            },
            grid: {
                borderColor: 'rgba(226, 232, 240, 0.2)',
                strokeDashArray: 4,
                padding: { left: 8, right: 8 }
            },
            tooltip: {
                theme: 'dark',
                y: { formatter: val => 'KES ' + val.toLocaleString() }
            }
        };
        new ApexCharts(document.querySelector("#revenueChart"), revenueOptions).render();

        // Package Distribution Chart (Donut)
        var packageOptions = {
            chart: {
                type: 'donut',
                height: 300,
                toolbar: { show: false },
                foreColor: '#E2E8F0'
            },
            series: [45, 25, 15, 10, 5],
            labels: ['1 Hour', '3 Hours', '24 Hours', 'Weekly', 'Monthly'],
            colors: ['#38BDF8', '#22D3EE', '#34D399', '#FBBF24', '#F87171'],
            dataLabels: { enabled: false },
            legend: {
                position: 'bottom',
                labels: { colors: '#E2E8F0' },
                markers: { width: 10, height: 10, radius: 12 }
            },
            plotOptions: {
                pie: {
                    donut: {
                        size: '65%',
                        labels: {
                            show: true,
                            name: { color: '#E2E8F0' },
                            value: {
                                color: '#F8FAFC',
                                fontSize: '22px',
                                fontWeight: 700,
                                formatter: val => `${val}%`
                            },
                            total: {
                                show: true,
                                label: 'Total Sales',
                                color: '#CBD5E1',
                                formatter: function() { return '412'; }
                            }
                        }
                    }
                }
            },
            tooltip: { theme: 'dark' }
        };
        new ApexCharts(document.querySelector("#packageChart"), packageOptions).render();
    });
</script>
@endpush
