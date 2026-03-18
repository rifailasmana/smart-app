<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Smart App</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50">
    <!-- Sidebar -->
    <div class="fixed inset-y-0 left-0 w-64 bg-gradient-to-b from-gray-900 to-gray-800 text-white shadow-xl">
        <div class="p-6 border-b border-gray-700">
            <div class="flex items-center space-x-3">
                <div class="w-10 h-10 bg-gradient-to-br from-blue-400 to-purple-500 rounded-lg flex items-center justify-center">
                    <span class="font-bold text-lg">S</span>
                </div>
                <div>
                    <h1 class="font-bold text-lg">Smart App</h1>
                    <p class="text-xs text-gray-400">Dashboard</p>
                </div>
            </div>
        </div>

        <nav class="mt-8 px-4 space-y-2">
            <a href="#" class="flex items-center space-x-3 px-4 py-3 rounded-lg bg-blue-600 text-white">
                <span class="text-xl">📊</span>
                <span class="font-semibold">Overview</span>
            </a>
            <a href="#" class="flex items-center space-x-3 px-4 py-3 rounded-lg hover:bg-gray-700 transition">
                <span class="text-xl">👥</span>
                <span>Users</span>
            </a>
            <a href="#" class="flex items-center space-x-3 px-4 py-3 rounded-lg hover:bg-gray-700 transition">
                <span class="text-xl">⚙️</span>
                <span>Settings</span>
            </a>
            <a href="#" class="flex items-center space-x-3 px-4 py-3 rounded-lg hover:bg-gray-700 transition">
                <span class="text-xl">📋</span>
                <span>Reports</span>
            </a>
        </nav>

        <div class="absolute bottom-0 left-0 right-0 p-4 border-t border-gray-700">
            <div class="flex items-center space-x-3 mb-4 px-4 py-2 bg-gray-700 rounded-lg">
                <div class="w-8 h-8 bg-gradient-to-br from-blue-400 to-purple-500 rounded-full flex items-center justify-center text-sm font-bold">
                    {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                </div>
                <div>
                    <p class="text-sm font-semibold">{{ auth()->user()->name }}</p>
                    <p class="text-xs text-gray-400 uppercase tracking-wider">{{ auth()->user()->role }}</p>
                </div>
            </div>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="w-full flex items-center space-x-2 px-4 py-2 text-red-400 hover:bg-red-600/20 rounded-lg transition text-sm font-semibold">
                    <span>🚪</span>
                    <span>Logout</span>
                </button>
            </form>
        </div>
    </div>

    <!-- Main Content -->
    <div class="ml-64">
        <!-- Top Bar -->
        <div class="bg-white border-b border-gray-200 sticky top-0 z-40">
            <div class="px-8 py-4 flex justify-between items-center">
                <h2 class="text-2xl font-bold text-gray-900">Welcome, {{ auth()->user()->name }}! 👋</h2>
                <div class="flex items-center space-x-4">
                    <button class="p-2 hover:bg-gray-100 rounded-lg transition">
                        <span class="text-xl">🔔</span>
                    </button>
                    <button class="p-2 hover:bg-gray-100 rounded-lg transition">
                        <span class="text-xl">⚙️</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- Content -->
        <div class="p-8">
            <!-- Stats Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                <div class="bg-white rounded-xl shadow p-6 hover:shadow-lg transition">
                    <div class="flex justify-between items-start">
                        <div>
                            <p class="text-gray-600 text-sm font-semibold">Total Users</p>
                            <p class="text-3xl font-bold text-gray-900 mt-2">2</p>
                            <p class="text-green-600 text-sm mt-2">+0% from last month</p>
                        </div>
                        <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center text-2xl">
                            👥
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-xl shadow p-6 hover:shadow-lg transition">
                    <div class="flex justify-between items-start">
                        <div>
                            <p class="text-gray-600 text-sm font-semibold">Active Sessions</p>
                            <p class="text-3xl font-bold text-gray-900 mt-2">1</p>
                            <p class="text-blue-600 text-sm mt-2">You are online</p>
                        </div>
                        <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center text-2xl">
                            🟢
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-xl shadow p-6 hover:shadow-lg transition">
                    <div class="flex justify-between items-start">
                        <div>
                            <p class="text-gray-600 text-sm font-semibold">Role Status</p>
                            <p class="text-3xl font-bold text-gray-900 mt-2">{{ ucfirst(auth()->user()->role) }}</p>
                            <p class="text-purple-600 text-sm mt-2">Highest Tier Access</p>
                        </div>
                        <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center text-2xl">
                            👑
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-xl shadow p-6 hover:shadow-lg transition">
                    <div class="flex justify-between items-start">
                        <div>
                            <p class="text-gray-600 text-sm font-semibold">System Status</p>
                            <p class="text-3xl font-bold text-gray-900 mt-2">Online</p>
                            <p class="text-green-600 text-sm mt-2">All systems operational</p>
                        </div>
                        <div class="w-12 h-12 bg-yellow-100 rounded-lg flex items-center justify-center text-2xl">
                            ⚡
                        </div>
                    </div>
                </div>
            </div>

            <!-- Charts Section -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
                <!-- Activity Chart -->
                <div class="bg-white rounded-xl shadow p-6">
                    <h3 class="text-lg font-bold text-gray-900 mb-4">Activity Overview</h3>
                    <div class="space-y-4">
                        <div>
                            <div class="flex justify-between items-center mb-2">
                                <span class="text-sm font-semibold text-gray-700">Page Views</span>
                                <span class="text-sm font-bold text-blue-600">68%</span>
                            </div>
                            <div class="w-full bg-gray-200 rounded-full h-2">
                                <div class="bg-blue-600 h-2 rounded-full" style="width: 68%"></div>
                            </div>
                        </div>
                        <div>
                            <div class="flex justify-between items-center mb-2">
                                <span class="text-sm font-semibold text-gray-700">User Engagement</span>
                                <span class="text-sm font-bold text-green-600">85%</span>
                            </div>
                            <div class="w-full bg-gray-200 rounded-full h-2">
                                <div class="bg-green-600 h-2 rounded-full" style="width: 85%"></div>
                            </div>
                        </div>
                        <div>
                            <div class="flex justify-between items-center mb-2">
                                <span class="text-sm font-semibold text-gray-700">Conversion Rate</span>
                                <span class="text-sm font-bold text-purple-600">42%</span>
                            </div>
                            <div class="w-full bg-gray-200 rounded-full h-2">
                                <div class="bg-purple-600 h-2 rounded-full" style="width: 42%"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="bg-white rounded-xl shadow p-6">
                    <h3 class="text-lg font-bold text-gray-900 mb-4">Quick Actions</h3>
                    <div class="space-y-3">
                        <button class="w-full flex items-center space-x-3 p-3 rounded-lg border border-gray-200 hover:bg-gray-50 transition text-left">
                            <span class="text-2xl">➕</span>
                            <div>
                                <p class="font-semibold text-gray-900">Add New User</p>
                                <p class="text-sm text-gray-600">Create a new user account</p>
                            </div>
                        </button>
                        <button class="w-full flex items-center space-x-3 p-3 rounded-lg border border-gray-200 hover:bg-gray-50 transition text-left">
                            <span class="text-2xl">📊</span>
                            <div>
                                <p class="font-semibold text-gray-900">View Reports</p>
                                <p class="text-sm text-gray-600">Check detailed analytics</p>
                            </div>
                        </button>
                        <button class="w-full flex items-center space-x-3 p-3 rounded-lg border border-gray-200 hover:bg-gray-50 transition text-left">
                            <span class="text-2xl">⚙️</span>
                            <div>
                                <p class="font-semibold text-gray-900">System Settings</p>
                                <p class="text-sm text-gray-600">Configure application settings</p>
                            </div>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Recent Activity -->
            <div class="bg-white rounded-xl shadow p-6">
                <h3 class="text-lg font-bold text-gray-900 mb-4">Recent Activity</h3>
                <div class="space-y-4">
                    <div class="flex items-center space-x-4 pb-4 border-b border-gray-200">
                        <div class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center text-sm font-bold text-blue-600">
                            👤
                        </div>
                        <div class="flex-1">
                            <p class="font-semibold text-gray-900">Admin account created</p>
                            <p class="text-sm text-gray-600">Admin account initialized</p>
                        </div>
                        <p class="text-sm text-gray-500">Today</p>
                    </div>
                    <div class="flex items-center space-x-4 pb-4 border-b border-gray-200">
                        <div class="w-10 h-10 bg-green-100 rounded-full flex items-center justify-center text-sm font-bold text-green-600">
                            ✓
                        </div>
                        <div class="flex-1">
                            <p class="font-semibold text-gray-900">System initialized</p>
                            <p class="text-sm text-gray-600">Application launched successfully</p>
                        </div>
                        <p class="text-sm text-gray-500">Today</p>
                    </div>
                    <div class="flex items-center space-x-4">
                        <div class="w-10 h-10 bg-purple-100 rounded-full flex items-center justify-center text-sm font-bold text-purple-600">
                            🎉
                        </div>
                        <div class="flex-1">
                            <p class="font-semibold text-gray-900">Welcome to Smart App</p>
                            <p class="text-sm text-gray-600">Your dashboard is ready to use</p>
                        </div>
                        <p class="text-sm text-gray-500">Today</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Admin Badge -->
    @if(auth()->user()->role === 'admin')
        <div class="fixed bottom-6 right-6 bg-gradient-to-r from-blue-500 to-purple-600 text-white rounded-full p-4 shadow-lg">
            <span class="text-2xl">👑</span>
        </div>
    @endif
</body>
</html>
