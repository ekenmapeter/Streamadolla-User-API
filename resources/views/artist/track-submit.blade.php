@extends('artist.layout')

@section('header', 'Submit a Track')
@section('subheader', 'Pay for a stream budget — an admin verifies your payment, then your track goes live')

@section('content')
    <div class="max-w-3xl">
        <form action="{{ route('artist.track.store') }}" method="POST" class="bg-gray-900 border border-white/10 rounded-2xl p-8">
            @csrf

            <div class="mb-6">
                <label class="block text-sm font-semibold text-gray-300 mb-2">Track Title</label>
                <input type="text" name="title" value="{{ old('title') }}" required maxlength="200"
                    class="w-full px-4 py-3 bg-white/5 border border-gray-700 rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-purple-500 outline-none transition-all text-white text-sm"
                    placeholder="e.g. 'Midnight Vibe'">
            </div>

            <div class="mb-6">
                <label class="block text-sm font-semibold text-gray-300 mb-2">Track / Video URL</label>
                <input type="url" name="media_url" value="{{ old('media_url') }}" required maxlength="500"
                    class="w-full px-4 py-3 bg-white/5 border border-gray-700 rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-purple-500 outline-none transition-all text-white text-sm"
                    placeholder="https://www.youtube.com/watch?v=... or Spotify/Audiomack link">
                <p class="text-xs text-gray-500 mt-1.5">This track will be streamed on rented devices across the platform.</p>
            </div>

            <div class="grid md:grid-cols-2 gap-6 mb-6">
                <div>
                    <label class="block text-sm font-semibold text-gray-300 mb-2">Platform</label>
                    <select name="platform" required
                        class="w-full px-4 py-3 bg-white/5 border border-gray-700 rounded-xl focus:ring-2 focus:ring-purple-500 outline-none transition-all text-white text-sm">
                        <option value="youtube">YouTube</option>
                        <option value="spotify">Spotify</option>
                        <option value="audiomack">Audiomack</option>
                        <option value="boomplay">Boomplay</option>
                        <option value="apple_music">Apple Music</option>
                        <option value="tidal">Tidal</option>
                        <option value="iheart">iHeartRadio</option>
                        <option value="other">Other</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-300 mb-2">Genre (optional)</label>
                    <input type="text" name="genre" value="{{ old('genre') }}" maxlength="50"
                        class="w-full px-4 py-3 bg-white/5 border border-gray-700 rounded-xl focus:ring-2 focus:ring-purple-500 outline-none transition-all text-white text-sm"
                        placeholder="e.g. Afrobeats">
                </div>
            </div>

            <div class="mb-6">
                <label class="block text-sm font-semibold text-gray-300 mb-2">Approx. Duration (seconds)</label>
                <input type="number" name="duration_seconds" value="{{ old('duration_seconds', 180) }}" min="30" max="7200"
                    class="w-full px-4 py-3 bg-white/5 border border-gray-700 rounded-xl focus:ring-2 focus:ring-purple-500 outline-none transition-all text-white text-sm">
                <p class="text-xs text-gray-500 mt-1.5">Used to time each stream on rented devices (default 180s).</p>
            </div>

            <div class="mb-8">
                <label class="block text-sm font-semibold text-gray-300 mb-2">Stream Budget (pick a package)</label>
                <div class="space-y-3">
                    @foreach ($packages as $package)
                        <label class="flex items-center justify-between p-5 rounded-xl border border-white/10 hover:border-purple-500/50 transition cursor-pointer">
                            <div class="flex items-center space-x-4">
                                <input type="radio" name="package_id" value="{{ $package->id }}" required class="accent-purple-500">
                                <div>
                                    <p class="font-semibold">{{ $package->name }}</p>
                                    <p class="text-sm text-gray-500">{{ $package->listen_target }} streams · {{ $package->margin_pct }}% platform fee</p>
                                </div>
                            </div>
                            <p class="text-xl font-extrabold text-purple-400">₦{{ number_format($package->price_ngn) }}</p>
                        </label>
                    @endforeach
                </div>
            </div>

            <button type="submit"
                class="w-full bg-purple-600 hover:bg-purple-700 text-white font-bold py-4 rounded-xl shadow-lg shadow-purple-900/40 transition-all active:scale-[0.98]">
                <i class="fas fa-credit-card mr-2"></i>Pay & Submit Track
            </button>
            <p class="text-xs text-gray-500 text-center mt-3">You'll be redirected to Paystack. After payment, an admin verifies and adds your track to the track list.</p>
        </form>
    </div>
@endsection