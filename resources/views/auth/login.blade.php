<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login — Streamadollar</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    @include('_partials.neu')
</head>

<body class="min-h-screen">
    @include('_partials.guest-nav')

    <main class="min-h-[calc(100vh-96px)] flex items-center justify-center p-4">
    <div class="max-w-md w-full">
        <div class="text-center mb-10">
            <div class="neu-circle h-20 w-20 mx-auto mb-6">
                <i class="fas fa-music text-3xl" style="color: var(--neu-accent)"></i>
            </div>
            <h1 class="text-4xl font-extrabold mb-2" style="color: var(--neu-text-strong)">Streama<span class="text-gradient">dollar</span></h1>
            <p class="font-semibold opacity-60">Admin Command Center</p>
        </div>

        <div class="neu p-8">
            <form action="{{ route('login.submit') }}" method="POST">
                @csrf
                <div class="mb-6">
                    <label class="block text-sm font-bold mb-2" style="color: var(--neu-text-strong)">Email Address</label>
                    <input type="email" name="email" required value="{{ old('email') }}"
                        class="neu-input"
                        placeholder="admin@streamadolla.com">
                    @error('email')
                        <p class="text-xs font-semibold mt-2" style="color: #dc2626">{{ $message }}</p>
                    @enderror
                </div>

                <div class="mb-8">
                    <label class="block text-sm font-bold mb-2" style="color: var(--neu-text-strong)">Password</label>
                    <input type="password" name="password" required
                        class="neu-input"
                        placeholder="••••••••">
                </div>

                <div class="flex items-center justify-between mb-8">
                    <label class="flex items-center text-sm font-semibold cursor-pointer" style="color: var(--neu-text)">
                        <input type="checkbox" name="remember"
                            class="w-4 h-4 rounded accent-purple-500 mr-2">
                        Remember me
                    </label>
                </div>

                <button type="submit" class="neu-accent w-full py-4 font-extrabold text-white">
                    <i class="fas fa-sign-in-alt mr-2"></i>Sign In
                </button>
            </form>
        </div>

        <div class="mt-10 flex justify-center">
            <a href="{{ asset('download/streamadolla-reward-app-v1.0.1.apk') }}"
                class="neu-btn flex items-center space-x-4 px-8 py-4">
                <div class="neu-circle h-12 w-12" style="color: var(--neu-accent)">
                    <i class="fas fa-mobile-screen-button text-xl"></i>
                </div>
                <div class="text-left">
                    <p class="text-xs font-bold tracking-wider uppercase opacity-60">Download App</p>
                    <p class="text-lg font-extrabold" style="color: var(--neu-text-strong)">Get Official APK</p>
                </div>
            </a>
        </div>

        <p class="text-center mt-10 text-sm font-semibold opacity-50">
            &copy; {{ date('Y') }} Streamadollar — Listen. Earn. Repeat.
        </p>
    </div>
    </main>
</body>

</html>