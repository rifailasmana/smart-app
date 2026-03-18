<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Majar Signature - Ordering')</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        :root {
            --primary-color: #FF8C00;
            --primary-dark: #E67E00;
            --secondary-color: #FFC107;
            --dark-color: #0f172a;
            --light-color: #f3f4f6;
            --surface-color: #ffffff;
            --radius-lg: 16px;
            --radius-md: 12px;
            --shadow-soft: 0 18px 45px rgba(15, 23, 42, 0.08);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            background-color: var(--light-color);
            color: var(--dark-color);
        }

        .navbar {
            background: radial-gradient(circle at top left, #fff7e6, #FFC107, #FF8C00);
            box-shadow: 0 12px 40px rgba(255, 140, 0, 0.25);
        }

        .navbar-brand {
            font-weight: 700;
            font-size: 1.4rem;
            color: white !important;
            letter-spacing: 0.03em;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            border: none;
            font-weight: 600;
            color: #000;
        }

        .btn-primary:hover {
            background: linear-gradient(135deg, var(--primary-dark) 0%, var(--secondary-color) 100%);
            border: none;
            color: #000;
        }

        .btn-secondary {
            background-color: var(--secondary-color);
            border-color: var(--secondary-color);
        }

        .btn-secondary:hover {
            background-color: #3db8ab;
            border-color: #3db8ab;
        }

        .card {
            border: none;
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-soft);
            background-color: var(--surface-color);
        }

        .badge {
            padding: 5px 10px;
            border-radius: 999px;
        }

        .alert {
            border-radius: var(--radius-md);
            border: none;
            box-shadow: 0 12px 30px rgba(15, 23, 42, 0.08);
        }

        main {
            min-height: calc(100vh - 140px);
        }

        footer {
            background-color: #020617;
            color: #e5e7eb;
            padding: 30px 0;
            margin-top: 40px;
        }

        footer a {
            color: #FFC107;
            text-decoration: none;
        }

        footer a:hover {
            text-decoration: underline;
        }

        .nav-link {
            color: rgba(255,255,255,0.86) !important;
            transition: color 0.25s ease;
            font-weight: 500;
        }

        .nav-link:hover {
            color: #ffffff !important;
        }

        .form-control:focus,
        .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.18rem rgba(255, 140, 0, 0.18);
        }
    </style>
    @yield('styles')
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark sticky-top">
        <div class="container">
            <a class="navbar-brand" href="/">
                <i class="bi bi-shop"></i> Majar Signature
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    @if(Auth::check())
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                                <i class="bi bi-person-circle"></i> {{ Auth::user()->name }}
                            </a>
                            <ul class="dropdown-menu" aria-labelledby="navbarDropdown">
                                <li><a class="dropdown-item" href="{{ route('dashboard.owner') }}">Dashboard</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <form action="{{ route('logout') }}" method="POST" style="display:inline;">
                                        @csrf
                                        <button type="submit" class="dropdown-item">Logout</button>
                                    </form>
                                </li>
                            </ul>
                        </li>
                    @else
                        <li class="nav-item">
                            <a class="nav-link" href="{{ route('login') }}">Login</a>
                        </li>
                    @endif
                </ul>
            </div>
        </div>
    </nav>

    <main>
        <div class="container mt-4">
            @if($errors->any())
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <strong>Terjadi Kesalahan!</strong>
                    <ul class="mb-0">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

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
        </div>

        @yield('content')
    </main>

    <footer class="mt-5">
        <div class="container">
            <div class="row">
                <div class="col-md-4 mb-4">
                    <h5>Majar Signature</h5>
                    <p>Sistem pemesanan digital untuk Majar Signature</p>
                </div>
                <div class="col-md-4 mb-4">
                    <h5>Fitur</h5>
                    <ul class="list-unstyled">
                        <li><a href="#">Pesanan Digital</a></li>
                        <li><a href="#">Kitchen Display System</a></li>
                        <li><a href="#">Analytics</a></li>
                    </ul>
                </div>
                <div class="col-md-4 mb-4">
                    <h5>Kontak</h5>
                    <ul class="list-unstyled">
                        <li>Email: <a href="mailto:support@majar.local">support@majar.local</a></li>
                        <li>WhatsApp: +62 xxx xxxx xxxx</li>
                    </ul>
                </div>
            </div>
            <hr>
            <div class="text-center">
                <p>&copy; {{ date('Y') }} Majar Signature. Semua hak dilindungi.</p>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    @yield('scripts')
</body>
</html>
