<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'CloudBridge Networks') }} - Admin</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet" />

    <!-- Vite Assets -->
    @include('partials.vite-assets', ['entries' => ['resources/css/app.css', 'resources/js/app.js']])
    <link rel="stylesheet" href="{{ asset('css/admin-overrides.css') }}?v=20260416-6">
    <!-- Font Awesome CDN fallback (prevents missing icons showing as squares if local build assets fail) -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer">
    @stack('styles')
    <style>
        :root {
            --cb-space-4: 4px;
            --cb-space-8: 8px;
            --cb-space-12: 12px;
            --cb-space-16: 16px;
            --cb-space-24: 24px;
            --cb-space-32: 32px;
            --cb-space-48: 48px;
            --cb-space-64: 64px;
            --cb-space-96: 96px;
            --cb-radius-control: 4px;
            --cb-radius-card: 8px;
        }

        @keyframes admin-page-fade {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        body.cb-admin-shell {
            font-family: 'Inter', sans-serif !important;
            opacity: 0;
            animation: admin-page-fade 300ms ease forwards !important;
        }

        body.cb-admin-shell *,
        body.cb-admin-shell *::before,
        body.cb-admin-shell *::after {
            box-shadow: none !important;
            backdrop-filter: none !important;
            transition: none !important;
            animation: none !important;
            text-shadow: none !important;
            scroll-behavior: auto !important;
        }

        body.cb-admin-shell h1,
        body.cb-admin-shell h2,
        body.cb-admin-shell h3,
        body.cb-admin-shell h4,
        body.cb-admin-shell h5,
        body.cb-admin-shell h6,
        body.cb-admin-shell p,
        body.cb-admin-shell span,
        body.cb-admin-shell a,
        body.cb-admin-shell button,
        body.cb-admin-shell input,
        body.cb-admin-shell select,
        body.cb-admin-shell textarea,
        body.cb-admin-shell table,
        body.cb-admin-shell th,
        body.cb-admin-shell td,
        body.cb-admin-shell label,
        body.cb-admin-shell small,
        body.cb-admin-shell li {
            font-family: 'Inter', sans-serif !important;
        }

        body.cb-admin-shell .main-header,
        body.cb-admin-shell .main-sidebar,
        body.cb-admin-shell .sidebar,
        body.cb-admin-shell .content-wrapper,
        body.cb-admin-shell .main-footer,
        body.cb-admin-shell .card,
        body.cb-admin-shell .small-box,
        body.cb-admin-shell .btn,
        body.cb-admin-shell .dropdown-menu,
        body.cb-admin-shell .modal-content,
        body.cb-admin-shell .alert,
        body.cb-admin-shell .shell-brand__mark,
        body.cb-admin-shell .shell-search,
        body.cb-admin-shell .navbar-user-menu .nav-link,
        body.cb-admin-shell .sidebar-panel,
        body.cb-admin-shell .nav-sidebar > .nav-item > .nav-link,
        body.cb-admin-shell .card-header,
        body.cb-admin-shell .card-footer,
        body.cb-admin-shell .small-box-footer,
        body.cb-admin-shell .badge {
            background-image: none !important;
            filter: none !important;
        }

        body.cb-admin-shell .card,
        body.cb-admin-shell .small-box,
        body.cb-admin-shell .dropdown-menu,
        body.cb-admin-shell .modal-content,
        body.cb-admin-shell .alert,
        body.cb-admin-shell .sidebar,
        body.cb-admin-shell .main-header,
        body.cb-admin-shell .content-wrapper,
        body.cb-admin-shell .table-responsive,
        body.cb-admin-shell .main-footer,
        body.cb-admin-shell .sidebar-panel {
            border-radius: var(--cb-radius-card) !important;
        }

        body.cb-admin-shell .btn,
        body.cb-admin-shell .form-control,
        body.cb-admin-shell .form-select,
        body.cb-admin-shell .input-group-text,
        body.cb-admin-shell .nav-link,
        body.cb-admin-shell .page-link,
        body.cb-admin-shell .badge,
        body.cb-admin-shell .btn-close,
        body.cb-admin-shell .dropdown-item {
            border-radius: var(--cb-radius-control) !important;
        }

        body.cb-admin-shell .container-fluid {
            padding-left: var(--cb-space-24) !important;
            padding-right: var(--cb-space-24) !important;
        }

        body.cb-admin-shell .card-header,
        body.cb-admin-shell .card-body,
        body.cb-admin-shell .card-footer,
        body.cb-admin-shell .modal-header,
        body.cb-admin-shell .modal-body,
        body.cb-admin-shell .modal-footer,
        body.cb-admin-shell .alert,
        body.cb-admin-shell .small-box .inner,
        body.cb-admin-shell .main-footer {
            padding: var(--cb-space-16) var(--cb-space-24) !important;
        }

        body.cb-admin-shell .content-header {
            padding: var(--cb-space-24) 0 var(--cb-space-16) !important;
        }

        body.cb-admin-shell .content {
            padding-bottom: var(--cb-space-24) !important;
        }

        body.cb-admin-shell .shell-toggle,
        body.cb-admin-shell .navbar-user-menu .nav-link,
        body.cb-admin-shell .nav-sidebar > .nav-item > .nav-link,
        body.cb-admin-shell .shell-brand__mark {
            border-radius: var(--cb-radius-control) !important;
        }

        body.cb-admin-shell .shell-brand__mark {
            background-color: #0b57d0 !important;
        }

        body.cb-admin-shell .main-sidebar,
        body.cb-admin-shell .sidebar {
            background-color: #0f172a !important;
        }

        body.cb-admin-shell .sidebar {
            border-right: 1px solid #1e293b !important;
        }

        body.cb-admin-shell .sidebar-brand__link {
            color: #f8fafc !important;
        }

        body.cb-admin-shell .sidebar-brand__name {
            color: #f8fafc !important;
        }

        body.cb-admin-shell .sidebar-brand__sub {
            color: #94a3b8 !important;
        }

        body.cb-admin-shell .nav-sidebar > .nav-item > .nav-link,
        body.cb-admin-shell .nav-treeview .nav-link {
            color: #cbd5e1 !important;
            background-color: transparent !important;
        }

        body.cb-admin-shell .nav-sidebar > .nav-item > .nav-link:hover,
        body.cb-admin-shell .nav-sidebar > .nav-item > .nav-link:focus,
        body.cb-admin-shell .nav-treeview .nav-link:hover,
        body.cb-admin-shell .nav-treeview .nav-link:focus {
            color: #ffffff !important;
            background-color: #1e293b !important;
        }

        body.cb-admin-shell .nav-sidebar > .nav-item > .nav-link.active,
        body.cb-admin-shell .nav-treeview .nav-link.active {
            color: #ffffff !important;
            background-color: #1d4ed8 !important;
        }

        body.cb-admin-shell .nav-sidebar > .nav-item > .nav-link p,
        body.cb-admin-shell .nav-sidebar > .nav-item > .nav-link p span,
        body.cb-admin-shell .nav-treeview .nav-link p,
        body.cb-admin-shell .nav-treeview .nav-link p span {
            color: inherit !important;
        }

        body.cb-admin-shell .sidebar-panel {
            background-color: #ffffff !important;
        }

        body.cb-admin-shell .card-header,
        body.cb-admin-shell .card-title,
        body.cb-admin-shell .card-body,
        body.cb-admin-shell .card-footer,
        body.cb-admin-shell .table,
        body.cb-admin-shell .table thead th,
        body.cb-admin-shell .table tbody td,
        body.cb-admin-shell .modal-title,
        body.cb-admin-shell .form-label {
            color: #0f172a !important;
        }

        body.cb-admin-shell .text-muted {
            color: #475569 !important;
        }

        body.cb-admin-shell .form-control,
        body.cb-admin-shell .form-select,
        body.cb-admin-shell .input-group-text {
            border-color: #cbd5e1 !important;
            background-color: #ffffff !important;
            color: #0f172a !important;
        }

        body.cb-admin-shell .form-control::placeholder,
        body.cb-admin-shell .form-select::placeholder,
        body.cb-admin-shell textarea.form-control::placeholder {
            color: #64748b !important;
        }

        body.cb-admin-shell .input-group-text,
        body.cb-admin-shell .card.bg-light,
        body.cb-admin-shell .bg-light {
            background-color: #f8fafc !important;
            color: #0f172a !important;
        }

        body.cb-admin-shell .form-check-label {
            color: #0f172a !important;
        }

        body.cb-admin-shell .form-check-input {
            border-color: #94a3b8 !important;
        }

        body.cb-admin-shell .form-check-input:checked {
            background-color: #0d6efd !important;
            border-color: #0d6efd !important;
        }

        body.cb-admin-shell :not(pre) > code {
            background-color: #f8fafc !important;
            color: #0f172a !important;
            border: 1px solid #dbe3ef !important;
            border-radius: var(--cb-radius-control) !important;
            padding: 2px 6px !important;
        }

        body.cb-admin-shell .small-box.bg-primary,
        body.cb-admin-shell .small-box.bg-success,
        body.cb-admin-shell .small-box.bg-danger {
            color: #ffffff !important;
        }

        body.cb-admin-shell .small-box.bg-warning {
            color: #111827 !important;
        }

        body.cb-admin-shell .small-box .inner h3,
        body.cb-admin-shell .small-box .inner p,
        body.cb-admin-shell .small-box .small-box-footer {
            color: inherit !important;
        }

        body.cb-admin-shell .small-box .icon,
        body.cb-admin-shell .small-box .icon i {
            color: inherit !important;
        }

        body.cb-admin-shell .card-primary {
            background-color: #ffffff !important;
            border-color: #dbe3ef !important;
            color: #0f172a !important;
        }

        body.cb-admin-shell .card-primary .card-header {
            background-color: #0d6efd !important;
            color: #ffffff !important;
            border-bottom-color: #0b5ed7 !important;
        }

        body.cb-admin-shell .card-primary .card-title,
        body.cb-admin-shell .card-primary .card-header .btn,
        body.cb-admin-shell .card-primary .card-header .btn i {
            color: #ffffff !important;
        }

        body.cb-admin-shell .card-primary .card-body,
        body.cb-admin-shell .card-primary .card-footer,
        body.cb-admin-shell .card-primary .description-block,
        body.cb-admin-shell .card-primary .description-header,
        body.cb-admin-shell .card-primary .description-text,
        body.cb-admin-shell .card-primary .dashboard-bar-chart__value,
        body.cb-admin-shell .card-primary .dashboard-donut__center strong,
        body.cb-admin-shell .card-primary .dashboard-status-legend__item,
        body.cb-admin-shell .card-primary .dashboard-status-legend__meta,
        body.cb-admin-shell .card-primary .dashboard-status-legend__item strong,
        body.cb-admin-shell .card-primary .dashboard-status-legend__item span {
            color: #0f172a !important;
        }

        body.cb-admin-shell .card-primary .card-body,
        body.cb-admin-shell .card-primary .card-footer {
            background-color: #ffffff !important;
        }

        body.cb-admin-shell .card-primary .description-text,
        body.cb-admin-shell .card-primary .dashboard-donut__center span {
            color: #475569 !important;
        }

        body.cb-admin-shell .alert-success {
            background-color: #dcfce7 !important;
            color: #166534 !important;
            border: 1px solid #86efac !important;
        }

        body.cb-admin-shell .alert-danger {
            background-color: #fee2e2 !important;
            color: #991b1b !important;
            border: 1px solid #fca5a5 !important;
        }

        body.cb-admin-shell .alert-warning {
            background-color: #fef3c7 !important;
            color: #92400e !important;
            border: 1px solid #fcd34d !important;
        }

        body.cb-admin-shell .alert-info {
            background-color: #dbeafe !important;
            color: #1d4ed8 !important;
            border: 1px solid #93c5fd !important;
        }

        body.cb-admin-shell .toast,
        body.cb-admin-shell .toast-header,
        body.cb-admin-shell .toast-body {
            background-color: #ffffff !important;
            color: #0f172a !important;
            border-color: #dbe3ef !important;
        }

        body.cb-admin-shell .swal2-popup {
            background: #ffffff !important;
            color: #0f172a !important;
            border: 1px solid #dbe3ef !important;
            border-radius: var(--cb-radius-card) !important;
        }

        body.cb-admin-shell .swal2-title,
        body.cb-admin-shell .swal2-html-container,
        body.cb-admin-shell .swal2-content,
        body.cb-admin-shell .swal2-actions,
        body.cb-admin-shell .swal2-footer {
            color: #0f172a !important;
        }

        body.cb-admin-shell .swal2-html-container .text-muted,
        body.cb-admin-shell .swal2-footer,
        body.cb-admin-shell .swal2-validation-message {
            color: #475569 !important;
        }

        body.cb-admin-shell .swal2-confirm,
        body.cb-admin-shell .swal2-cancel,
        body.cb-admin-shell .swal2-deny {
            border-radius: var(--cb-radius-control) !important;
            box-shadow: none !important;
        }

        body.cb-admin-shell .table thead th {
            white-space: nowrap !important;
            word-break: normal !important;
            overflow-wrap: normal !important;
        }

        body.cb-admin-shell .table tbody td {
            word-break: normal !important;
            overflow-wrap: break-word !important;
        }

        body.cb-admin-shell .table td code,
        body.cb-admin-shell .table td strong,
        body.cb-admin-shell .table td small,
        body.cb-admin-shell .table td .badge,
        body.cb-admin-shell .table td .btn-group {
            word-break: normal !important;
            overflow-wrap: normal !important;
        }

        body.cb-admin-shell .table td code {
            white-space: nowrap !important;
        }

        body.cb-admin-shell #paymentsPage .table thead th:nth-child(2),
        body.cb-admin-shell #paymentsPage .table tbody td:nth-child(2) {
            min-width: 124px;
        }

        body.cb-admin-shell #paymentsPage .table thead th:nth-child(3),
        body.cb-admin-shell #paymentsPage .table tbody td:nth-child(3) {
            min-width: 140px;
            white-space: nowrap !important;
        }

        body.cb-admin-shell #paymentsPage .table thead th:nth-child(4),
        body.cb-admin-shell #paymentsPage .table tbody td:nth-child(4) {
            min-width: 156px;
        }

        body.cb-admin-shell #paymentsPage .table thead th:nth-child(5),
        body.cb-admin-shell #paymentsPage .table tbody td:nth-child(5) {
            min-width: 112px;
        }

        body.cb-admin-shell #paymentsPage .table thead th:nth-child(7),
        body.cb-admin-shell #paymentsPage .table tbody td:nth-child(7) {
            min-width: 164px;
        }

        body.cb-admin-shell .nav-tabs {
            gap: var(--cb-space-8);
            border-bottom: 1px solid #dbe3ef !important;
            padding: 0 var(--cb-space-24);
        }

        body.cb-admin-shell .nav-tabs .nav-item {
            margin-bottom: -1px;
        }

        body.cb-admin-shell .nav-tabs .nav-link {
            border: 1px solid transparent !important;
            color: #475569 !important;
            background-color: transparent !important;
            padding: var(--cb-space-12) var(--cb-space-16) !important;
            font-weight: 600 !important;
        }

        body.cb-admin-shell .nav-tabs .nav-link:hover,
        body.cb-admin-shell .nav-tabs .nav-link:focus {
            color: #0f172a !important;
            border-color: #dbe3ef !important;
            background-color: #f8fafc !important;
        }

        body.cb-admin-shell .nav-tabs .nav-link.active {
            color: #0f172a !important;
            background-color: #eff6ff !important;
            border-color: #bfdbfe #bfdbfe #eff6ff !important;
        }

        body.cb-admin-shell .tab-content > .tab-pane {
            padding-top: var(--cb-space-24);
        }

        body.cb-admin-shell .cb-page-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: var(--cb-space-16);
            margin-bottom: var(--cb-space-24);
        }

        body.cb-admin-shell .cb-page-heading h2 {
            margin: 0;
            color: #0f172a;
            font-size: 28px;
            line-height: 1.2;
        }

        body.cb-admin-shell .cb-page-heading p {
            margin: var(--cb-space-8) 0 0;
            color: #475569;
            max-width: 720px;
            font-size: 14px;
            line-height: 1.5;
        }

        body.cb-admin-shell .cb-page-actions,
        body.cb-admin-shell .cb-stacked-actions,
        body.cb-admin-shell .cb-radio-row {
            display: flex;
            flex-wrap: wrap;
            gap: var(--cb-space-8);
        }

        body.cb-admin-shell .cb-page-actions {
            justify-content: flex-end;
        }

        body.cb-admin-shell .cb-stacked-actions {
            align-items: center;
            justify-content: flex-end;
            margin-top: var(--cb-space-24);
        }

        body.cb-admin-shell .cb-card-grid,
        body.cb-admin-shell .cb-metric-grid {
            display: grid;
            gap: var(--cb-space-16);
        }

        body.cb-admin-shell .cb-card-grid {
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
        }

        body.cb-admin-shell .cb-metric-grid {
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
        }

        body.cb-admin-shell .cb-section-card,
        body.cb-admin-shell .cb-metric-card {
            background: #ffffff;
            border: 1px solid #dbe3ef;
            border-radius: var(--cb-radius-card);
        }

        body.cb-admin-shell .cb-section-card {
            padding: var(--cb-space-24);
            height: 100%;
        }

        body.cb-admin-shell .cb-section-card--compact {
            padding: var(--cb-space-16);
        }

        body.cb-admin-shell .cb-metric-card {
            padding: var(--cb-space-16);
            min-height: 100%;
        }

        body.cb-admin-shell .cb-section-title {
            margin: 0;
            color: #0f172a;
            font-size: 16px;
            font-weight: 600;
            line-height: 1.4;
        }

        body.cb-admin-shell .cb-section-copy {
            margin: var(--cb-space-8) 0 0;
            color: #475569;
            font-size: 14px;
            line-height: 1.5;
        }

        body.cb-admin-shell .cb-metric-label {
            color: #475569;
            font-size: 12px;
            font-weight: 600;
            letter-spacing: 0.04em;
            text-transform: uppercase;
        }

        body.cb-admin-shell .cb-metric-value {
            margin-top: var(--cb-space-8);
            color: #0f172a;
            font-size: 24px;
            font-weight: 700;
            line-height: 1.2;
        }

        body.cb-admin-shell .cb-metric-note,
        body.cb-admin-shell .cb-field-note {
            display: block;
            margin-top: var(--cb-space-8);
            color: #475569;
            font-size: 13px;
            line-height: 1.5;
        }

        body.cb-admin-shell .cb-divider {
            border-top: 1px solid #dbe3ef;
            margin: var(--cb-space-24) 0;
        }

        @media (max-width: 767.98px) {
            body.cb-admin-shell .cb-page-header {
                flex-direction: column;
            }

            body.cb-admin-shell .cb-page-actions,
            body.cb-admin-shell .cb-stacked-actions {
                width: 100%;
                justify-content: flex-start;
            }

            body.cb-admin-shell .nav-tabs {
                padding-left: var(--cb-space-16);
                padding-right: var(--cb-space-16);
            }
        }
    </style>
