<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="Majar POS">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <title>@yield('title', 'Terminal') | {{ $warung->name ?? 'SmartOrder' }}</title>
    <link href="/manifest.json" rel="manifest">
    <meta name="theme-color" content="#22C55E">
    <link href="https://fonts.googleapis.com" rel="preconnect">
    <link href="https://fonts.gstatic.com" rel="preconnect" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        terminal: {
                            bg: '#daaa68', // Content background
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
            --terminal-bg: #daaa68;
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
            width: 100vw;
            overflow: hidden;
            /* iOS Safe Area Insets for True Fullscreen */
            padding-top: env(safe-area-inset-top, 0px);
            padding-bottom: env(safe-area-inset-bottom, 0px);
            padding-left: env(safe-area-inset-left, 0px);
            padding-right: env(safe-area-inset-right, 0px);
        }

        .terminal-container {
            display: flex;
            flex-direction: column;
            height: 100vh;
            width: 100vw;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
        }

        .terminal-header {
            height: 72px;
            background-color: #063024;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 32px;
            flex-shrink: 0;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.2);
        }

        .terminal-content {
            flex: 1;
            display: flex;
            overflow: hidden;
            background-color: var(--terminal-bg);
        }

        .terminal-content>* {
            background-color: var(--terminal-bg) !important;
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
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.03);
        }

        #terminal-clock {
            font-variant-numeric: tabular-nums;
            min-width: 100px;
            text-align: right;
        }
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
    </style>
    @yield('extra_css')
</head>

<body>
    <div class="terminal-container">
        <header class="terminal-header">
            <div class="flex items-center gap-6">
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-lg bg-terminal-accent flex items-center justify-center shadow-lg shadow-terminal-accent/20">
                        <span class="font-black text-white text-sm">M</span>
                    </div>
                    <span class="text-white font-black text-xs uppercase tracking-widest hidden md:block">Majar POS</span>
                </div>
                <div class="h-4 w-px bg-white/10 hidden md:block"></div>
                <div id="online-indicator" class="flex items-center gap-2 px-3 py-1.5 rounded-full bg-white/5 border border-white/5">
                    <div class="status-dot w-1.5 h-1.5 rounded-full bg-terminal-accent animate-pulse"></div>
                    <span class="text-[9px] font-black text-white uppercase tracking-widest">System: Online</span>
                </div>
                <button onclick="toggleFullScreen()" class="w-8 h-8 rounded-lg bg-white/5 border border-white/5 flex items-center justify-center text-white hover:bg-white/10 transition-all active:scale-90">
                    <i class="bi bi-fullscreen text-sm"></i>
                </button>
            </div>

            <div class="flex items-center gap-6">
                <div class="hidden lg:flex flex-col items-end">
                    <div id="terminal-clock" class="text-white font-black text-xs tracking-tight">--:--:-- WIB</div>
                    <div id="terminal-date" class="text-white/40 font-bold text-[8px] uppercase tracking-widest mt-0.5">---, -- --- ----</div>
                </div>
                <div class="h-4 w-px bg-white/10 hidden lg:block"></div>
                <div class="flex items-center gap-3 bg-white/5 px-3 py-1.5 rounded-xl border border-white/5">
                    <div class="w-6 h-6 rounded-lg bg-terminal-accent/20 flex items-center justify-center text-terminal-accent">
                        <i class="bi bi-person-circle text-sm"></i>
                    </div>
                    <div class="flex flex-col">
                        <span class="text-white font-black text-[10px] tracking-tight leading-none truncate max-w-[100px]">{{ Auth::user()->name ?? 'Staff' }}</span>
                        <span class="text-terminal-accent font-bold text-[7px] uppercase tracking-widest mt-0.5">@yield('terminal_role', 'User')</span>
                    </div>
                </div>
                @yield('header_extra')
                <a href="#" onclick="event.preventDefault(); document.getElementById('logout-form').submit();" class="w-8 h-8 rounded-lg bg-red-500/10 border border-red-500/20 flex items-center justify-center text-red-500 hover:bg-red-500 hover:text-white transition-all active:scale-90">
                    <i class="bi bi-power text-sm"></i>
                </a>
                <form id="logout-form" style="display: none;" action="{{ route('logout') }}" method="POST">
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
        // --- Fullscreen API Management ---
        const toggleFullScreen = () => {
            if (!document.fullscreenElement) {
                const elem = document.documentElement;
                if (elem.requestFullscreen) {
                    elem.requestFullscreen();
                } else if (elem.webkitRequestFullscreen) { /* Safari */
                    elem.webkitRequestFullscreen();
                } else if (elem.msRequestFullscreen) { /* IE11 */
                    elem.msRequestFullscreen();
                }
            } else {
                if (document.exitFullscreen) {
                    document.exitFullscreen();
                } else if (document.webkitExitFullscreen) { /* Safari */
                    document.webkitExitFullscreen();
                } else if (document.msExitFullscreen) { /* IE11 */
                    document.msExitFullscreen();
                }
            }
        };

        // Auto-Fullscreen on first click anywhere
        document.addEventListener('click', function initialInteraction() {
            if (!document.fullscreenElement) {
                toggleFullScreen();
            }
            // Remove the listener after first interaction to not spam the API
            document.removeEventListener('click', initialInteraction);
        }, { once: true });

        // --- Fullscreen Guard Logic ---
        // Ensure application stays fullscreen after input focus/blur (keyboard dismissal)
        document.addEventListener('focusout', (e) => {
            if (['INPUT', 'SELECT', 'TEXTAREA'].includes(e.target.tagName)) {
                setTimeout(() => {
                    if (!document.fullscreenElement) {
                        const elem = document.documentElement;
                        if (elem.requestFullscreen) elem.requestFullscreen();
                        else if (elem.webkitRequestFullscreen) elem.webkitRequestFullscreen();
                    }
                }, 300); // Small delay to allow OS UI to settle
            }
        });

        // Service Worker...
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('/sw.js')
                    .then(reg => console.log('SW registered'))
                    .catch(err => console.log('SW registration failed', err));
            });
        }

        function updateClock() {
            const now = new Date();
            
            // Update Time
            const timeString = now.toLocaleTimeString('id-ID', {
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit',
                hour12: false
            });
            const clockEl = document.getElementById('terminal-clock');
            if (clockEl) clockEl.textContent = timeString + ' WIB';

            // Update Date (if day changed)
            const dateString = now.toLocaleDateString('id-ID', {
                weekday: 'long',
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            });
            const dateEl = document.getElementById('terminal-date');
            if (dateEl && dateEl.textContent !== dateString) {
                dateEl.textContent = dateString;
            }
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
    </script>
    @yield('extra_js')
</body>

</html>
