<!DOCTYPE html>
<html lang="nb-NO">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Aroi Asia Admin</title>

    <!-- Favicon -->
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('images/logo.png') }}">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('images/logo.png') }}">
    <link rel="shortcut icon" href="{{ asset('images/logo.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('images/logo.png') }}">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">

    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">

    <!-- Custom CSS -->
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .navbar {
            height: 70px;
            z-index: 1030;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
        }

        .navbar-brand {
            font-weight: 700;
            font-size: 1.4rem;
            text-shadow: 1px 1px 2px rgba(0,0,0,0.3);
        }

        .nav-btn {
            border-radius: 25px !important;
            padding: 0.375rem 1rem !important;
            font-weight: 500;
            transition: all 0.3s ease;
            border-width: 2px !important;
            white-space: nowrap;
            backdrop-filter: blur(10px);
        }

        .nav-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.3);
        }

        .sidebar {
            position: fixed;
            top: 70px;
            bottom: 0;
            left: 0;
            z-index: 100;
            width: 250px;
            background-color: #f8f9fa;
            border-right: 1px solid #dee2e6;
            box-shadow: 2px 0 4px rgba(0,0,0,0.1);
        }

        .sidebar-sticky {
            position: relative;
            top: 0;
            height: calc(100vh - 70px);
            padding-top: 1rem;
            overflow-x: hidden;
            overflow-y: auto;
        }

        .sidebar .nav-link {
            color: #495057;
            padding: 0.75rem 1rem;
            border-radius: 0.375rem;
            margin: 0.125rem 0.5rem;
            transition: all 0.2s ease;
        }

        .sidebar .nav-link:hover {
            background-color: #e9ecef;
            color: #212529;
        }

        .sidebar .nav-link.active {
            background-color: #007bff;
            color: white;
        }

        .sidebar .nav-link i {
            width: 20px;
            text-align: center;
        }

        .main-content {
            padding: 2rem;
            min-height: calc(100vh - 70px);
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
        }

        .main-content.with-sidebar {
            margin-left: 250px;
        }

        .card {
            border: none;
            border-radius: 0.5rem;
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
            transition: box-shadow 0.15s ease-in-out;
        }

        .card:hover {
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
        }

        .card-header {
            background-color: #fff;
            border-bottom: 1px solid #dee2e6;
            font-weight: 600;
        }

        .btn {
            border-radius: 0.375rem;
            font-weight: 500;
        }

        .table {
            border-radius: 0.5rem;
            overflow: hidden;
        }

        .badge {
            font-size: 0.75rem;
            font-weight: 500;
        }

        /* Enhanced card styling - optimized for performance */
        .dashboard-card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
            transition: box-shadow 0.2s ease;
            overflow: hidden;
            background: #ffffff;
            will-change: box-shadow;
        }

        .dashboard-card:hover {
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.12);
        }

        .weather-card {
            background: linear-gradient(135deg, #74b9ff 0%, #0984e3 100%);
            color: white;
        }

        .estimate-card {
            background: linear-gradient(135deg, #fd79a8 0%, #e84393 100%);
            color: white;
        }

                        /* Ensure sidebar is visible on desktop */
        @media (min-width: 768px) {
            .sidebar {
                display: block !important;
                transform: translateX(0) !important;
            }
        }

        /* Mobile styles */
        @media (max-width: 767.98px) {
            .sidebar {
                transform: translateX(-100%);
                transition: transform 0.3s ease;
                width: 280px;
            }

            .sidebar.show {
                transform: translateX(0);
            }

            .main-content,
            .main-content.with-sidebar {
                margin-left: 0;
                padding: 1rem;
            }

            .d-flex.flex-nowrap {
                flex-wrap: wrap !important;
            }

            .nav-btn {
                margin-bottom: 0.5rem;
                font-size: 0.75rem;
                padding: 0.25rem 0.75rem !important;
            }
        }

        /* Navbar dropdown improvements */
        .dropdown-menu {
            border-radius: 0.5rem;
            border: none;
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
        }

        .dropdown-item {
            padding: 0.5rem 1rem;
            border-radius: 0.375rem;
            margin: 0.125rem;
        }

        .dropdown-item:hover {
            background-color: #f8f9fa;
        }

        /* Status cards styling */
        .border-start {
            border-left-width: 4px !important;
        }

        /* Welcome section styling */
        .lead {
            color: #6c757d;
        }

        /* Loading states and performance optimizations */
        .spinner-border {
            animation: spinner-border 0.75s linear infinite;
        }

        /* Smooth page transitions */
        body {
            transition: opacity 0.3s ease;
        }

        /* Reduce reflow by setting fixed heights */
        .dashboard-card .card-body {
            min-height: 120px;
        }

        .dashboard-card canvas {
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .dashboard-card canvas:not([style*="display: none"]) {
            opacity: 1;
        }

        /* Optimize chart containers */
        .chart-container {
            position: relative;
            height: 300px;
        }

                /* Better navbar on mobile */
        @media (max-width: 991.98px) {
            .navbar-brand {
                font-size: 1.1rem;
            }

            .btn-sm {
                padding: 0.25rem 0.5rem;
                font-size: 0.75rem;
            }

            /* Stack navbar buttons vertically on smaller screens */
            .d-flex.align-items-center.me-auto {
                flex-wrap: wrap;
                gap: 0.25rem;
            }

            /* Hide text in buttons on very small screens */
            @media (max-width: 767.98px) {
                .btn-sm .d-none.d-sm-inline {
                    display: none !important;
                }
            }
        }

        /* Compact navbar layout */
        .navbar .container-fluid {
            flex-wrap: nowrap;
        }

        .navbar .d-flex.align-items-center {
            min-width: 0;
        }
    </style>

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <!-- Custom Styles -->
    @stack('styles')
</head>
<body class="bg-light">
    <!-- Navigation -->
    <nav class="navbar navbar-dark sticky-top bg-gradient shadow-lg">
        <div class="container-fluid">
            <div class="d-flex align-items-center w-100">
                <!-- Brand -->
                <a class="navbar-brand fw-bold text-white me-4" href="{{ route('admin.dashboard') }}">
                    <i class="bi bi-shop me-2 text-primary"></i>Aroi Admin
                </a>

                                                <!-- Menu buttons - Updated design -->
                <div class="d-flex align-items-center me-auto">
                    <!-- Primary action button -->
                    <a class="btn btn-primary btn-sm me-2" href="{{ route('admin.orders.index') }}">
                        <i class="bi bi-tablet me-1"></i><span class="d-none d-sm-inline">App</span>
                    </a>

                    <!-- Dashboard button -->
                    <a class="btn btn-outline-light btn-sm me-2" href="{{ route('dashboard') }}">
                        <i class="bi bi-house me-1"></i><span class="d-none d-md-inline">Dashboard</span>
                    </a>

                    <!-- Statistics button -->
                    <a class="btn btn-outline-light btn-sm me-2" href="{{ route('admin.statistics.index') }}">
                        <i class="bi bi-bar-chart-fill me-1"></i><span class="d-none d-md-inline">Statistikk</span>
                    </a>

                    <!-- Settings dropdown -->
                    <div class="dropdown me-2">
                        <button class="btn btn-outline-light btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown">
                            <i class="bi bi-gear me-1"></i><span class="d-none d-md-inline">Instillinger</span>
                        </button>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="/admin/opening-hours">
                                <i class="bi bi-clock me-2"></i>Åpningstider
                            </a></li>
                            <li><a class="dropdown-item" href="{{ route('profile.edit') }}">
                                <i class="bi bi-person-gear me-2"></i>Profil
                            </a></li>
                        </ul>
                    </div>

                    @if(auth()->user() && auth()->user()->isAdmin())
                        <!-- Admin dropdown -->
                        <div class="dropdown me-2">
                            <button class="btn btn-outline-danger btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                <i class="bi bi-shield-lock me-1"></i><span class="d-none d-lg-inline">Admin</span>
                            </button>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="{{ route('admin.users.index') }}">
                                    <i class="bi bi-people me-2"></i>Brukere
                                </a></li>
                                <li><a class="dropdown-item" href="{{ route('admin.sites.index') }}">
                                    <i class="bi bi-shop me-2"></i>Steder
                                </a></li>
                                <li><a class="dropdown-item" href="{{ route('admin.locations.index') }}">
                                    <i class="bi bi-geo-alt me-2"></i>Lokasjoner
                                </a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="{{ route('admin.pck-soap.index') }}">
                                    <i class="fas fa-server me-2"></i>PCK SOAP
                                </a></li>
                                <li><a class="dropdown-item" href="{{ route('admin.settings.index') }}">
                                    <i class="bi bi-gear me-2"></i>Systeminnstillinger
                                </a></li>
                            </ul>
                        </div>
                    @endif
                </div>

                <!-- User dropdown -->
                <div class="dropdown">
                    <button class="btn btn-outline-light btn-sm dropdown-toggle d-flex align-items-center" type="button" data-bs-toggle="dropdown">
                        <i class="bi bi-person-circle me-2"></i>
                        <span class="d-none d-md-inline">{{ auth()->user() ? auth()->user()->username : 'User' }}</span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        @if(session('impersonate.original_id'))
                            <li>
                                <form method="POST" action="{{ route('admin.stop-impersonate') }}" class="d-inline w-100">
                                    @csrf
                                    <button type="submit" class="dropdown-item text-warning">
                                        <i class="fas fa-user-secret me-2"></i>Avslutt impersonering
                                    </button>
                                </form>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                        @endif
                        <li>
                            <a class="dropdown-item" href="{{ route('profile.edit') }}">
                                <i class="bi bi-person-gear me-2"></i>Profil
                            </a>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <form method="POST" action="{{ route('logout') }}" class="d-inline w-100">
                                @csrf
                                <button type="submit" class="dropdown-item text-danger">
                                    <i class="bi bi-box-arrow-right me-2"></i>Logg ut
                                </button>
                            </form>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </nav>

    <div class="container-fluid">
        <div class="row">
            @if(auth()->user() && auth()->user()->isAdmin() && request()->routeIs('admin.*'))
                <!-- Sidebar (only for admin pages) -->
                <nav id="sidebarMenu" class="col-md-3 col-lg-2 d-md-block bg-light sidebar">
                    <div class="sidebar-sticky pt-3">
                        <ul class="nav flex-column">
                            <li class="nav-item">
                                <a class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}"
                                   href="{{ route('admin.dashboard') }}">
                                    <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link {{ request()->routeIs('admin.orders.*') ? 'active' : '' }}"
                                   href="{{ route('admin.orders.index') }}">
                                    <i class="fas fa-shopping-cart me-2"></i>Bestillinger
                                </a>
                            </li>

                            <li class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle {{ request()->routeIs('admin.users.*') || request()->routeIs('admin.sites.*') || request()->routeIs('admin.settings.*') || request()->routeIs('admin.pck-soap.*') ? 'active' : '' }}"
                                   href="#" id="adminDropdown" role="button" data-bs-toggle="dropdown">
                                    <i class="fas fa-cogs me-2"></i>Admin
                                </a>
                                <ul class="dropdown-menu">
                                    <li>
                                        <a class="dropdown-item {{ request()->routeIs('admin.users.*') ? 'active' : '' }}"
                                           href="{{ route('admin.users.index') }}">
                                            <i class="fas fa-users me-2"></i>Brukere
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item {{ request()->routeIs('admin.sites.*') ? 'active' : '' }}"
                                           href="{{ route('admin.sites.index') }}">
                                            <i class="fas fa-store me-2"></i>Steder
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item {{ request()->routeIs('admin.locations.*') ? 'active' : '' }}"
                                           href="{{ route('admin.locations.index') }}">
                                            <i class="fas fa-map-marker-alt me-2"></i>Lokasjoner
                                        </a>
                                    </li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li>
                                        <a class="dropdown-item {{ request()->routeIs('admin.settings.*') ? 'active' : '' }}"
                                           href="{{ route('admin.settings.index') }}">
                                            <i class="fas fa-cog me-2"></i>Innstillinger
                                        </a>
                                    </li>
                                </ul>
                            </li>
                        </ul>

                        <h6 class="sidebar-heading d-flex justify-content-between align-items-center px-3 mt-4 mb-1 text-muted">
                            <span>Bruker</span>
                        </h6>
                        <ul class="nav flex-column mb-2">
                            <li class="nav-item">
                                <a class="nav-link" href="{{ route('profile.edit') }}">
                                    <i class="bi bi-person me-2"></i>Profil
                                </a>
                            </li>
                        </ul>
                    </div>
                </nav>

                <!-- Main content with sidebar -->
                <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 main-content with-sidebar">
            @else
                <!-- Main content without sidebar -->
                <main class="main-content">
            @endif
                @if (session('success'))
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        {{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                @if (session('error'))
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        {{ session('error') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                @yield('content')
            </main>
        </div>
    </div>

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

        <!-- Custom JS -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Auto-hide alerts after 5 seconds
            setTimeout(function() {
                const alerts = document.querySelectorAll('.alert');
                alerts.forEach(function(alert) {
                    const bsAlert = new bootstrap.Alert(alert);
                    bsAlert.close();
                });
            }, 5000);

            // Mobile sidebar toggle - only if sidebar exists
            const sidebar = document.querySelector('#sidebarMenu');

            if (sidebar) {
                // Add click handler to toggle sidebar on mobile
                document.addEventListener('click', function(e) {
                    if (e.target.closest('.navbar-toggler') && window.innerWidth < 768) {
                        sidebar.classList.toggle('show');
                    }
                });

                // Close sidebar when clicking outside on mobile
                document.addEventListener('click', function(e) {
                    if (window.innerWidth < 768 && sidebar.classList.contains('show')) {
                        if (!sidebar.contains(e.target) && !e.target.closest('.navbar-toggler')) {
                            sidebar.classList.remove('show');
                        }
                    }
                });

                // Handle window resize
                window.addEventListener('resize', function() {
                    if (window.innerWidth >= 768) {
                        sidebar.classList.remove('show');
                    }
                });
            }
        });
    </script>



    @stack('scripts')
</body>
</html>
