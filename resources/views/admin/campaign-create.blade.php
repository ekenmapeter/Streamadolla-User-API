@extends('admin.layout')

@section('header', 'New Campaign')
@section('subtitle', 'Create a promotion campaign on behalf of an artist')

@section('content')
    <div class="max-w-3xl">
        <form action="{{ route('admin.campaigns.store') }}" method="POST" class="bg-gray-900 border border-white/10 rounded-2xl p-8">
            @csrf

            <div class="mb-6">
                <label class="block text-sm font-semibold text-gray-300 mb-2">Artist</label>
                <select name="artist_id" required
                    class="w-full px-4 py-3 bg-white/5 border border-gray-700 rounded-xl focus:ring-2 focus:ring-purple-500 outline-none transition-all text-white text-sm">
                    <option value="" disabled {{ old('artist_id') ? '' : 'selected' }}>Select an artist…</option>
                    @foreach ($artists as $artist)
                        <option value="{{ $artist->id }}" {{ old('artist_id') == $artist->id ? 'selected' : '' }} class="bg-gray-900">
                            {{ $artist->name }} — {{ $artist->email }}
                        </option>
                    @endforeach
                </select>
                @if ($artists->isEmpty())
                    <p class="text-xs text-amber-400 mt-1.5">No artist accounts exist yet.</p>
                @endif
            </div>

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
                <p class="text-xs text-gray-500 mt-1.5">YouTube, Spotify, Audiomack or Boomplay links supported.</p>
            </div>

            <div class="mb-6">
                <label class="block text-sm font-semibold text-gray-300 mb-2">Platform</label>
                <select name="platform" required
                    class="w-full px-4 py-3 bg-white/5 border border-gray-700 rounded-xl focus:ring-2 focus:ring-purple-500 outline-none transition-all text-white text-sm">
                    <option value="youtube" {{ old('platform') === 'youtube' ? 'selected' : '' }}>YouTube</option>
                    <option value="spotify" {{ old('platform') === 'spotify' ? 'selected' : '' }}>Spotify</option>
                    <option value="audiomack" {{ old('platform') === 'audiomack' ? 'selected' : '' }}>Audiomack</option>
                    <option value="boomplay" {{ old('platform') === 'boomplay' ? 'selected' : '' }}>Boomplay</option>
                    <option value="apple_music" {{ old('platform') === 'apple_music' ? 'selected' : '' }}>Apple Music</option>
                    <option value="other" {{ old('platform') === 'other' ? 'selected' : '' }}>Other / Direct upload</option>
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
                <div class="space-y-3">
                    @foreach ($packages as $package)
                        <label class="flex items-center justify-between p-5 rounded-xl border border-white/10 hover:border-purple-500/50 transition cursor-pointer">
                            <div class="flex items-center space-x-4">
                                <input type="radio" name="package_id" value="{{ $package->id }}" required {{ old('package_id') == $package->id ? 'checked' : '' }} class="accent-purple-500">
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

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 mb-8">
                <div>
                    <label class="block text-sm font-semibold text-gray-300 mb-2">Start Date (optional)</label>
                    <input type="date" name="starts_at" value="{{ old('starts_at') }}"
                        class="w-full px-4 py-3 bg-white/5 border border-gray-700 rounded-xl focus:ring-2 focus:ring-purple-500 outline-none transition-all text-white text-sm">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-300 mb-2">Status</label>
                    <select name="status" required
                        class="w-full px-4 py-3 bg-white/5 border border-gray-700 rounded-xl focus:ring-2 focus:ring-purple-500 outline-none transition-all text-white text-sm">
                        <option value="active" {{ old('status', 'active') === 'active' ? 'selected' : '' }}>Active (distribute now)</option>
                        <option value="draft" {{ old('status') === 'draft' ? 'selected' : '' }}>Draft (activate later)</option>
                    </select>
                </div>
            </div>

            <button type="submit"
                class="w-full bg-purple-600 hover:bg-purple-700 text-white font-bold py-4 rounded-xl shadow-lg shadow-purple-900/40 transition-all active:scale-[0.98]">
                <i class="fas fa-bullhorn mr-2"></i>Create Campaign
            </button>
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