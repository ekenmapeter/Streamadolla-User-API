<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AudioReach — Real Listeners. Real Playlists. Real Promotion.</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            font-family: 'Inter', sans-serif;
        }

        .glass-card {
            background: rgba(255, 255, 255, 0.08);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
    </style>
</head>

<body class="bg-gray-950 text-white min-h-screen">
    <!-- Nav -->
    <nav class="border-b border-white/10">
        <div class="max-w-6xl mx-auto px-6 py-4 flex items-center justify-between">
            <div class="flex items-center space-x-3">
                <div class="h-10 w-10 rounded-xl bg-gradient-to-tr from-purple-500 to-fuchsia-500 flex items-center justify-center text-xl">🎵</div>
                <span class="text-xl font-bold">AudioReach</span>
            </div>
            <div class="flex items-center space-x-4">
                <a href="{{ route('login') }}" class="text-sm text-gray-300 hover:text-white transition">Artist Login</a>
                <a href="{{ route('artist.signup') }}" class="text-sm bg-purple-600 hover:bg-purple-700 px-5 py-2.5 rounded-xl font-semibold transition">Get Started</a>
            </div>
        </div>
    </nav>

    <!-- Hero -->
    <section class="max-w-6xl mx-auto px-6 pt-20 pb-24 text-center">
        <div class="inline-flex items-center space-x-2 bg-purple-500/10 border border-purple-500/30 text-purple-300 px-4 py-1.5 rounded-full text-sm mb-8">
            <span class="h-2 w-2 rounded-full bg-green-400 animate-pulse"></span>
            Real human listeners — never bots
        </div>
        <h1 class="text-5xl md:text-6xl font-extrabold leading-tight mb-6">
            Real Listeners.<br>
            <span class="bg-gradient-to-r from-purple-400 to-fuchsia-400 bg-clip-text text-transparent">Real Plays. Real Promotion.</span>
        </h1>
        <p class="text-lg text-gray-400 max-w-2xl mx-auto mb-10">
            Submit your track like you would pitch a radio station — and get a curated audience of real listeners
            who genuinely play your music. Every play is a real human, fully engaged.
        </p>
        <div class="flex items-center justify-center space-x-4">
            <a href="{{ route('artist.signup') }}" class="bg-purple-600 hover:bg-purple-700 px-8 py-4 rounded-2xl font-bold text-lg transition active:scale-[0.98]">
                Promote Your Music
            </a>
            <a href="{{ route('login') }}" class="border border-white/20 hover:bg-white/5 px-8 py-4 rounded-2xl font-semibold transition">
                Artist Dashboard
            </a>
        </div>

        <!-- Numbers -->
        <div class="grid grid-cols-3 gap-6 mt-20 max-w-3xl mx-auto">
            <div class="glass-card rounded-2xl p-6">
                <p class="text-3xl font-extrabold text-purple-400">100%</p>
                <p class="text-sm text-gray-400 mt-1">Verified human plays</p>
            </div>
            <div class="glass-card rounded-2xl p-6">
                <p class="text-3xl font-extrabold text-purple-400">15+</p>
                <p class="text-sm text-gray-400 mt-1">Minimum listen duration</p>
            </div>
            <div class="glass-card rounded-2xl p-6">
                <p class="text-3xl font-extrabold text-purple-400">Full</p>
                <p class="text-sm text-gray-400 mt-1">Fraud-checked plays</p>
            </div>
        </div>
    </section>

    <!-- How it works -->
    <section class="max-w-6xl mx-auto px-6 pb-24">
        <h2 class="text-3xl font-bold text-center mb-14">How it works</h2>
        <div class="grid md:grid-cols-3 gap-8">
            <div class="glass-card rounded-2xl p-8">
                <div class="h-12 w-12 rounded-xl bg-purple-600/20 text-purple-300 flex items-center justify-center text-xl mb-5"><i class="fas fa-paper-plane"></i></div>
                <h3 class="font-bold text-lg mb-2">1. Submit your track</h3>
                <p class="text-gray-400 text-sm leading-relaxed">Add your link, pick your genre and choose a package that defines how many listens you want.</p>
            </div>
            <div class="glass-card rounded-2xl p-8">
                <div class="h-12 w-12 rounded-xl bg-purple-600/20 text-purple-300 flex items-center justify-center text-xl mb-5"><i class="fas fa-headphones"></i></div>
                <h3 class="font-bold text-lg mb-2">2. Real listeners engage</h3>
                <p class="text-gray-400 text-sm leading-relaxed">Verified listeners play your track for a minimum duration and leave structured feedback — not bots, not skims.</p>
            </div>
            <div class="glass-card rounded-2xl p-8">
                <div class="h-12 w-12 rounded-xl bg-purple-600/20 text-purple-300 flex items-center justify-center text-xl mb-5"><i class="fas fa-chart-line"></i></div>
                <h3 class="font-bold text-lg mb-2">3. See what works</h3>
                <p class="text-gray-400 text-sm">Live dashboard with listen counts and engagement — before you spend on bigger campaigns.</p>
            </div>
        </div>
    </section>

    <!-- Packages -->
    <section class="max-w-6xl mx-auto px-6 pb-24">
        <h2 class="text-3xl font-bold text-center mb-14">Pick your promotion package</h2>
        <div class="grid md:grid-cols-3 gap-8">
            <div class="glass-card rounded-2xl p-8 text-center">
                <h3 class="font-bold text-lg">Starter</h3>
                <p class="text-4xl font-extrabold mt-4 mb-1">₦30k</p>
                <p class="text-sm text-gray-400 mb-6">100 real listens</p>
                <a href="{{ route('artist.signup') }}" class="block w-full border border-purple-500/50 text-purple-300 hover:bg-purple-500/10 py-3 rounded-xl font-semibold">Get Started</a>
            </div>
            <div class="rounded-2xl p-8 text-center bg-gradient-to-b from-purple-600 to-fuchsia-600 shadow-xl shadow-purple-900/40 scale-105">
                <h3 class="font-bold text-lg">Growth <span class="text-xs bg-white/20 px-2 py-0.5 rounded-full">Popular</span></h3>
                <p class="text-4xl font-extrabold mt-4 mb-1">₦120k</p>
                <p class="text-sm text-purple-100 mb-6">500 real listens</p>
                <a href="{{ route('artist.signup') }}" class="block w-full bg-white text-purple-700 hover:bg-purple-50 py-3 rounded-xl font-bold">Get Started</a>
            </div>
            <div class="glass-card rounded-2xl p-8 text-center">
                <h3 class="font-bold text-lg">Pro</h3>
                <p class="text-4xl font-extrabold mt-4 mb-1">₦300k</p>
                <p class="text-sm text-gray-400 mb-6">1,500 real listens</p>
                <a href="{{ route('artist.signup') }}" class="block w-full border border-purple-500/50 text-purple-300 hover:bg-purple-500/10 py-3 rounded-xl font-semibold">Get Started</a>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="border-t border-white/10">
        <div class="max-w-6xl mx-auto px-6 py-8 flex flex-col md:flex-row items-center justify-between text-sm text-gray-500">
            <div>
                <p>&copy; {{ date('Y') }} AudioReach. Real Listeners. Real Playlists. Real Promotion.</p>
            </div>
            <div class="flex items-center space-x-6 mt-4 md:mt-0">
                <a href="{{ route('login') }}" class="hover:text-gray-300 transition">Artist Login</a>
                <a href="{{ route('login') }}" class="hover:text-gray-300 transition"><i class="fas fa-lock mr-1"></i>Admin</a>
            </div>
        </div>
    </footer>
</body>

</html>

