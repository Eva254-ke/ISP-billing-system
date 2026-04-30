@extends('admin.layouts.app')

@section('page-title', 'Dashboard')

@push('styles')
<style>
    .dashboard-bar-chart {
        display: grid;
        grid-template-columns: repeat(7, minmax(0, 1fr));
        gap: 16px;
        align-items: end;
        min-height: 260px;
    }

    .dashboard-bar-chart__column {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 12px;
        min-width: 0;
    }

    .dashboard-bar-chart__value {
        min-height: 20px;
        font-size: 0.75rem;
        color: #6b7280;
        text-align: center;
    }

    .dashboard-bar-chart__track {
        width: 100%;
        max-width: 72px;
        height: 180px;
        padding: 4px;
        display: flex;
        align-items: flex-end;
        background: #f8fafc;
        border: 1px solid #e5e7eb;
        border-radius: 4px;
    }

    .dashboard-bar-chart__bar {
        width: 100%;
        min-height: 8px;
        border-radius: 4px;
        background: #0d6efd;
    }

    .dashboard-bar-chart__label {
        font-size: 0.875rem;
        font-weight: 600;
        color: #111827;
    }

    .dashboard-donut-card {
        display: grid;
        gap: 24px;
        justify-items: center;
    }

    .dashboard-donut-shell {
        position: relative;
        width: 220px;
        height: 220px;
    }

    .dashboard-donut-shell svg {
        width: 100%;
        height: 100%;
        display: block;
    }

    .dashboard-donut__center {
        position: absolute;
        inset: 0;
        display: grid;
        place-items: center;
        text-align: center;
    }

    .dashboard-donut__center strong {
        display: block;
        font-size: 2rem;
        line-height: 1;
    }

    .dashboard-donut__center span {
        display: block;
        margin-top: 8px;
        color: #6b7280;
        font-size: 0.875rem;
    }

    .dashboard-status-legend {
        width: 100%;
        display: grid;
        gap: 12px;
    }

    .dashboard-status-legend__item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 12px;
        padding: 12px 16px;
        border: 1px solid #e5e7eb;
        border-radius: 8px;
    }

    .dashboard-status-legend__meta {
        display: flex;
        align-items: center;
        gap: 12px;
        font-weight: 600;
    }

    .dashboard-status-legend__swatch {
        width: 12px;
        height: 12px;
        border-radius: 4px;
        flex: 0 0 auto;
    }

    .dashboard-table-name {
        font-weight: 600;
        color: #111827;
    }

    .dashboard-table-subtext {
        display: block;
        margin-top: 4px;
        color: #6b7280;
        font-size: 0.75rem;
    }
</style>
@endpush

@section('content')
@php
    $weeklyRevenueCollection = collect($weeklyRevenue ?? []);
    $maxRevenue = (float) max(1, (float) $weeklyRevenueCollection->max('amount'));
    $routerSegments = collect($routerStatusBreakdown ?? [])->values();
    $routerTotal = (int) $routerSegments->sum('count');
    $donutRadius = 42;
    $donutCircumference = 2 * pi() * $donutRadius;
@endphp

