<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Terminal') | {{ $warung->name ?? 'SmartOrder' }}</title>
    <link rel="manifest" href="/manifest.json">
    <meta name="theme-color" content="#22C55E">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        terminal: {
                            bg: '#111827', // Dark Gray
                            panel: '#1F2937', // Lighter Dark Gray
                            accent: '#F97316', // Orange
                            warning: '#FBBF24', // Yellow
                            danger: '#EF4444',
                            text: '#F9FAFB', // Almost White
                            muted: '#9CA3AF', // Muted Gray
                            border: '#374151' // Border Gray
                        }
                    }
                }
            }
        }
    </script>
    <script src="https://unpkg.com/react@18/umd/react.production.min.js"></script>
    <script src="https://unpkg.com/react-dom@18/umd/react-dom.production.min.js"></script>
    <script src="https://unpkg.com/@babel/standalone/babel.min.js"></script>
    <style>
        :root {
            --terminal-bg: #F8FAFC;
            --terminal-panel: #FFFFFF;
            --terminal-accent: #FF8C00;
            --terminal-warning: #FFC107;
            --terminal-danger: #EF4444;
            --terminal-text: #1E293B;
            --terminal-text-muted: #64748B;
            --terminal-border: rgba(255, 140, 0, 0.2);
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--terminal-bg);
            color: var(--terminal-text);
            margin: 0;
            padding: 0;
            height: 100vh;
            overflow: hidden;
        }

        .terminal-container {
            display: flex;
            flex-direction: column;
            height: 100vh;
        }

        .terminal-header {
            height: 64px;
            background-color: var(--terminal-panel);
            border-bottom: 1px solid var(--terminal-border);
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 24px;
            flex-shrink: 0;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }

        .terminal-content {
            flex: 1;
            display: flex;
            overflow: hidden;
        }

        .btn-terminal {
            height: 48px;
            border-radius: 12px;
            font-weight: 600;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: all 0.2s;
            border: none;
        }

        .btn-terminal-primary {
            background-color: var(--terminal-accent);
            color: #fff;
        }

        .btn-terminal-primary:hover {
            background-color: #e67e00;
            color: #fff;
        }

        .btn-terminal-secondary {
            background-color: var(--terminal-bg);
            color: var(--terminal-text);
            border: 1px solid var(--terminal-border);
        }

        .btn-terminal-secondary:hover {
            background-color: #f1f5f9;
        }

        .card-terminal {
            background-color: var(--terminal-panel);
            border: 1px solid var(--terminal-border);
            border-radius: 16px;
            padding: 24px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.03);
        }

        /* Scrollbar styling */
        ::-webkit-scrollbar {
            width: 8px;
        }
        ::-webkit-scrollbar-track {
            background: var(--terminal-bg);
        }
        ::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 4px;
        }
        ::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
        }

        @yield('extra_css')
    </style>
</head>
<body>
    <div class="terminal-container bg-gray-900 text-white">
        <header class="terminal-header bg-gray-900 border-b border-gray-800">
            <div class="flex items-center gap-4">
                <div class="w-10 h-10 bg-gradient-to-br from-orange-500 to-yellow-400 rounded-lg flex items-center justify-center">
                    <i class="bi bi-person-circle text-2xl text-white"></i>
                </div>
                <div>
                    <h1 class="text-lg font-bold tracking-tight text-white">@yield('terminal_role', 'Terminal')</h1>
                    <p class="text-xs text-gray-400">{{ now()->format('l, j F Y') }}</p>
                </div>
            </div>
            <div class="flex items-center gap-6">
                @yield('header_extra')
                <div class="flex items-center gap-2 text-white">
                    <i class="bi bi-wifi text-lg"></i>
                    <span class="text-sm font-semibold">{{ $warung->name ?? 'SmartOrder' }}</span>
                </div>
                <a href="{{ route('logout') }}" onclick="event.preventDefault(); document.getElementById('logout-form').submit();" class="text-gray-400 hover:text-white transition-colors">
                    <i class="bi bi-box-arrow-right text-xl"></i>
                </a>
                <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
                    @csrf
                </form>
            </div>
        </header>
        <main class="terminal-content">
            @yield('content')
        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // PWA Service Worker Registration
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('/sw.js')
                    .then(reg => console.log('SW registered'))
                    .catch(err => console.log('SW registration failed', err));
            });
        }

        function updateClock() {
            const now = new Date();
            const timeString = now.toLocaleTimeString('id-ID', { hour12: false });
            document.getElementById('terminal-clock').textContent = timeString;
        }
        setInterval(updateClock, 1000);
        updateClock();

        // Online/Offline handling
        window.addEventListener('online', () => {
            const indicator = document.getElementById('online-indicator');
            indicator.querySelector('.status-dot').className = 'status-dot w-2 h-2 rounded-full bg-terminal-accent';
            indicator.querySelector('span').textContent = 'Online';
        });
        window.addEventListener('offline', () => {
            const indicator = document.getElementById('online-indicator');
            indicator.querySelector('.status-dot').className = 'status-dot w-2 h-2 rounded-full bg-terminal-danger';
            indicator.querySelector('span').textContent = 'Offline';
        });

        // Full-screen support
        function toggleFullScreen() {
            if (!document.fullscreenElement) {
                document.documentElement.requestFullscreen();
            } else {
                if (document.exitFullscreen) {
                    document.exitFullscreen();
                }
            }
        }
    </script>
    @yield('extra_js')
</body>
</html>
