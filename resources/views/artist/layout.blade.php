<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Artist Dashboard') — AudioReach</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            font-family: 'Inter', sans-serif;
        }
    </style>
</head>

<body class="bg-gray-950 text-white min-h-screen">
    <!-- Sidebar -->
    <div class="fixed inset-y-0 left-0 w-64 bg-gray-900 border-r border-white/10">
        <div class="flex items-center space-x-3 px-6 py-5 border-b border-white/10">
            <div class="h-9 w-9 rounded-xl bg-gradient-to-tr from-purple-500 to-fuchsia-500 flex items-center justify-center text-lg">🎵</div>
            <div>
                <p class="font-bold leading-tight">AudioReach</p>
                <p class="text-xs text-gray-500">Artist Portal</p>
            </div>
        </div>

        <nav class="p-4 space-y-1">
            <a href="{{ route('artist.dashboard') }}" class="flex items-center space-x-3 px-4 py-3 rounded-xl text-sm font-medium {{ request()->routeIs('artist.dashboard') ? 'bg-purple-600 text-white' : 'text-gray-400 hover:bg-white/5 hover:text-white transition' }}">
                <i class="fas fa-chart-pie w-5"></i><span>Overview</span>
            </a>
            <a href="{{ route('artist.campaign.create') }}" class="flex items-center space-x-3 px-4 py-3 rounded-xl text-sm font-medium {{ request()->routeIs('artist.campaign.create', 'artist.campaign.store') ? 'bg-purple-600 text-white' : 'text-gray-400 hover:bg-white/5 hover:text-white transition' }}">
                <i class="fas fa-plus-circle w-5"></i><span>New Campaign</span>
            </a>
            <form action="{{ route('logout') }}" method="POST" class="mt-8">
                @csrf
                <button type="submit" class="w-full flex items-center space-x-3 px-4 py-3 rounded-xl text-sm font-medium text-gray-500 hover:bg-red-500/10 hover:text-red-400 transition">
                    <i class="fas fa-sign-out-alt w-5"></i><span>Logout</span>
                </button>
            </form>
        </nav>
    </div>

    <!-- Main -->
    <div class="ml-64">
        <header class="border-b border-white/10 px-8 py-5 flex items-center justify-between bg-gray-900/50">
            <div>
                <h1 class="text-xl font-bold">@yield('header', 'Overview')</h1>
                <p class="text-sm text-gray-500">@yield('subheader', 'Welcome back, ' . auth()->user()->name)</p>
            </div>
            <div class="flex items-center space-x-3 text-sm text-gray-400">
                <span class="bg-white/5 px-4 py-2 rounded-xl">{{ auth()->user()->artistProfile?->stage_name ?? auth()->user()->name }}</span>
            </div>
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