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
                            bg: '#F8FAFC', // Light Grayish White
                            panel: '#FFFFFF', // Pure White
                            accent: '#FF8C00', // Majar Orange
                            warning: '#FFC107', // Majar Yellow
                            danger: '#EF4444',
                            text: '#1E293B', // Dark Slate
                            muted: '#64748B', // Slate Muted
                            border: 'rgba(255, 140, 0, 0.2)'
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
    <div class="terminal-container">
        <div class="terminal-header">
            <div class="d-flex align-items-center gap-3">
                <div class="fw-bold fs-4 text-terminal-text">@yield('terminal_role', 'TERMINAL')</div>
                <div class="text-muted">|</div>
                <div class="text-muted">{{ $warung->name ?? '-' }}</div>
            </div>
            <div class="d-flex align-items-center gap-4">
                @yield('header_extra')
                <div class="d-flex align-items-center gap-2 text-terminal-text">
                    <i class="bi bi-person-circle fs-5"></i>
                    <span>{{ auth()->user()->name }}</span>
                </div>
                <div id="terminal-clock" class="fw-bold fs-5 text-terminal-text">00:00:00</div>
                <div id="online-indicator" class="d-flex align-items-center gap-2 px-3 py-1 rounded-pill border border-terminal-border bg-white/50 shadow-sm">
                    <div class="status-dot w-2 h-2 rounded-full bg-terminal-accent"></div>
                    <span class="text-xs font-bold uppercase tracking-wider text-terminal-text">Online</span>
                </div>
                <a href="{{ route('terminal.index') }}" class="btn btn-terminal btn-terminal-secondary py-1 px-3 shadow-sm" style="height: auto;" title="Pilih Terminal">
                    <i class="bi bi-grid-fill"></i>
                </a>
                <form action="{{ route('logout') }}" method="POST" class="m-0">
                    @csrf
                    <button type="submit" class="btn btn-terminal btn-terminal-danger py-1 px-3 text-white shadow-sm" style="height: auto;" title="End Shift / Logout">
                        <i class="bi bi-power"></i>
                    </button>
                </form>
            </div>
        </div>
        
        <div class="terminal-content">
            @yield('content')
        </div>
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
