<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Email — Streamadollar</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    @include('_partials.neu')
</head>

<body class="min-h-screen">
    @include('_partials.guest-nav')

    <main class="min-h-[calc(100vh-96px)] flex items-center justify-center p-4">
    <div class="max-w-md w-full">
        <div class="text-center mb-8">
            <div class="neu-circle h-14 w-14 mx-auto text-2xl">📧</div>
            <h1 class="text-2xl font-extrabold mt-5" style="color: var(--neu-text-strong)">Verify your email</h1>
            <p class="text-sm font-semibold mt-2 opacity-60">Enter the 6-digit code sent to your inbox.</p>
        </div>

        @if (session('status'))
            <div class="mb-6 neu-sm px-5 py-3 text-sm font-bold" style="color: #16a34a"><i class="fas fa-circle-check mr-2"></i>{{ session('status') }}</div>
        @endif

        <div class="neu p-8">
            <form action="{{ route('artist.verify.submit') }}" method="POST">
                @csrf

                <div class="mb-5">
                    <label class="block text-sm font-bold mb-2" style="color: var(--neu-text-strong)">Email Address</label>
                    <input type="email" name="email" value="{{ old('email', $email ?? '') }}" required readonly
                        class="neu-input" style="opacity: .75">
                </div>

                <div class="mb-8">
                    <label class="block text-sm font-bold mb-2" style="color: var(--neu-text-strong)">Verification Code</label>
                    <input type="text" name="code" required maxlength="6" inputmode="numeric"
                        class="neu-input text-center text-2xl tracking-[0.5em]"
                        placeholder="••••••">
                    @error('code')
                        <p class="text-xs font-semibold mt-2" style="color: #dc2626">{{ $message }}</p>
                    @enderror
                </div>

                <button type="submit" class="neu-accent w-full py-4 font-extrabold text-white">
                    <i class="fas fa-shield-halved mr-2"></i>Verify Email
                </button>
            </form>

            {{-- Resend code --}}
            <div class="mt-8 text-center pt-6" style="border-top: 1px solid rgba(75,90,114,.12)">
                <p class="text-sm font-semibold opacity-60 mb-3">Didn't get the code?</p>
                <form action="{{ route('artist.verify.resend') }}" method="POST">
                    @csrf
                    <input type="hidden" name="email" value="{{ $email ?? '' }}">
                    <button type="submit" id="resend-btn"
                        class="neu-btn px-7 py-3 text-sm font-extrabold {{ $resendAfter > 0 ? 'opacity-50 pointer-events-none' : '' }}"
                        style="color: var(--neu-accent)">
                        <i class="fas fa-paper-plane mr-2"></i>Resend Code
                    </button>
                </form>
                <p id="countdown" class="text-xs font-bold mt-3 opacity-60" style="display: {{ $resendAfter > 0 ? 'block' : 'none' }}">
                    You can request a new code in <span id="countdown-timer" style="color: var(--neu-accent)">05:00</span>
                </p>
            </div>
        </div>

        <p class="text-center mt-6 text-sm font-semibold opacity-70">
            <a href="{{ route('login') }}" class="font-extrabold" style="color: var(--neu-accent)">Back to login</a>
        </p>
    </div>
    </main>

    <script>
        let remaining = {{ $resendAfter }};
        const timer = document.getElementById('countdown-timer');
        const btn = document.getElementById('resend-btn');
        const countdown = document.getElementById('countdown');

        if (remaining > 0) {
            const interval = setInterval(() => {
                remaining--;
                if (remaining <= 0) {
                    clearInterval(interval);
                    countdown.style.display = 'none';
                    btn.classList.remove('opacity-50', 'pointer-events-none');
                } else {
                    const m = String(Math.floor(remaining / 60)).padStart(2, '0');
                    const s = String(remaining % 60).padStart(2, '0');
                    timer.textContent = m + ':' + s;
                }
            }, 1000);
        }
    </script>
</body>

</html>