<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Stream Farm Control</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            font-family: 'Inter', sans-serif;
        }

        .glass-panel {
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.3);
        }
    </style>
</head>

<body
    class="bg-gradient-to-br from-blue-600 via-primary-600 to-indigo-800 min-h-screen flex items-center justify-center p-4">
    <div class="max-w-md w-full">
        <div class="text-center mb-10">
            <div class="inline-flex items-center justify-center h-20 w-20 rounded-2xl bg-white shadow-2xl mb-6 p-3">
                <img src="{{ asset('images/new_logo.png') }}" alt="Stream Farm" class="h-full w-full object-contain">
            </div>
            <h1 class="text-4xl font-bold text-white mb-2">Stream Farm</h1>
            <p class="text-blue-100 opacity-80">Admin Control Center</p>
        </div>

        <div class="glass-panel p-8 rounded-3xl shadow-2xl">
            <form action="{{ route('login.submit') }}" method="POST">
                @csrf
                <div class="mb-6">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Email Address</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-4 flex items-center text-gray-400">
                            <i class="fas fa-envelope"></i>
                        </span>
                        <input type="email" name="email" required value="{{ old('email') }}"
                            class="w-full pl-11 pr-4 py-4 bg-white/50 border border-gray-200 rounded-2xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-all"
                            placeholder="admin@streamadolla.com">
                    </div>
                    @error('email')
                        <p class="text-red-500 text-xs mt-2">{{ $message }}</p>
                    @enderror
                </div>

                <div class="mb-8">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Password</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-4 flex items-center text-gray-400">
                            <i class="fas fa-lock"></i>
                        </span>
                        <input type="password" name="password" required
                            class="w-full pl-11 pr-4 py-4 bg-white/50 border border-gray-200 rounded-2xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-all"
                            placeholder="••••••••">
                    </div>
                </div>

                <div class="flex items-center justify-between mb-8">
                    <label class="flex items-center text-sm text-gray-600 cursor-pointer">
                        <input type="checkbox" name="remember"
                            class="w-4 h-4 rounded text-blue-600 border-gray-300 focus:ring-blue-500 mr-2">
                        Remember me
                    </label>
                </div>

                <button type="submit"
                    class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-4 rounded-2xl shadow-lg shadow-blue-500/30 transition-all active:scale-[0.98]">
                    Sign In
                </button>
            </form>
        </div>

        <div class="mt-10 flex justify-center">
            <a href="{{ asset('download/streamadolla-official-with-mutiple-device-v5.apk') }}"
                class="flex items-center space-x-4 bg-white/10 hover:bg-white/20 text-white px-8 py-4 rounded-3xl backdrop-blur-xl border border-white/20 transition-all duration-300 group hover:shadow-2xl hover:shadow-blue-500/20 active:scale-95">
                <div
                    class="bg-gradient-to-tr from-blue-500 to-indigo-500 rounded-2xl p-3 shadow-lg group-hover:rotate-12 transition-transform">
                    <i class="fas fa-mobile-screen-button text-xl"></i>
                </div>
                <div class="text-left">
                    <p class="text-xs text-blue-100/70 font-semibold tracking-wider uppercase">Download App</p>
                    <p class="text-lg font-bold">Get Official APK</p>
                </div>
            </a>
        </div>


        <p class="text-center mt-10 text-blue-100/50 text-sm">
            &copy; {{ date('Y') }} Stream Farm Control System
        </p>
    </div>
</body>

</html>
