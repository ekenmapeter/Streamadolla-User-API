<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Email — AudioReach</title>
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
            <div class="inline-flex items-center justify-center h-14 w-14 rounded-2xl bg-gradient-to-tr from-purple-500 to-fuchsia-500 text-2xl mx-auto">📧</div>
            <h1 class="text-2xl font-bold text-white mt-5">Verify your email</h1>
            <p class="text-purple-200/60 text-sm mt-2">Enter the 6-digit code sent to your inbox.</p>
        </div>

        @if (session('status'))
            <div class="mb-6 bg-green-500/10 border border-green-500/30 text-green-300 text-sm px-4 py-3 rounded-xl">{{ session('status') }}</div>
        @endif

        <div class="glass-panel p-8 rounded-3xl">
            <form action="{{ route('artist.verify.submit') }}" method="POST">
                @csrf

                <div class="mb-5">
                    <label class="block text-sm font-semibold text-gray-300 mb-2">Email Address</label>
                    <input type="email" name="email" value="{{ old('email', $email ?? '') }}" required readonly
                        class="w-full px-4 py-3 bg-white/5 border border-gray-700 rounded-xl focus:ring-2 focus:ring-purple-500 outline-none transition-all text-white text-sm">
                </div>

                <div class="mb-8">
                    <label class="block text-sm font-semibold text-gray-300 mb-2">Verification Code</label>
                    <input type="text" name="code" required maxlength="6" inputmode="numeric"
                        class="w-full px-4 py-3 bg-white/5 border border-gray-700 rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-purple-500 outline-none transition-all text-white text-center text-2xl tracking-[0.5em]"
                        placeholder="••••••">
                    @error('code')
                        <p class="text-red-400 text-xs mt-2">{{ $message }}</p>
                    @enderror
                </div>

                <button type="submit"
                    class="w-full bg-purple-600 hover:bg-purple-700 text-white font-bold py-3.5 rounded-xl shadow-lg shadow-purple-900/40 transition-all active:scale-[0.98]">
                    Verify Email
                </button>
            </form>
        </div>

        <p class="text-center mt-6 text-gray-400 text-sm">
            <a href="{{ route('login') }}" class="text-purple-400 hover:text-purple-300 font-semibold">Back to login</a>
        </p>
    </div>
</body>

</html>
