<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Artist Login — Streamadollar</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    @include('_partials.neu')
</head>

<body class="min-h-screen flex items-center justify-center p-4">
    <div class="max-w-md w-full">
        <div class="text-center mb-8">
            <a href="{{ route('landing') }}" class="inline-flex items-center space-x-3">
                <div class="neu-circle h-12 w-12">
                    <i class="fas fa-music text-xl" style="color: var(--neu-accent)"></i>
                </div>
                <span class="text-2xl font-extrabold" style="color: var(--neu-text-strong)">Streama<span class="text-gradient">dollar</span></span>
            </a>
            <h1 class="text-2xl font-extrabold mt-6" style="color: var(--neu-text-strong)">Welcome back</h1>
            <p class="text-sm font-semibold mt-2 opacity-60">Sign in to your artist dashboard</p>
        </div>

        <div class="neu p-8">
            <form action="{{ route('login.submit') }}" method="POST">
                @csrf

                @if (session('status'))
                    <div class="mb-6 neu-sm px-5 py-3 text-sm font-bold" style="color: #16a34a"><i class="fas fa-circle-check mr-2"></i>{{ session('status') }}</div>
                @endif

                <div class="mb-5">
                    <label class="block text-sm font-bold mb-2" style="color: var(--neu-text-strong)">Email Address</label>
                    <input type="email" name="email" value="{{ old('email') }}" required
                        class="neu-input"
                        placeholder="you@example.com">
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

                <button type="submit" class="neu-accent w-full py-4 font-extrabold text-white">
                    <i class="fas fa-sign-in-alt mr-2"></i>Sign In
                </button>
            </form>
        </div>

        <p class="text-center mt-6 text-sm font-semibold opacity-70">
            New to Streamadollar?
            <a href="{{ route('artist.signup') }}" class="font-extrabold" style="color: var(--neu-accent)">Create an account</a>
        </p>
    </div>
</body>

</html>