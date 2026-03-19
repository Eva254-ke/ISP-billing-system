<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'CloudBridge Networks') }} - Admin</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

    <!-- Vite Assets -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="stylesheet" href="{{ asset('css/admin-overrides.css') }}?v=20260319-2">
</head>
<body class="hold-transition sidebar-mini layout-fixed layout-navbar-fixed">
    <div class="wrapper">

        <!-- Navbar (Edge-to-Edge, Fixed Top) -->
        <nav class="main-header navbar navbar-expand navbar-white navbar-light border-bottom">
            <!-- Left: Collapse Toggle + Logo -->
            <ul class="navbar-nav">
                <li class="nav-item">
                    <a class="nav-link" id="sidebarToggle" href="javascript:void(0)">
                        <i class="fas fa-bars"></i>
                    </a>
                </li>
                <li class="nav-item d-md-none">
                    <a href="{{ route('admin.dashboard') }}" class="nav-link brand-logo">
                        <strong>CloudBridge</strong> Networks
                    </a>
                </li>
            </ul>

            <!-- Right: User Menu -->
            <ul class="navbar-nav ms-auto ml-auto align-items-center navbar-user-menu">
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle js-user-dropdown-toggle" id="userMenuDropdown" data-bs-toggle="dropdown" data-toggle="dropdown" href="#" role="button" aria-haspopup="true" aria-expanded="false">
                        <i class="far fa-user me-1"></i>
                        <span class="d-none d-sm-inline">Admin</span>
                    </a>
                    <div class="dropdown-menu dropdown-menu-lg dropdown-menu-end dropdown-menu-right" aria-labelledby="userMenuDropdown">
                        <a href="#" class="dropdown-item">
                            <i class="fas fa-user me-2"></i> Profile
                        </a>
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
        </nav>
        <!-- /.navbar -->

        <!-- Sidebar (Below Navbar, Collapsible) -->
        <aside class="main-sidebar sidebar-dark-primary elevation-4" id="mainSidebar">
            <!-- Brand (Mobile Only) -->
            <a href="{{ route('admin.dashboard') }}" class="brand-link">
                <span class="brand-text">CloudBridge Networks</span>
            </a>

            <!-- Sidebar -->
            <div class="sidebar">
                <!-- Menu -->
                <nav class="mt-2">
                    <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu">
                        
                        <!-- Dashboard -->
                        <li class="nav-item">
                            <a href="{{ route('admin.dashboard') }}" 
                               class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                                <i class="nav-icon fas fa-tachometer-alt"></i>
                                <p>Dashboard</p>
                            </a>
                        </li>

                        <!-- Routers -->
                        <li class="nav-item">
                            <a href="{{ route('admin.routers.index') }}" 
                               class="nav-link {{ request()->routeIs('admin.routers.*') ? 'active' : '' }}">
                                <i class="nav-icon fas fa-server"></i>
                                <p>Routers</p>
                            </a>
                        </li>

                        <!-- Packages -->
                        <li class="nav-item">
                            <a href="{{ route('admin.packages.index') }}" 
                               class="nav-link {{ request()->routeIs('admin.packages.*') ? 'active' : '' }}">
                                <i class="nav-icon fas fa-box"></i>
                                <p>Packages</p>
                            </a>
                        </li>

                        <!-- Vouchers -->
                        <li class="nav-item">
                            <a href="{{ route('admin.vouchers.index') }}" 
                               class="nav-link {{ request()->routeIs('admin.vouchers.*') ? 'active' : '' }}">
                                <i class="nav-icon fas fa-ticket-alt"></i>
                                <p>Vouchers</p>
                            </a>
                        </li>

                        <!-- Payments -->
                        <li class="nav-item">
                            <a href="{{ route('admin.payments.index') }}" 
                               class="nav-link {{ request()->routeIs('admin.payments.*') ? 'active' : '' }}">
                                <i class="nav-icon fas fa-money-bill-wave"></i>
                                <p>Payments</p>
                            </a>
                        </li>

                        <!-- Clients Dropdown -->
                        <li class="nav-item has-treeview {{ request()->is('admin/clients*') ? 'menu-open' : '' }}">
                            <a href="#" class="nav-link {{ request()->is('admin/clients*') ? 'active' : '' }}">
                                <i class="nav-icon fas fa-users"></i>
                                <p>
                                    Clients
                                    <i class="right fas fa-angle-left"></i>
                                </p>
                            </a>
                            <ul class="nav nav-treeview">
                                <li class="nav-item">
                                    <a href="{{ route('admin.clients.hotspot') }}" 
                                       class="nav-link {{ request()->routeIs('admin.clients.hotspot') ? 'active' : '' }}">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>Hotspot Clients</p>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="{{ route('admin.clients.pppoe') }}" 
                                       class="nav-link {{ request()->routeIs('admin.clients.pppoe') ? 'active' : '' }}">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>PPPoE Clients</p>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="{{ route('admin.clients.customers') }}" 
                                       class="nav-link {{ request()->routeIs('admin.clients.customers') ? 'active' : '' }}">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>All Customers</p>
                                    </a>
                                </li>
                            </ul>
                        </li>

                        <!-- Settings -->
                        <li class="nav-item">
                            <a href="{{ route('admin.settings') }}" 
                               class="nav-link {{ request()->routeIs('admin.settings') ? 'active' : '' }}">
                                <i class="nav-icon fas fa-cogs"></i>
                                <p>Settings</p>
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
            if (!toggle) return;
            var hasBs5 = window.bootstrap && window.bootstrap.Dropdown;
            var hasBs4 = window.jQuery && window.jQuery.fn && window.jQuery.fn.dropdown;
            if (hasBs5 || hasBs4) return;

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
        });
    </script>

    @stack('scripts')
</body>
</html>