<div class="row g-4 mb-4">
    <div class="col-xl-3 col-sm-6">
        <div class="small-box bg-primary position-relative">
            <div class="inner">
                <h3>KES {{ number_format((float) ($stats['revenue_today'] ?? 0), 0) }}</h3>
                <p>Revenue Today</p>
            </div>
            <div class="icon">
                <i class="fas fa-money-bill-wave"></i>
            </div>
            <div class="small-box-footer">Payments recorded today</div>
            <a href="{{ route('admin.payments.index') }}" class="stretched-link" aria-label="Open payments"></a>
        </div>
    </div>

    <div class="col-xl-3 col-sm-6">
        <div class="small-box bg-success position-relative">
            <div class="inner">
                <h3>{{ number_format((int) ($stats['active_sessions'] ?? 0)) }}</h3>
                <p>Active Sessions</p>
            </div>
            <div class="icon">
                <i class="fas fa-wifi"></i>
            </div>
            <div class="small-box-footer">Clients online now</div>
            <a href="{{ route('admin.clients.hotspot') }}" class="stretched-link" aria-label="Open active sessions"></a>
        </div>
    </div>

    <div class="col-xl-3 col-sm-6">
        <div class="small-box bg-warning position-relative">
            <div class="inner">
                <h3>{{ number_format((int) ($stats['packages_total'] ?? 0)) }}</h3>
                <p>Packages</p>
            </div>
            <div class="icon">
                <i class="fas fa-box"></i>
            </div>
            <div class="small-box-footer">Packages on sale</div>
            <a href="{{ route('admin.packages.index') }}" class="stretched-link" aria-label="Open packages"></a>
        </div>
    </div>

    <div class="col-xl-3 col-sm-6">
        <div class="small-box bg-danger position-relative">
            <div class="inner">
                <h3>{{ number_format((int) ($stats['routers_online'] ?? 0)) }}/{{ number_format((int) ($stats['routers_total'] ?? 0)) }}</h3>
                <p>Routers Online</p>
            </div>
            <div class="icon">
                <i class="fas fa-server"></i>
            </div>
            <div class="small-box-footer">Healthy routers only</div>
            <a href="{{ route('admin.routers.index') }}" class="stretched-link" aria-label="Open routers"></a>
        </div>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-xl-8">
        <div class="card card-primary">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-chart-area me-2"></i>
                    Revenue (Last 7 Days)
                </h3>
            </div>
            <div class="card-body">
                @if($weeklyRevenueCollection->isEmpty())
                    <p class="text-muted mb-0">No revenue data is available yet.</p>
                @else
                    <div class="dashboard-bar-chart" aria-label="Revenue for the last 7 days">
                        @foreach($weeklyRevenueCollection as $day)
                            @php
                                $amount = (float) ($day['amount'] ?? 0);
                                $height = $maxRevenue > 0 ? max(8, (int) round(($amount / $maxRevenue) * 100)) : 8;
                            @endphp
                            <div class="dashboard-bar-chart__column">
                                <div class="dashboard-bar-chart__value">KES {{ number_format($amount, 0) }}</div>
                                <div class="dashboard-bar-chart__track">
                                    <div class="dashboard-bar-chart__bar" style="height: {{ $height }}%;"></div>
                                </div>
                                <div class="dashboard-bar-chart__label">{{ $day['label'] ?? '-' }}</div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
            <div class="card-footer">
                <div class="row g-3">
                    <div class="col-sm-3 col-6">
                        <div class="description-block border-end">
                            <h5 class="description-header">KES {{ number_format((float) ($stats['revenue_week'] ?? 0), 0) }}</h5>
                            <span class="description-text">This Week</span>
                        </div>
                    </div>
                    <div class="col-sm-3 col-6">
                        <div class="description-block border-end">
                            <h5 class="description-header">KES {{ number_format((float) (($stats['revenue_week'] ?? 0) / 7), 0) }}</h5>
                            <span class="description-text">Daily Average</span>
                        </div>
                    </div>
                    <div class="col-sm-3 col-6">
                        <div class="description-block border-end">
                            <h5 class="description-header">{{ number_format((int) ($transactionsWeek ?? 0)) }}</h5>
                            <span class="description-text">Transactions</span>
                        </div>
                    </div>
                    <div class="col-sm-3 col-6">
                        <div class="description-block">
                            <h5 class="description-header">{{ number_format((int) ($successRateWeek ?? 0)) }}%</h5>
                            <span class="description-text">Success Rate</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-4">
        <div class="card card-primary h-100">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-chart-pie me-2"></i>
                    Router Status Breakdown
                </h3>
            </div>
            <div class="card-body">
                <div class="dashboard-donut-card">
                    <div class="dashboard-donut-shell">
                        <svg viewBox="0 0 120 120" role="img" aria-label="Router status breakdown">
                            <circle cx="60" cy="60" r="{{ $donutRadius }}" fill="none" stroke="#e5e7eb" stroke-width="16"></circle>
                            @if($routerTotal > 0)
                                @php $offset = 0.0; @endphp
                                @foreach($routerSegments as $segment)
                                    @continue(($segment['count'] ?? 0) <= 0)
                                    @php
                                        $segmentLength = ((int) $segment['count'] / $routerTotal) * $donutCircumference;
                                        $dashArray = number_format($segmentLength, 2, '.', '') . ' ' . number_format(max($donutCircumference - $segmentLength, 0), 2, '.', '');
                                        $dashOffset = number_format(-$offset, 2, '.', '');
                                        $offset += $segmentLength;
                                    @endphp
                                    <circle
                                        cx="60"
                                        cy="60"
                                        r="{{ $donutRadius }}"
                                        fill="none"
                                        stroke="{{ $segment['color'] }}"
                                        stroke-width="16"
                                        stroke-dasharray="{{ $dashArray }}"
                                        stroke-dashoffset="{{ $dashOffset }}"
                                        transform="rotate(-90 60 60)"
                                    ></circle>
                                @endforeach
                            @endif
                        </svg>
                        <div class="dashboard-donut__center">
                            <div>
                                <strong>{{ number_format($routerTotal) }}</strong>
                                <span>Routers tracked</span>
                            </div>
                        </div>
                    </div>

                    <div class="dashboard-status-legend">
                        @foreach($routerSegments as $segment)
                            <div class="dashboard-status-legend__item">
                                <div class="dashboard-status-legend__meta">
                                    <span class="dashboard-status-legend__swatch" style="background-color: {{ $segment['color'] }};"></span>
                                    <span>{{ $segment['label'] }}</span>
                                </div>
                                <strong>{{ number_format((int) ($segment['count'] ?? 0)) }}</strong>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
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
                <table class="table table-hover table-striped mb-0">
                    <thead>
                        <tr>
                            <th>Time</th>
                            <th>Payer</th>
                            <th>Package</th>
                            <th>Amount</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse(($recentPayments ?? collect())->take(5) as $payment)
                            @php
                                $status = strtolower((string) ($payment->status ?? 'unknown'));
                                $statusClass = match ($status) {
                                    'completed', 'confirmed', 'activated' => 'bg-success',
                                    'pending' => 'bg-warning text-dark',
                                    'failed' => 'bg-danger',
                                    default => 'bg-secondary',
                                };
                                $paymentPhone = trim((string) ($payment->phone ?: $payment->mpesa_phone ?: ''));
                            @endphp
                            <tr>
                                <td>{{ optional($payment->created_at)->format('h:i A') ?? '-' }}</td>
                                <td>
                                    <span class="dashboard-table-name">{{ $payment->display_customer_name }}</span>
                                    @if($paymentPhone !== '' && $paymentPhone !== $payment->display_customer_name)
                                        <span class="dashboard-table-subtext">{{ $paymentPhone }}</span>
                                    @endif
                                </td>
                                <td>{{ $payment->package_name ?? optional($payment->package)->name ?? '-' }}</td>
                                <td>KES {{ number_format((float) ($payment->amount ?? 0), 0) }}</td>
                                <td><span class="badge {{ $statusClass }}">{{ ucfirst($status) }}</span></td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted py-3">No recent payments.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card">
            <div class="card-header border-0">
                <h3 class="card-title">
                    <i class="fas fa-server me-2"></i>
                    Router List
                </h3>
                <div class="card-tools">
                    <a href="{{ route('admin.routers.index') }}" class="btn btn-sm btn-primary">
                        Manage
                    </a>
                </div>
            </div>
            <div class="card-body">
                <ul class="products-list product-list-in-card ps-2 pe-2 mb-0">
                    @forelse(($routerStatuses ?? collect())->take(6) as $router)
                        @php
                            $status = strtolower((string) ($router->status ?? 'unknown'));
                            [$iconClass, $badgeClass] = match ($status) {
                                'online' => ['text-success', 'bg-success'],
                                'warning' => ['text-warning', 'bg-warning text-dark'],
                                'offline' => ['text-danger', 'bg-danger'],
                                default => ['text-secondary', 'bg-secondary'],
                            };
                        @endphp
                        <li class="item">
                            <div class="product-img">
                                <i class="fas fa-circle {{ $iconClass }} fa-2x"></i>
                            </div>
                            <div class="product-info">
                                <a href="{{ route('admin.routers.show', $router) }}" class="product-title">
                                    {{ $router->name }}
                                    <span class="badge {{ $badgeClass }} float-end">{{ ucfirst($status) }}</span>
                                </a>
                                <span class="product-description">
                                    {{ $router->ip_address ?: 'No IP saved' }} | {{ number_format((int) ($router->active_sessions ?? 0)) }} users
                                </span>
                            </div>
                        </li>
                    @empty
                        <li class="item text-muted">No routers configured.</li>
                    @endforelse
                </ul>
            </div>
        </div>
    </div>
</div>
@endsection
