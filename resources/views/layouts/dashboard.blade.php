<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Majar Signature')</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    <style>
        :root {
            --sidebar-width: 260px;
            --brand-orange: #FF8C00;
            --brand-yellow: #FFC107;
            --brand-gradient: linear-gradient(135deg, #FF8C00, #FFC107);
            --sidebar-bg: #1a1a1a;
            --sidebar-text: #e0e0e0;
            --sidebar-active: #ffffff;
            --dashboard-bg: #f8f9fa;
            --accent-orange: var(--brand-orange);
            --accent-yellow: var(--brand-yellow);
            --card-shadow: 0 4px 20px rgba(0,0,0,0.05);
            --radius-lg: 1.25rem;
            --radius-md: 0.75rem;
        }

        body {
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            background-color: var(--dashboard-bg);
            margin: 0;
            color: #333;
        }

        .dashboard-root {
            display: flex;
            min-height: 100vh;
        }

        .dashboard-sidebar {
            width: var(--sidebar-width);
            background-color: var(--sidebar-bg);
            color: var(--sidebar-text);
            display: flex;
            flex-direction: column;
            position: fixed;
            height: 100vh;
            z-index: 1000;
            transition: all 0.3s ease;
            box-shadow: 4px 0 25px rgba(0,0,0,0.15);
        }

        .sidebar-brand {
            padding: 2rem 1.5rem;
            font-size: 1.25rem;
            font-weight: 800;
            color: #fff;
            letter-spacing: 0.5px;
            text-align: center;
            border-bottom: 1px solid rgba(255,255,255,0.05);
            margin-bottom: 1rem;
        }

        .sidebar-brand span.highlight {
            color: var(--accent-orange);
        }

        .sidebar-nav {
            flex: 1;
            padding: 0 0.75rem;
            overflow-y: auto;
        }

        .nav-item {
            display: flex;
            align-items: center;
            padding: 0.85rem 1.25rem;
            color: var(--sidebar-text);
            text-decoration: none;
            border-radius: var(--radius-md);
            margin-bottom: 0.5rem;
            transition: all 0.2s;
            font-weight: 500;
        }

        .nav-item i {
            width: 24px;
            font-size: 1.1rem;
            margin-right: 12px;
            text-align: center;
        }

        .nav-item:hover {
            background-color: rgba(255, 140, 0, 0.1);
            color: var(--accent-orange);
            transform: translateX(5px);
        }

        .nav-item.active {
            background: var(--brand-gradient);
            color: #000;
            box-shadow: 0 4px 15px rgba(255, 140, 0, 0.3);
            font-weight: 700;
        }

        .dashboard-shell {
            flex: 1;
            margin-left: var(--sidebar-width);
            display: flex;
            flex-direction: column;
            min-width: 0;
            background-color: var(--dashboard-bg);
        }

        .dashboard-header {
            background: #fff;
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.02);
            position: sticky;
            top: 0;
            z-index: 900;
        }

        .dashboard-header-left {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .dashboard-logo {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .dashboard-title-main {
            font-size: 1.1rem;
            font-weight: 700;
            color: #1a1a1a;
            line-height: 1.2;
        }

        .dashboard-title-sub {
            font-size: 0.8rem;
            color: #6c757d;
        }

        .dashboard-header-right {
            display: flex;
            align-items: center;
            gap: 1.5rem;
        }

        .user-chip {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.5rem 1rem;
            background: #f8f9fa;
            border-radius: 50px;
            border: 1px solid #eee;
        }

        .user-avatar {
            width: 32px;
            height: 32px;
            background: linear-gradient(135deg, var(--accent-orange), var(--accent-yellow));
            color: #000;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 0.85rem;
        }

        .user-name {
            font-size: 0.9rem;
            font-weight: 600;
            color: #333;
        }

        .user-role {
            font-size: 0.7rem;
            color: #FF8C00;
            font-weight: 700;
            text-transform: uppercase;
        }

        .dashboard-content {
            padding: 2rem;
            flex: 1;
        }

        .card {
            border: none;
            border-radius: var(--radius-lg);
            box-shadow: var(--card-shadow);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            background: #fff;
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.08);
        }

        .btn-primary {
            background: var(--brand-gradient);
            border: none;
            color: #000;
            font-weight: 700;
            border-radius: var(--radius-md);
            padding: 0.6rem 1.5rem;
            box-shadow: 0 4px 10px rgba(255, 140, 0, 0.2);
        }

        .btn-primary:hover {
            filter: brightness(1.05);
            color: #000;
            box-shadow: 0 6px 15px rgba(255, 140, 0, 0.3);
        }

        .btn-outline-brand {
            border: 1px solid rgba(255, 140, 0, 0.35);
            background: rgba(255, 140, 0, 0.06);
            color: #1a1a1a;
            font-weight: 700;
            border-radius: var(--radius-md);
        }

        .btn-outline-brand:hover {
            background: rgba(255, 140, 0, 0.12);
            color: #1a1a1a;
        }

        .text-brand {
            color: var(--brand-orange) !important;
        }

        .bg-brand {
            background: var(--brand-gradient) !important;
            color: #000 !important;
        }

        .badge-brand {
            background: rgba(255, 140, 0, 0.14);
            color: #7a3c00;
            border: 1px solid rgba(255, 140, 0, 0.22);
            font-weight: 800;
        }

        .form-control:focus,
        .form-select:focus {
            border-color: rgba(255, 140, 0, 0.55);
            box-shadow: 0 0 0 0.25rem rgba(255, 140, 0, 0.12);
        }

        @media (max-width: 1024px) {
            .dashboard-sidebar {
                width: 80px;
            }
            .sidebar-brand span, .nav-item span {
                display: none;
            }
            .dashboard-shell {
                margin-left: 80px;
            }
            .nav-item {
                justify-content: center;
                padding: 1rem;
            }
            .nav-item i {
                margin-right: 0;
                font-size: 1.3rem;
            }
        }

        @media (max-width: 640px) {
            .dashboard-sidebar {
                transform: translateX(-100%);
            }
            .dashboard-shell {
                margin-left: 0;
            }
            .dashboard-root.sidebar-open .dashboard-sidebar {
                transform: translateX(0);
                width: 260px;
            }
        }
    </style>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="{{ asset('js/app.js') }}"></script>
</head>
<body>
    <div class="dashboard-root">
        <aside class="dashboard-sidebar shadow">
            <div class="sidebar-brand">
                <span class="highlight">MAJAR</span> SIGNATURE
            </div>
            <nav class="sidebar-nav">
                <a href="{{ route('dashboard') }}" class="nav-item {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                    <i class="fas fa-home"></i> <span>Home</span>
                </a>

                @if(auth()->user()->role === 'admin')
                <a href="{{ route('admin.warungs') }}" class="nav-item {{ request()->routeIs('admin.warungs') ? 'active' : '' }}">
                    <i class="fas fa-tools"></i> <span>Admin Warungs</span>
                </a>
                <a href="{{ route('admin.diagnostics') }}" class="nav-item {{ request()->routeIs('admin.diagnostics') ? 'active' : '' }}">
                    <i class="fas fa-shield-alt"></i> <span>Diagnostics</span>
                </a>
                @endif

                @if(auth()->user()->isOwner())
                <a href="{{ route('dashboard.owner') }}" class="nav-item {{ request()->routeIs('dashboard.owner') ? 'active' : '' }}">
                    <i class="fas fa-crown"></i> <span>Owner Panel</span>
                </a>
                @endif

                @if(auth()->user()->isHRD())
                <a href="{{ route('dashboard.hrd') }}" class="nav-item {{ request()->routeIs('dashboard.hrd') ? 'active' : '' }}">
                    <i class="fas fa-user-tie"></i> <span>HRD Panel</span>
                </a>
                @endif

                @if(auth()->user()->isManager())
                <a href="{{ route('dashboard.manager') }}" class="nav-item {{ request()->routeIs('dashboard.manager') ? 'active' : '' }}">
                    <i class="fas fa-tasks"></i> <span>Manager Panel</span>
                </a>
                @endif

                @if(auth()->user()->isInventory())
                <a href="{{ route('dashboard.inventory') }}" class="nav-item {{ request()->routeIs('dashboard.inventory') ? 'active' : '' }}">
                    <i class="fas fa-boxes"></i> <span>Inventory</span>
                </a>
                @endif

                @if(auth()->user()->isCashier())
                <a href="{{ route('terminal.kasir') }}" class="nav-item {{ request()->routeIs('terminal.kasir') ? 'active' : '' }}">
                    <i class="fas fa-cash-register"></i> <span>Cashier POS</span>
                </a>
                @endif

                @if(auth()->user()->isWaiter())
                <a href="{{ route('terminal.waiter') }}" class="nav-item {{ request()->routeIs('terminal.waiter') ? 'active' : '' }}">
                    <i class="fas fa-concierge-bell"></i> <span>Waiter Service</span>
                </a>
                @endif

                @if(auth()->user()->isKitchen())
                <a href="{{ route('terminal.kitchen') }}" class="nav-item {{ request()->routeIs('terminal.kitchen') ? 'active' : '' }}">
                    <i class="fas fa-fire"></i> <span>Kitchen KDS</span>
                </a>
                @endif

                <div class="mt-auto pt-4">
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="nav-item border-0 bg-transparent w-100 text-start text-danger">
                            <i class="fas fa-sign-out-alt"></i> <span>Logout</span>
                        </button>
                    </form>
                </div>
            </nav>
        </aside>
        <div class="dashboard-shell">

            <main class="dashboard-main">
                <header class="dashboard-header">
                    <div class="dashboard-header-left">
                        <button type="button" class="btn btn-outline-brand btn-sm d-inline-flex d-lg-none" id="sidebarToggle" aria-label="Toggle sidebar">
                            <i class="fas fa-bars"></i>
                        </button>
                        <div class="dashboard-logo" style="background: linear-gradient(135deg, var(--accent-orange), var(--accent-yellow)); color: #000; font-weight: 800;">
                            MS
                        </div>
                        <div class="dashboard-title">
                            <div class="dashboard-title-main">
                                @yield('header_title', 'Dashboard')
                            </div>
                            <div class="dashboard-title-sub">
                                @yield('header_subtitle', 'Majar Signature Operating System')
                            </div>
                        </div>
                    </div>
                    <div class="dashboard-header-right">
                        @auth
                            @php
                                $warung = auth()->user()->warung;
                                $clockEnabled = optional($warung)->enable_system_clock !== false;
                                $clockFormat = $warung ? ($warung->system_clock_format ?? '24h') : '24h';
                            @endphp
                            @if($clockEnabled)
                                <div class="dashboard-clock" data-clock-format="{{ $clockFormat }}">
                                    <div class="dashboard-clock-time" id="system-clock"></div>
                                    @if($warung)
                                        <div class="dashboard-clock-warung">{{ $warung->name }}</div>
                                    @endif
                                </div>
                            @endif
                            <div class="user-chip">
                                <div class="user-avatar">
                                    {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                                </div>
                                <div class="user-meta">
                                    <div class="user-name">{{ auth()->user()->name }}</div>
                                    <div class="user-role">{{ ucfirst(auth()->user()->role) }}</div>
                                </div>
                            </div>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="btn btn-outline-brand btn-sm">
                                    <span>Logout</span>
                                </button>
                            </form>
                        @endauth
                    </div>
                </header>

                <div class="dashboard-content">
                    <div class="dashboard-content-inner">
                        @yield('content')
                    </div>
                </div>

                <footer class="dashboard-footer">
                    <div>© {{ date('Y') }} Majar Signature. All rights reserved.</div>
                    <div>
                        <a href="#" class="link-muted">Help & Support</a>
                    </div>
                </footer>
            </main>
        </div>
    </div>

    <div id="confirm-modal-backdrop" class="modal-backdrop">
        <div class="modal-panel">
            <div class="modal-header">
                <div data-confirm-title>Konfirmasi</div>
                <button type="button" class="toast-close" data-confirm-no>×</button>
            </div>
            <div class="modal-body" data-confirm-body></div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline btn-sm" data-confirm-no>Batal</button>
                <button type="button" class="btn btn-danger btn-sm" data-confirm-yes>Ya, lanjutkan</button>
            </div>
        </div>
    </div>
    <script>
        (function () {
            const root = document.querySelector('.dashboard-root');
            const btn = document.getElementById('sidebarToggle');
            if (!root || !btn) return;
            btn.addEventListener('click', function () {
                root.classList.toggle('sidebar-open');
            });
        })();
    </script>
</body>
</html>
