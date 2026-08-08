<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('header', 'Command Center') — AudioReach</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            font-family: 'Inter', sans-serif;
        }

        .glass-card {
            backdrop-filter: blur(10px);
        }
    </style>
</head>

<body class="bg-gray-950 text-gray-100 min-h-screen">
    <!-- Sidebar -->
    <div class="fixed inset-y-0 left-0 w-60 bg-gray-900 border-r border-white/10">
        <div class="flex items-center space-x-3 px-5 py-5 border-b border-white/10">
            <div class="h-9 w-9 rounded-xl bg-gradient-to-tr from-purple-500 to-fuchsia-500 flex items-center justify-center text-lg">🎵</div>
            <div>
                <p class="font-bold leading-tight">Command Center</p>
                <p class="text-xs text-gray-500">AudioReach Admin</p>
            </div>
        </div>

        <nav class="p-3 space-y-1 text-sm">
            <a href="{{ route('admin.center') }}" class="flex items-center space-x-3 px-4 py-2.5 rounded-xl font-medium {{ request()->routeIs('admin.center') ? 'bg-purple-600 text-white' : 'text-gray-400 hover:bg-white/5 hover:text-white' }}">
                <i class="fas fa-chart-line w-5"></i><span>Overview</span>
            </a>
            <a href="{{ route('admin.campaigns') }}" class="flex items-center space-x-3 px-4 py-2.5 rounded-xl font-medium {{ request()->routeIs('admin.campaigns') ? 'bg-purple-600 text-white' : 'text-gray-400 hover:bg-white/5 hover:text-white' }}">
                <i class="fas fa-bullhorn w-5"></i><span>Campaigns</span>
            </a>
            <a href="{{ route('admin.listeners') }}" class="flex items-center space-x-3 px-4 py-2.5 rounded-xl font-medium {{ request()->routeIs('admin.listeners') ? 'bg-purple-600 text-white' : 'text-gray-400 hover:bg-white/5 hover:text-white' }}">
                <i class="fas fa-users w-5"></i><span>Listeners</span>
            </a>
            <a href="{{ route('admin.api-docs') }}" class="flex items-center space-x-3 px-4 py-2.5 rounded-xl font-medium {{ request()->routeIs('admin.api-docs') ? 'bg-purple-600 text-white' : 'text-gray-400 hover:bg-white/5 hover:text-white' }}">
                <i class="fas fa-code w-5"></i><span>API Docs</span>
            </a>
            <a href="{{ route('admin.payouts') }}" class="flex items-center space-x-3 px-4 py-2.5 rounded-xl font-medium {{ request()->routeIs('admin.payouts') ? 'bg-purple-600 text-white' : 'text-gray-400 hover:bg-white/5 hover:text-white' }}">
                <i class="fas fa-money-bill-transfer w-5"></i><span>Payouts</span>
            </a>
            <a href="{{ route('admin.settings') }}" class="flex items-center space-x-3 px-4 py-2.5 rounded-xl font-medium {{ request()->routeIs('admin.settings') ? 'bg-purple-600 text-white' : 'text-gray-400 hover:bg-white/5 hover:text-white' }}">
                <i class="fas fa-sliders w-5"></i><span>App Settings</span>
            </a>

            <div class="pt-6 border-t border-white/10 mt-6">
                <a href="{{ route('dashboard') }}" class="flex items-center space-x-3 px-4 py-2.5 rounded-xl font-medium text-gray-500 hover:bg-white/5 hover:text-white">
                    <i class="fas fa-truck-pickup w-5"></i><span>Fleet Control</span>
                </a>
                <form action="{{ route('logout') }}" method="POST">
                    @csrf
                    <button type="submit" class="w-full flex items-center space-x-3 px-4 py-2.5 rounded-xl font-medium text-gray-500 hover:bg-red-500/10 hover:text-red-400">
                        <i class="fas fa-sign-out-alt w-5"></i><span>Logout</span>
                    </button>
                </form>
            </div>
        </nav>
    </div>

    <!-- Main -->
    <div class="ml-60">
        <header class="border-b border-white/10 px-8 py-5">
            <h1 class="text-xl font-bold">@yield('header', 'Overview')</h1>
            <p class="text-sm text-gray-500 mt-0.5">@yield('subtitle', 'AudioReach platform monitor')</p>
        </header>

        <main class="p-8">
            @if (session('status'))
                <div class="mb-6 bg-green-500/10 border border-green-500/30 text-green-300 text-sm px-4 py-3 rounded-xl">{{ session('status') }}</div>
            @endif
            @if ($errors->any())
                <div class="mb-6 bg-red-500/10 border border-red-500/30 text-red-300 text-sm px-4 py-3 rounded-xl">
                    <ul class="list-disc list-inside">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @yield('content')
        </main>
    </div>

    @yield('scripts')
</body>

</html>