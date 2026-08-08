<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Artist Signup — AudioReach</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            font-family: 'Inter', sans-serif;
        }

        .glass-panel {
            background: rgba(255, 255, 255, 0.06);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
    </style>
</head>

<body class="bg-gradient-to-br from-gray-950 via-purple-950 to-gray-900 min-h-screen flex items-center justify-center p-4">
    <div class="max-w-md w-full">
        <div class="text-center mb-8">
            <a href="{{ route('landing') }}" class="inline-flex items-center space-x-2">
                <div class="h-12 w-12 rounded-xl bg-gradient-to-tr from-purple-500 to-fuchsia-500 flex items-center justify-center text-xl">🎵</div>
                <span class="text-2xl font-bold text-white">AudioReach</span>
            </a>
            <h1 class="text-2xl font-bold text-white mt-6">Create your artist account</h1>
            <p class="text-purple-200/60 text-sm mt-2">Real listeners. Real plays. Real promotion.</p>
        </div>

        @if (session('status'))
            <div class="mb-6 bg-green-500/10 border border-green-500/30 text-green-300 text-sm px-4 py-3 rounded-xl">{{ session('status') }}</div>
        @endif

        <div class="glass-panel p-8 rounded-3xl">
            <form action="{{ route('artist.signup.submit') }}" method="POST">
                @csrf

                <div class="mb-5">
                    <label class="block text-sm font-semibold text-gray-300 mb-2">Full Name</label>
                    <input type="text" name="name" value="{{ old('name') }}" required
                        class="w-full px-4 py-3 bg-white/5 border border-white/10 rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-purple-500 outline-none transition-all text-white text-sm"
                        placeholder="e.g. Adeola Johnson">
                    @error('name')
                        <p class="text-red-400 text-xs mt-2">{{ $message }}</p>
                    @enderror
                </div>

                <div class="mb-5">
                    <label class="block text-sm font-semibold text-gray-300 mb-2">Stage Name</label>
                    <input type="text" name="stage_name" value="{{ old('stage_name') }}" required
                        class="w-full px-4 py-3 bg-white/5 border border-gray-700 rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-purple-500 outline-none transition-all text-white text-sm placeholder-gray-500">
                    @error('stage_name')
                        <p class="text-red-400 text-xs mt-2">{{ $message }}</p>
                    @enderror
                </div>

                <div class="mb-5">
                    <label class="block text-sm font-semibold text-gray-300 mb-2">Email Address</label>
                    <input type="email" name="email" value="{{ old('email') }}" required
                        class="w-full px-4 py-3 bg-white/5 border border-gray-700 rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-purple-500 outline-none transition-all text-white text-sm placeholder-gray-500"
                        placeholder="you@example.com">
                    @error('email')
                        <p class="text-red-400 text-xs mt-2">{{ $message }}</p>
                    @enderror
                </div>

                <div class="mb-5">
                    <label class="block text-sm font-semibold text-gray-300 mb-2">Phone (optional)</label>
                    <input type="text" name="phone" value="{{ old('phone') }}"
                        class="w-full px-4 py-3 bg-white/5 border border-gray-700 rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-purple-500 outline-none transition-all text-white text-sm placeholder-gray-500"
                        placeholder="+234...">
                </div>

                <div class="mb-5">
                    <label class="block text-sm font-semibold text-gray-300 mb-2">Password</label>
                    <input type="password" name="password" required minlength="8"
                        class="w-full px-4 py-3 bg-white/5 border border-gray-700 rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-purple-500 outline-none transition-all text-white text-sm">
                    @error('password')
                        <p class="text-red-400 text-xs mt-2">{{ $message }}</p>
                    @enderror
                </div>

                <div class="mb-8">
                    <label class="block text-sm font-semibold text-gray-300 mb-2">Confirm Password</label>
                    <input type="password" name="password_confirmation" required
                        class="w-full px-4 py-3 bg-white/5 border border-gray-700 rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-purple-500 outline-none transition-all text-white text-sm">
                </div>

                <button type="submit"
                    class="w-full bg-purple-600 hover:bg-purple-700 text-white font-bold py-3.5 rounded-xl shadow-lg shadow-purple-900/40 transition-all active:scale-[0.98]">
                    Create Account
                </button>
            </form>
        </div>

        <p class="text-center mt-6 text-gray-400 text-sm">
            Already have an account?
            <a href="{{ route('login') }}" class="text-purple-400 hover:text-purple-300 font-semibold">Sign in</a>
        </p>
    </div>
</body>

</html>
