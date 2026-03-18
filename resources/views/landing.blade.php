<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Majar Signature - Welcome</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gradient-to-br from-amber-50 via-orange-50 to-yellow-50">
    <nav class="fixed w-full bg-white/80 backdrop-blur-md shadow-sm z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <div class="flex items-center space-x-2">
                    <div class="w-8 h-8 bg-gradient-to-br from-orange-500 to-amber-400 rounded-lg flex items-center justify-center">
                        <span class="text-black font-bold text-lg">M</span>
                    </div>
                    <span class="font-bold text-xl text-gray-900">Majar Signature</span>
                </div>
                <div class="flex items-center space-x-4">
                    @auth
                        <a href="{{ route('dashboard') }}" class="text-gray-700 hover:text-orange-600 transition">Dashboard</a>
                        <form method="POST" action="{{ route('logout') }}" class="inline">
                            @csrf
                            <button type="submit" class="px-4 py-2 bg-gray-900 hover:bg-black text-white rounded-lg transition">
                                Logout
                            </button>
                        </form>
                    @else
                        <a href="{{ route('login') }}" class="px-4 py-2 text-gray-700 hover:bg-orange-50 rounded-lg transition">
                            Sign In
                        </a>
                        <a href="{{ route('login') }}" class="px-4 py-2 bg-gradient-to-r from-orange-500 to-amber-400 hover:from-orange-600 hover:to-amber-500 text-black rounded-lg transition shadow-lg">
                            Get Started
                        </a>
                    @endauth
                </div>
            </div>
        </div>
    </nav>

    <div class="min-h-screen pt-32 px-4 sm:px-6 lg:px-8">
        <div class="max-w-7xl mx-auto">
            <div class="grid md:grid-cols-2 gap-12 items-center">
                <div>
                    <h1 class="text-5xl md:text-6xl font-bold text-gray-900 mb-6">
                        Welcome to <span class="bg-gradient-to-r from-orange-500 to-amber-400 bg-clip-text text-transparent">Majar Signature</span>
                    </h1>
                    <p class="text-xl text-gray-600 mb-8 leading-relaxed">
                        A modern, intuitive F&B application designed to help you manage orders, tables, and payments in real time.
                    </p>
                    <div class="flex flex-col sm:flex-row gap-4">
                        <a href="{{ route('login') }}" class="px-8 py-3 bg-gradient-to-r from-orange-500 to-amber-400 hover:from-orange-600 hover:to-amber-500 text-black rounded-lg font-semibold transition shadow-lg text-center">
                            Sign In Now
                        </a>
                        <button onclick="document.getElementById('features').scrollIntoView({behavior: 'smooth'})" class="px-8 py-3 bg-white hover:bg-orange-50 text-gray-900 border border-orange-100 rounded-lg font-semibold transition">
                            Learn More
                        </button>
                    </div>
                </div>
                <div class="hidden md:block">
                    <div class="relative">
                        <div class="absolute inset-0 bg-gradient-to-r from-orange-300 to-amber-400 rounded-2xl opacity-30 blur-2xl"></div>
                        <div class="relative bg-white rounded-2xl shadow-xl p-8">
                            <div class="space-y-4">
                                <div class="h-3 bg-gradient-to-r from-orange-500 to-amber-400 rounded-full w-3/4"></div>
                                <div class="h-3 bg-gray-200 rounded-full"></div>
                                <div class="h-3 bg-gray-200 rounded-full w-5/6"></div>
                                <div class="pt-4 space-y-3">
                                    <div class="flex gap-2">
                                        <div class="w-10 h-10 bg-orange-100 rounded-lg"></div>
                                        <div class="flex-1">
                                            <div class="h-2 bg-gray-200 rounded w-2/3"></div>
                                            <div class="h-2 bg-gray-100 rounded w-1/2 mt-2"></div>
                                        </div>
                                    </div>
                                    <div class="flex gap-2">
                                        <div class="w-10 h-10 bg-amber-100 rounded-lg"></div>
                                        <div class="flex-1">
                                            <div class="h-2 bg-gray-200 rounded w-2/3"></div>
                                            <div class="h-2 bg-gray-100 rounded w-1/2 mt-2"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Features Section -->
    <div id="features" class="py-20 px-4 sm:px-6 lg:px-8 bg-white">
        <div class="max-w-7xl mx-auto">
            <div class="text-center mb-16">
                <h2 class="text-4xl font-bold text-gray-900 mb-4">Why Majar Signature OS</h2>
                <p class="text-xl text-gray-600">Everything you need to run a modern F&B business</p>
            </div>
            <div class="grid md:grid-cols-3 gap-8">
                <div class="p-8 bg-gradient-to-br from-amber-50 to-orange-50 rounded-xl hover:shadow-lg transition">
                    <div class="w-12 h-12 bg-orange-500 rounded-lg flex items-center justify-center mb-4">
                        <span class="text-white text-xl">🔐</span>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-2">Smart Ordering</h3>
                    <p class="text-gray-600">QR menu, digital orders, and real-time kitchen updates.</p>
                </div>
                <div class="p-8 bg-gradient-to-br from-orange-50 to-yellow-50 rounded-xl hover:shadow-lg transition">
                    <div class="w-12 h-12 bg-amber-400 rounded-lg flex items-center justify-center mb-4">
                        <span class="text-white text-xl">📊</span>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-2">Owner Dashboard</h3>
                    <p class="text-gray-600">Monitor omzet, popular menu, and peak hours in one place.</p>
                </div>
                <div class="p-8 bg-gradient-to-br from-yellow-50 to-orange-50 rounded-xl hover:shadow-lg transition">
                    <div class="w-12 h-12 bg-red-500 rounded-lg flex items-center justify-center mb-4">
                        <span class="text-white text-xl">⚡</span>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-2">Faster Service</h3>
                    <p class="text-gray-600">Reduce waiting time and increase table turnover automatically.</p>
                </div>
            </div>
        </div>
    </div>

    <!-- CTA Section -->
    <div class="py-20 px-4 sm:px-6 lg:px-8">
        <div class="max-w-4xl mx-auto bg-gradient-to-r from-orange-500 to-amber-400 rounded-2xl p-12 text-center text-black">
            <h2 class="text-4xl font-bold mb-4">Ready to delight your customers?</h2>
            <p class="text-xl mb-8 opacity-90">Sign in and kelola restoran Anda dengan tampilan dashboard modern.</p>
            <p class="text-sm opacity-75 mb-4">Demo: Email: <strong>admin@admin.com</strong> | Password: <strong>admin</strong></p>
            <a href="{{ route('login') }}" class="inline-block px-8 py-3 bg-white text-orange-600 hover:bg-orange-50 rounded-lg font-semibold transition">
                Sign In Now
            </a>
        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-gray-900 text-gray-400 py-8 px-4 sm:px-6 lg:px-8">
        <div class="max-w-7xl mx-auto text-center">
            <p>&copy; 2026 Majar Signature. All rights reserved.</p>
        </div>
    </footer>
</body>
</html>
