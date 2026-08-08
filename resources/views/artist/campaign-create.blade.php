@extends('artist.layout')

@section('header', 'New Campaign')
@section('subtitle', 'Submit your track for real listens')

@section('content')
    <div class="max-w-3xl">
        <form action="{{ route('artist.campaign.store') }}" method="POST" class="bg-gray-900 border border-white/10 rounded-2xl p-8">
            @csrf

            <div class="mb-6">
                <label class="block text-sm font-semibold text-gray-300 mb-2">Campaign Title</label>
                <input type="text" name="title" value="{{ old('title') }}" required maxlength="200"
                    class="w-full px-4 py-3 bg-white/5 border border-gray-700 rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-purple-500 outline-none transition-all text-white text-sm"
                    placeholder="e.g. 'Midnight Vibe' — Single Promotion">
            </div>

            <div class="mb-6">
                <label class="block text-sm font-semibold text-gray-300 mb-2">Track / Video URL</label>
                <input type="url" name="track_url" value="{{ old('track_url') }}" required maxlength="500"
                    class="w-full px-4 py-3 bg-white/5 border border-gray-700 rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-purple-500 outline-none transition-all text-white text-sm"
                    placeholder="https://www.youtube.com/watch?v=... or Spotify/Audiomack link">
                <p class="text-xs text-gray-500 mt-1.5">YouTube, Spotify, Audiomack or Boomplay links supported. Your own channel URL — the plays count on your official page.</p>
            </div>

            <div class="mb-6">
                <label class="block text-sm font-semibold text-gray-300 mb-2">Platform</label>
                <select name="platform" required
                    class="w-full px-4 py-3 bg-white/5 border border-gray-700 rounded-xl focus:ring-2 focus:ring-purple-500 outline-none transition-all text-white text-sm">
                    <option value="youtube">YouTube</option>
                    <option value="spotify">Spotify</option>
                    <option value="audiomack">Audiomack</option>
                    <option value="boomplay">Boomplay</option>
                    <option value="apple_music">Apple Music</option>
                    <option value="other">Other / Direct upload</option>
                </select>
            </div>

            <div class="mb-6">
                <label class="block text-sm font-semibold text-gray-300 mb-3">Genres (tap to select)</label>
                @php $genres = ['Afrobeats', 'Amapiano', 'Hip-Hop', 'R&B', 'Afropop', 'Gospel', 'Reggae', 'Highlife', 'Pop', 'Soul']; @endphp
                <div class="flex flex-wrap gap-2" id="genre-tags">
                    @foreach ($genres as $genre)
                        <button type="button" data-genre="{{ $genre }}"
                            class="genre-chip px-4 py-2 rounded-full border border-white/10 text-sm text-gray-400 hover:border-purple-500/50 transition">
                            {{ $genre }}
                        </button>
                    @endforeach
                </div>
                <input type="hidden" name="genres[]" id="selected-genres">
            </div>

            <div class="mb-6">
                <label class="block text-sm font-semibold text-gray-300 mb-2">Package</label>
                <div class="space-y-3" id="package-list">
                    @foreach ($packages as $package)
                        <label class="flex items-center justify-between p-5 rounded-xl border border-white/10 hover:border-purple-500/50 transition cursor-pointer package-option">
                            <div class="flex items-center space-x-4">
                                <input type="radio" name="package_id" value="{{ $package->id }}" required class="package-radio accent-purple-500">
                                <div>
                                    <p class="font-semibold">{{ $package->name }}</p>
                                    <p class="text-sm text-gray-500">{{ $package->listen_target }} listens · {{ $package->margin_pct }}% platform fee</p>
                                </div>
                            </div>
                            <p class="text-xl font-extrabold text-purple-400">₦{{ number_format($package->price_ngn) }}</p>
                        </label>
                    @endforeach
                </div>
            </div>

            <div class="mb-8">
                <label class="block text-sm font-semibold text-gray-300 mb-2">Start Date (optional)</label>
                <input type="date" name="starts_at" value="{{ old('starts_at') }}"
                    class="w-full px-4 py-3 bg-white/5 border border-gray-700 rounded-xl focus:ring-2 focus:ring-purple-500 outline-none transition-all text-white text-sm">
            </div>

            <button type="submit"
                class="w-full bg-purple-600 hover:bg-purple-700 text-white font-bold py-4 rounded-xl shadow-lg shadow-purple-900/40 transition-all active:scale-[0.98]">
                <i class="fas fa-credit-card mr-2"></i>Create Campaign & Pay
            </button>
            <p class="text-xs text-gray-500 text-center mt-3">You'll be redirected to Paystack to complete payment securely.</p>
        </form>
    </div>
@endsection

@section('scripts')
    <script>
        const selected = new Set();

        document.querySelectorAll('.genre-chip').forEach(chip => {
            chip.addEventListener('click', () => {
                const genre = chip.dataset.genre;
                if (selected.has(genre)) {
                    selected.delete(genre);
                    chip.classList.remove('border-purple-500', 'text-purple-300', 'bg-purple-500/10');
                } else {
                    selected.add(genre);
                    chip.classList.add('border-purple-500', 'text-purple-300', 'bg-purple-500/10');
                }
                document.getElementById('selected-genres').value = Array.from(selected).join(',');
            });
        });
    </script>
@endsection