</head>
<body class="hold-transition sidebar-mini layout-fixed layout-navbar-fixed cb-admin-shell">
    @php
        $currentUser = auth()->user();
        $displayName = trim((string) ($currentUser?->full_name ?: $currentUser?->name ?: $currentUser?->email ?: 'Account'));
        $roleLabel = $currentUser?->role_label ?: 'Administrator';
        $tenantName = $currentUser?->tenant?->name;
        $sidebarBrand = config('app.name', 'CloudBridge Networks');
    @endphp
    <div class="wrapper">

        <!-- Navbar -->
        <nav class="main-header navbar navbar-expand border-bottom">
            <div class="shell-header-start">
                <button class="nav-link shell-toggle" id="sidebarToggle" type="button" aria-label="Toggle navigation" aria-controls="mainSidebar" aria-expanded="true">
                    <i class="fas fa-bars"></i>
                </button>

                <a href="{{ route('admin.dashboard') }}" class="shell-brand" aria-label="CloudBridge Networks">
                    <span class="shell-brand__mark">
                        <i class="fas fa-cloud"></i>
                    </span>
                    <span class="shell-brand__copy">
                        <span class="shell-brand__name">CloudBridge</span>
                        <span class="shell-brand__sub">Networks</span>
                    </span>
                </a>
            </div>

            <div class="shell-header-end">
                <ul class="navbar-nav align-items-center navbar-user-menu">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle js-user-dropdown-toggle" id="userMenuDropdown" data-bs-toggle="dropdown" data-toggle="dropdown" href="#" role="button" aria-haspopup="true" aria-expanded="false">
                            <i class="far fa-user-circle me-1"></i>
                            <span class="d-none d-sm-inline">{{ $displayName }}</span>
                        </a>
                        <div class="dropdown-menu dropdown-menu-end dropdown-menu-right" aria-labelledby="userMenuDropdown">
                            <div class="dropdown-item-text navbar-user-menu__summary">
                                <strong class="navbar-user-menu__name">{{ $displayName }}</strong>
                                <span class="navbar-user-menu__meta">{{ $roleLabel }}</span>
                                @if($tenantName)
                                    <span class="navbar-user-menu__meta">{{ $tenantName }}</span>
                                @endif
                            </div>
                            <div class="dropdown-divider"></div>
                            <a href="{{ route('logout') }}" class="dropdown-item"
                               onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                                <i class="fas fa-sign-out-alt me-2"></i> Logout
                            </a>
                            <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
                                @csrf
                            </form>
                        </div>
                    </li>
                </ul>
            </div>
        </nav>
        <!-- /.navbar -->

        <!-- Sidebar (Below Navbar, Collapsible) -->
        <aside class="main-sidebar sidebar-dark-primary elevation-4" id="mainSidebar">
            <div class="sidebar">
                <div class="sidebar-brand">
                    <a href="{{ route('admin.dashboard') }}" class="sidebar-brand__link" aria-label="{{ $sidebarBrand }}">
                        <span class="sidebar-brand__name">{{ $sidebarBrand }}</span>
                        <span class="sidebar-brand__sub">Admin Console</span>
                    </a>
                </div>

                <nav class="mt-2 sidebar-nav-wrap">
                    <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu">
                        <li class="nav-item">
                            <a href="{{ route('admin.dashboard') }}" 
                               class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                                <p class="mb-0"><span>Dashboard</span></p>
                            </a>
                        </li>

                        <li class="nav-item">
                            <a href="{{ route('admin.routers.index') }}" 
                               class="nav-link {{ request()->routeIs('admin.routers.*') ? 'active' : '' }}">
                                <p class="mb-0"><span>Routers</span></p>
                            </a>
                        </li>

                        <li class="nav-item">
                            <a href="{{ route('admin.packages.index') }}" 
                               class="nav-link {{ request()->routeIs('admin.packages.*') ? 'active' : '' }}">
                                <p class="mb-0"><span>Packages</span></p>
                            </a>
                        </li>

                        <li class="nav-item">
                            <a href="{{ route('admin.vouchers.index') }}" 
                               class="nav-link {{ request()->routeIs('admin.vouchers.*') ? 'active' : '' }}">
                                <p class="mb-0"><span>Vouchers</span></p>
                            </a>
                        </li>

                        <li class="nav-item">
                            <a href="{{ route('admin.payments.index') }}" 
                               class="nav-link {{ request()->routeIs('admin.payments.*') ? 'active' : '' }}">
                                <p class="mb-0"><span>Payments</span></p>
                            </a>
                        </li>

                        <li class="nav-item">
                            <a href="{{ route('admin.logs.index') }}"
                               class="nav-link {{ request()->routeIs('admin.logs.*') ? 'active' : '' }}">
                                <p class="mb-0"><span>Logs</span></p>
                            </a>
                        </li>

                        <li class="nav-item has-treeview {{ request()->is('admin/clients*') ? 'menu-open' : '' }}">
                            <a href="{{ route('admin.clients.hotspot') }}" class="nav-link {{ request()->is('admin/clients*') ? 'active' : '' }}" aria-expanded="{{ request()->is('admin/clients*') ? 'true' : 'false' }}">
                                <p>
                                    <span>Clients</span>
                                </p>
                            </a>
                            <ul class="nav nav-treeview">
                                <li class="nav-item">
                                    <a href="{{ route('admin.clients.hotspot') }}" 
                                       class="nav-link {{ request()->routeIs('admin.clients.hotspot') ? 'active' : '' }}">
                                        <p class="mb-0"><span>Hotspot Clients</span></p>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="{{ route('admin.clients.pppoe') }}" 
                                       class="nav-link {{ request()->routeIs('admin.clients.pppoe') ? 'active' : '' }}">
                                        <p class="mb-0"><span>PPPoE Clients</span></p>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="{{ route('admin.clients.customers') }}" 
                                       class="nav-link {{ request()->routeIs('admin.clients.customers') ? 'active' : '' }}">
                                        <p class="mb-0"><span>All Customers</span></p>
                                    </a>
                                </li>
                            </ul>
                        </li>

                        <li class="nav-item">
                            <a href="{{ route('admin.settings') }}" 
                               class="nav-link {{ request()->routeIs('admin.settings') ? 'active' : '' }}">
                                <p class="mb-0"><span>Settings</span></p>
                            </a>
                        </li>
                    </ul>
                </nav>
            </div>
        </aside>

        <!-- Content Wrapper -->
        <div class="content-wrapper" id="contentWrapper">
            <!-- Content Header -->
            <div class="content-header">
                <div class="container-fluid">
                    <div class="row mb-2">
                        <div class="col-sm-6">
                            <h1 class="m-0">@yield('page-title', 'Dashboard')</h1>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Main Content -->
            <section class="content">
                <div class="container-fluid">
                    @if(session('success'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            {{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    @if(session('error'))
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            {{ session('error') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    @yield('content')
                </div>
            </section>
        </div>

        <!-- Footer -->
        <footer class="main-footer">
            <strong>Copyright &copy; {{ date('Y') }} <a href="#">CloudBridge Networks</a>.</strong>
            All rights reserved.
            <div class="float-end d-none d-sm-inline-block">
                <b>Version</b> 1.0.0
            </div>
        </footer>

    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var toggle = document.querySelector('.js-user-dropdown-toggle');
            var hasBs5 = window.bootstrap && window.bootstrap.Dropdown;
            var hasBs4 = window.jQuery && window.jQuery.fn && window.jQuery.fn.dropdown;
            if (toggle && !(hasBs5 || hasBs4)) {
                var menu = toggle.nextElementSibling;
                var closeMenu = function () {
                    if (!menu) return;
                    menu.classList.remove('show');
                    toggle.classList.remove('show');
                    toggle.setAttribute('aria-expanded', 'false');
                };

                toggle.addEventListener('click', function (e) {
                    e.preventDefault();
                    if (!menu) return;
                    var isOpen = menu.classList.contains('show');
                    closeMenu();
                    if (!isOpen) {
                        menu.classList.add('show');
                        toggle.classList.add('show');
                        toggle.setAttribute('aria-expanded', 'true');
                    }
                });

                document.addEventListener('click', function (e) {
                    if (!menu) return;
                    if (toggle.contains(e.target) || menu.contains(e.target)) return;
                    closeMenu();
                });
            }

            // Modal compatibility helper (supports Bootstrap 5, Bootstrap 4, or no Bootstrap JS)
            var hasBootstrapModal = window.bootstrap && window.bootstrap.Modal;
            var hasBs4Modal = window.jQuery && window.jQuery.fn && window.jQuery.fn.modal;
            var openModal = null;

            function getBootstrapModalInstance(modal) {
                if (!hasBootstrapModal || !modal) return null;

                var modalApi = window.bootstrap.Modal;

                if (typeof modalApi.getOrCreateInstance === 'function') {
                    return modalApi.getOrCreateInstance(modal);
                }

                if (typeof modalApi.getInstance === 'function') {
                    return modalApi.getInstance(modal) || new modalApi(modal);
                }

                try {
                    return new modalApi(modal);
                } catch (error) {
                    return null;
                }
            }

            function ensureBackdrop() {
                var backdrop = document.querySelector('.modal-backdrop[data-fallback-backdrop]');
                if (backdrop) return backdrop;
                backdrop = document.createElement('div');
                backdrop.className = 'modal-backdrop fade show';
                backdrop.dataset.fallbackBackdrop = 'true';
                document.body.appendChild(backdrop);
                return backdrop;
            }

            function showFallback(modal) {
                if (!modal) return;
                if (openModal && openModal !== modal) {
                    hideFallback(openModal);
                }
                modal.style.display = 'block';
                modal.classList.add('show');
                modal.removeAttribute('aria-hidden');
                modal.setAttribute('aria-modal', 'true');
                document.body.classList.add('modal-open');
                ensureBackdrop();
                openModal = modal;
            }

            function hideFallback(modal) {
                if (!modal) return;
                modal.classList.remove('show');
                modal.style.display = 'none';
                modal.setAttribute('aria-hidden', 'true');
                modal.removeAttribute('aria-modal');
                document.body.classList.remove('modal-open');
                document.querySelectorAll('.modal-backdrop[data-fallback-backdrop]').forEach(function (el) {
                    el.remove();
                });
                if (openModal === modal) {
                    openModal = null;
                }
            }

            function showModal(modal) {
                if (!modal) return;
                var modalInstance = getBootstrapModalInstance(modal);
                if (modalInstance && typeof modalInstance.show === 'function') {
                    modalInstance.show();
                    return;
                }
                if (hasBs4Modal) {
                    window.jQuery(modal).modal('show');
                    return;
                }
                showFallback(modal);
            }

            function hideModal(modal) {
                if (!modal) return;
                var modalInstance = getBootstrapModalInstance(modal);
                if (modalInstance && typeof modalInstance.hide === 'function') {
                    modalInstance.hide();
                    return;
                }
                if (hasBs4Modal) {
                    window.jQuery(modal).modal('hide');
                    return;
                }
                hideFallback(modal);
            }

            window.CBModal = window.CBModal || {};
            window.CBModal.showById = function (id) {
                var modal = document.getElementById(id);
                showModal(modal);
            };
            window.CBModal.hideById = function (id) {
                var modal = document.getElementById(id);
                hideModal(modal);
            };

            if (!hasBootstrapModal && !hasBs4Modal) {
                document.addEventListener('click', function (e) {
                    var trigger = e.target.closest('[data-bs-toggle="modal"], [data-toggle="modal"]');
                    if (trigger) {
                        var target = trigger.getAttribute('data-bs-target') || trigger.getAttribute('data-target') || trigger.getAttribute('href');
                        if (target && target.startsWith('#')) {
                            var modal = document.querySelector(target);
                            if (modal) {
                                e.preventDefault();
                                showFallback(modal);
                            }
                        }
                    }

                    var dismiss = e.target.closest('[data-bs-dismiss="modal"], [data-dismiss="modal"]');
                    if (dismiss) {
                        var modalToClose = dismiss.closest('.modal');
                        if (modalToClose) {
                            e.preventDefault();
                            hideFallback(modalToClose);
                        }
                    }

                    if (openModal && e.target === openModal) {
                        hideFallback(openModal);
                    }
                });

                document.addEventListener('keydown', function (e) {
                    if (e.key === 'Escape' && openModal) {
                        hideFallback(openModal);
                    }
                });
            }
        });
    </script>

    @stack('scripts')
</body>
</html>
