<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Artist Signup — Streamadollar</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    @include('_partials.neu')
</head>

<body class="min-h-screen flex items-center justify-center p-4 lg:p-8">
    <div class="max-w-5xl w-full grid lg:grid-cols-2 gap-10 items-center">

        {{-- Image side --}}
        <div class="hidden lg:block relative">
            <div class="neu p-4">
                <img
                    src="https://images.unsplash.com/photo-1470225620780-dba8ba36b745?q=80&w=1200&auto=format&fit=crop"
                    alt="DJ mixing music on a turntable"
                    class="rounded-3xl w-full object-cover aspect-[4/5]"
                    loading="lazy">
            </div>
            <div class="neu-sm absolute -bottom-6 left-1/2 -translate-x-1/2 px-6 py-4 text-center whitespace-nowrap">
                <p class="text-xs font-bold tracking-widest opacity-60 mb-1">LISTENERS EARNING</p>
                <p class="text-xl font-extrabold text-gradient">12,400+</p>
            </div>
            <a href="{{ route('landing') }}" class="inline-flex items-center space-x-3 absolute -top-8 left-2">
                <div class="neu-circle h-11 w-11">
                    <i class="fas fa-music text-lg" style="color: var(--neu-accent)"></i>
                </div>
                <span class="text-xl font-extrabold" style="color: var(--neu-text-strong)">Streama<span class="text-gradient">dollar</span></span>
            </a>
        </div>

        {{-- Form side --}}
        <div class="max-w-md w-full mx-auto">
            <div class="text-center lg:text-left mb-8">
                <a href="{{ route('landing') }}" class="lg:hidden inline-flex items-center space-x-3 mb-6">
                    <div class="neu-circle h-12 w-12">
                        <i class="fas fa-music text-xl" style="color: var(--neu-accent)"></i>
                    </div>
                    <span class="text-2xl font-extrabold" style="color: var(--neu-text-strong)">Streama<span class="text-gradient">dollar</span></span>
                </a>
                <h1 class="text-3xl font-extrabold" style="color: var(--neu-text-strong)">Create your artist account</h1>
                <p class="text-sm font-semibold mt-2 opacity-60">Real listeners. Real plays. Real promotion.</p>
            </div>

            @if (session('status'))
                <div class="mb-6 neu-sm px-5 py-3 text-sm font-bold" style="color: #16a34a"><i class="fas fa-circle-check mr-2"></i>{{ session('status') }}</div>
            @endif

            <div class="neu p-8">
                <form action="{{ route('artist.signup.submit') }}" method="POST">
                    @csrf

                    <div class="mb-5">
                        <label class="block text-sm font-bold mb-2" style="color: var(--neu-text-strong)">Full Name</label>
                        <input type="text" name="name" value="{{ old('name') }}" required
                            class="neu-input"
                            placeholder="e.g. Adeola Johnson">
                        @error('name')
                            <p class="text-xs font-semibold mt-2" style="color: #dc2626">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="mb-5">
                        <label class="block text-sm font-bold mb-2" style="color: var(--neu-text-strong)">Stage Name</label>
                        <input type="text" name="stage_name" value="{{ old('stage_name') }}" required
                            class="neu-input"
                            placeholder="e.g. Adeola J">
                        @error('stage_name')
                            <p class="text-xs font-semibold mt-2" style="color: #dc2626">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="mb-5">
                        <label class="block text-sm font-bold mb-2" style="color: var(--neu-text-strong)">Email Address</label>
                        <input type="email" name="email" value="{{ old('email') }}" required
                            class="neu-input"
                            placeholder="you@example.com">
                        @error('email')
                            <p class="text-xs font-semibold mt-2" style="color: #dc2626">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="mb-5">
                        <label class="block text-sm font-bold mb-2" style="color: var(--neu-text-strong)">Phone (optional)</label>
                        <input type="text" name="phone" value="{{ old('phone') }}"
                            class="neu-input"
                            placeholder="+234...">
                    </div>

                    <div class="mb-5">
                        <label class="block text-sm font-bold mb-2" style="color: var(--neu-text-strong)">Password</label>
                        <input type="password" name="password" required minlength="8"
                            class="neu-input"
                            placeholder="••••••••">
                        @error('password')
                            <p class="text-xs font-semibold mt-2" style="color: #dc2626">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="mb-8">
                        <label class="block text-sm font-bold mb-2" style="color: var(--neu-text-strong)">Confirm Password</label>
                        <input type="password" name="password_confirmation" required
                            class="neu-input"
                            placeholder="••••••••">
                    </div>

                    <button type="submit" class="neu-accent w-full py-4 font-extrabold text-white">
                        <i class="fas fa-user-plus mr-2"></i>Create Account
                    </button>
                </form>
            </div>

            <p class="text-center mt-6 text-sm font-semibold opacity-70">
                Already have an account?
                <a href="{{ route('login') }}" class="font-extrabold" style="color: var(--neu-accent)">Sign in</a>
            </p>
        </div>
    </div>
</body>

</html>