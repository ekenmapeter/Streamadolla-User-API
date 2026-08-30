<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Artist Dashboard') — Streamadollar</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    @include('_partials.neu')
</head>

<body class="min-h-screen">
    <!-- Sidebar -->
    <div class="fixed inset-y-0 left-0 w-64 p-4 hidden lg:block">
        <div class="neu-sm h-full flex flex-col px-5 py-5">
            <a href="{{ route('landing') }}" class="flex items-center space-x-3 px-2 pb-5 mb-4" style="border-bottom: 1px solid rgba(75,90,114,.12)">
                <div class="neu-circle h-10 w-10 shrink-0">
                    <i class="fas fa-music text-lg" style="color: var(--neu-accent)"></i>
                </div>
                <div class="leading-tight min-w-0">
                    <p class="font-extrabold leading-tight" style="color: var(--neu-text-strong)">Streama<span class="text-gradient">dollar</span></p>
                    <p class="text-[11px] font-semibold opacity-60">Artist Portal</p>
                </div>
            </a>

            <nav class="space-y-2 text-sm font-bold">
                <a href="{{ route('artist.dashboard') }}" class="flex items-center space-x-3 px-4 py-3 rounded-full transition {{ request()->routeIs('artist.dashboard') ? 'neu-inset' : 'neu-btn' }}" style="{{ request()->routeIs('artist.dashboard') ? 'color: var(--neu-accent)' : 'color: var(--neu-text)' }}">
                    <i class="fas fa-chart-pie w-5"></i><span>Overview</span>
                </a>
                <a href="{{ route('artist.campaign.create') }}" class="flex items-center space-x-3 px-4 py-3 rounded-full transition {{ request()->routeIs('artist.campaign.create', 'artist.campaign.store') ? 'neu-inset' : 'neu-btn' }}" style="{{ request()->routeIs('artist.campaign.create', 'artist.campaign.store') ? 'color: var(--neu-accent)' : 'color: var(--neu-text)' }}">
                    <i class="fas fa-plus-circle w-5"></i><span>New Campaign</span>
                </a>
            </nav>

            <div class="mt-auto space-y-2 text-sm font-bold pt-4" style="border-top: 1px solid rgba(75,90,114,.12)">
                <form action="{{ route('logout') }}" method="POST">
                    @csrf
                    <button type="submit" class="neu-btn w-full flex items-center space-x-3 px-4 py-3 rounded-full transition" style="color: #dc2626">
                        <i class="fas fa-sign-out-alt w-5"></i><span>Logout</span>
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Mobile top bar -->
    <div class="lg:hidden p-4">
        <div class="neu-sm flex items-center justify-between px-5 py-3">
            <a href="{{ route('landing') }}" class="flex items-center space-x-3">
                <div class="neu-circle h-9 w-9"><i class="fas fa-music" style="color: var(--neu-accent)"></i></div>
                <span class="font-extrabold" style="color: var(--neu-text-strong)">Streama<span class="text-gradient">dollar</span></span>
            </a>
            <details class="relative">
                <summary class="neu-btn list-none px-4 py-2 text-sm font-bold cursor-pointer" style="color: var(--neu-text)"><i class="fas fa-bars"></i></summary>
                <div class="neu-sm absolute right-0 mt-3 w-52 p-3 space-y-1 text-sm font-bold z-50">
                    <a href="{{ route('artist.dashboard') }}" class="block px-4 py-2.5 rounded-full hover:bg-black/5" style="color: var(--neu-text)">Overview</a>
                    <a href="{{ route('artist.campaign.create') }}" class="block px-4 py-2.5 rounded-full hover:bg-black/5" style="color: var(--neu-text)">New Campaign</a>
                    <form action="{{ route('logout') }}" method="POST">
                        @csrf
                        <button type="submit" class="w-full text-left px-4 py-2.5 rounded-full hover:bg-black/5" style="color: #dc2626"><i class="fas fa-sign-out-alt mr-2"></i>Logout</button>
                    </form>
                </div>
            </details>
        </div>
    </div>

    <!-- Main -->
    <div class="lg:ml-64 p-4 lg:p-8">
        <header class="neu-inset px-6 lg:px-8 py-5 mb-8 flex items-center justify-between">
            <div>
                <h1 class="text-xl font-extrabold" style="color: var(--neu-text-strong)">@yield('header', 'Overview')</h1>
                <p class="text-sm font-medium opacity-60 mt-0.5">@yield('subheader', 'Welcome back, ' . auth()->user()->name)</p>
            </div>
            <div class="flex items-center space-x-3 text-sm font-bold">
                <span class="neu-chip" style="color: var(--neu-accent)">{{ auth()->user()->artistProfile?->stage_name ?? auth()->user()->name }}</span>
                <div class="neu-circle h-10 w-10 hidden sm:flex">
                    <span class="text-sm font-extrabold text-gradient">{{ strtoupper(substr(auth()->user()->name, 0, 2)) }}</span>
                </div>
            </div>
        </header>

        <main>
            @if (session('status'))
                <div class="mb-6 neu-sm px-5 py-4 text-sm font-bold" style="color: #16a34a">
                    <i class="fas fa-circle-check mr-2"></i>{{ session('status') }}
                </div>
            @endif
            @if ($errors->any())
                <div class="mb-6 neu-sm px-5 py-4 text-sm font-bold" style="color: #dc2626">
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