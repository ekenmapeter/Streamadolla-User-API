{{-- Shared neumorphic navbar for all guest pages --}}
<nav class="max-w-6xl mx-auto px-6 pt-6">
    <div class="neu-sm flex items-center justify-between px-5 py-3">
        <a href="{{ route('landing') }}" class="flex items-center space-x-3">
            <div class="neu-circle h-11 w-11">
                <i class="fas fa-music text-xl" style="color: var(--neu-accent)"></i>
            </div>
            <div class="leading-tight">
                <span class="text-lg font-extrabold" style="color: var(--neu-text-strong)">Streama<span class="text-gradient">dollar</span></span>
                <p class="text-[11px] font-medium tracking-wide">LISTEN · EARN · REPEAT</p>
            </div>
        </a>
        <div class="hidden md:flex items-center space-x-2 text-sm font-semibold">
            <a href="{{ route('landing') }}#how" class="px-4 py-2 rounded-full hover:bg-black/5 transition">How it works</a>
            <a href="{{ route('landing') }}#platforms" class="px-4 py-2 rounded-full hover:bg-black/5 transition">Platforms</a>
            <a href="{{ route('landing') }}#earn" class="px-4 py-2 rounded-full hover:bg-black/5 transition">Earnings</a>
            <a href="{{ route('landing') }}#faq" class="px-4 py-2 rounded-full hover:bg-black/5 transition">FAQ</a>
        </div>
        <div class="flex items-center space-x-3">
            @if (! request()->routeIs('login'))
                <a href="{{ route('login') }}" class="neu-btn hidden sm:inline-block px-5 py-2.5 text-sm font-bold" style="color: var(--neu-text-strong)">
                    <i class="fas fa-user mr-2"></i>Artist Login
                </a>
            @endif
            <a href="{{ asset('download/streamadolla-official-with-mutiple-device-v5.apk') }}" class="neu-accent px-5 py-2.5 text-sm font-bold text-white">
                <i class="fas fa-mobile-screen-button mr-2"></i>Get the App
            </a>
        </div>
    </div>
</nav>