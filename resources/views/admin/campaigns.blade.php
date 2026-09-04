@extends('admin.layout')

@section('header', 'Campaigns')
@section('subtitle', 'Monitor and control all promotion campaigns')

@section('content')
    <div class="flex items-center justify-end mb-6">
        <a href="{{ route('admin.campaigns.create') }}"
            class="bg-purple-600 hover:bg-purple-700 text-white text-sm font-semibold px-5 py-2.5 rounded-xl transition">
            <i class="fas fa-plus mr-1"></i>New Campaign
        </a>
    </div>

    <div class="space-y-4">
        @forelse ($campaigns as $campaign)
            <div class="bg-gray-900 border border-white/10 rounded-2xl p-6">
                <div class="flex items-start justify-between gap-4">
                    <div class="min-w-0">
                        <div class="flex items-center gap-3 flex-wrap">
                            <h3 class="font-bold truncate">{{ $campaign->title }}</h3>
                            @php
                                $colors = [
                                    'active' => ['text-green-400', 'bg-green-500/10'],
                                    'pending_payment' => ['text-amber-400', 'bg-amber-500/10'],
                                    'draft' => ['text-gray-400', 'bg-white/5'],
                                    'paused' => ['text-orange-400', 'bg-orange-500/10'],
                                    'completed' => ['text-blue-400', 'bg-blue-500/10'],
                                    'cancelled' => ['text-red-400', 'bg-red-500/10'],
                                ];
                                [$tc, $bc] = $colors[$campaign->status] ?? ['text-gray-400', 'bg-white/5'];
                            @endphp
                            <span class="text-xs font-semibold uppercase px-2.5 py-1 rounded-full {{ $bc }} {{ $tc }}">{{ str_replace('_', ' ', $campaign->status) }}</span>
                        </div>
                        <p class="text-sm text-gray-500 mt-1">
                            Artist: <b class="text-gray-300">{{ $campaign->artist?->name }}</b>
                            · {{ $campaign->platform }} · ₦{{ number_format($campaign->amount_paid_ngn ?? 0) }}
                            @if ($campaign->payment_reference)
                                · Ref: {{ $campaign->payment_reference }}
                            @endif
                        </p>
                        <p class="text-xs text-gray-600 mt-1">
                            {{ $campaign->rewarded_sessions_count }}/{{ $campaign->listen_target ?? $campaign->review_target }} paid listens · ₦{{ number_format($campaign->reward_per_review) }}/listen
                            · started {{ $campaign->starts_at?->diffForHumans() ?? '—' }}
                        </p>
                    </div>
                    <div class="flex items-center space-x-2 shrink-0">
                        <button type="button" data-title="{{ $campaign->title }}" data-url="{{ $campaign->track_url }}"
                            class="preview-btn bg-white/5 hover:bg-white/10 text-gray-300 text-sm font-semibold px-4 py-2 rounded-xl transition">
                            <i class="fas fa-eye mr-1"></i>Preview
                        </button>
                        @if ($campaign->status === 'pending_payment' || $campaign->status === 'paused' || $campaign->status === 'draft')
                            <form action="{{ route('admin.campaigns.activate', $campaign) }}" method="POST">
                                @csrf
                                <button class="bg-green-600 hover:bg-green-700 text-white text-sm font-semibold px-4 py-2 rounded-xl transition">
                                    <i class="fas fa-play mr-1"></i>Activate
                                </button>
                            </form>
                        @endif
                        @if ($campaign->status === 'active')
                            <form action="{{ route('admin.campaigns.pause', $campaign) }}" method="POST">
                                @csrf
                                <button class="bg-white/5 hover:bg-white/10 text-gray-300 text-sm font-semibold px-4 py-2 rounded-xl transition">
                                    <i class="fas fa-pause mr-1"></i>Pause
                                </button>
                            </form>
                        @endif
                    </div>
                </div>
            </div>
        @empty
            <div class="bg-gray-900 border border-dashed border-white/20 rounded-2xl p-14 text-center">
                <p class="text-gray-400">No campaigns yet.</p>
            </div>
        @endforelse
    </div>

    <div class="mt-6">{{ $campaigns->links() }}</div>

    {{-- Preview modal --}}
    <div id="preview-modal" class="fixed inset-0 z-50 hidden items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/80" onclick="closePreview()"></div>
        <div class="relative bg-gray-900 border border-white/10 rounded-2xl w-full max-w-2xl overflow-hidden">
            <div class="flex items-center justify-between px-6 py-4 border-b border-white/10">
                <h3 id="preview-title" class="font-bold truncate"></h3>
                <button onclick="closePreview()" class="text-gray-400 hover:text-white transition"><i class="fas fa-xmark text-lg"></i></button>
            </div>
            <div class="p-6">
                <div id="preview-player" class="aspect-video bg-black rounded-xl overflow-hidden hidden">
                    <iframe id="preview-iframe" class="w-full h-full" frameborder="0" allow="autoplay; encrypted-media; picture-in-picture" allowfullscreen></iframe>
                </div>
                <div id="preview-fallback" class="hidden text-center py-14">
                    <p class="text-gray-400 mb-4">This track isn't directly embeddable.</p>
                    <a id="preview-link" href="#" target="_blank" rel="noopener"
                        class="inline-flex bg-purple-600 hover:bg-purple-700 text-white text-sm font-semibold px-5 py-2.5 rounded-xl transition">
                        <i class="fas fa-external-link mr-2"></i>Open in new tab
                    </a>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        function embedUrl(url) {
            const m = url.match(/(?:youtube\.com\/(?:watch\?v=|shorts\/|embed\/)|youtu\.be\/)([\w-]{11})/);
            return m ? 'https://www.youtube.com/embed/' + m[1] : null;
        }

        function openPreview(title, url) {
            document.getElementById('preview-title').textContent = title;
            const embed = embedUrl(url);
            const player = document.getElementById('preview-player');
            const iframe = document.getElementById('preview-iframe');
            const fallback = document.getElementById('preview-fallback');
            const link = document.getElementById('preview-link');

            if (embed) {
                iframe.src = embed;
                player.classList.remove('hidden');
                fallback.classList.add('hidden');
            } else {
                iframe.src = '';
                player.classList.add('hidden');
                link.href = url;
                fallback.classList.remove('hidden');
            }

            const modal = document.getElementById('preview-modal');
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            document.body.style.overflow = 'hidden';
        }

        function closePreview() {
            const modal = document.getElementById('preview-modal');
            modal.classList.add('hidden');
            modal.classList.remove('flex');
            document.getElementById('preview-iframe').src = '';
            document.body.style.overflow = '';
        }

        document.querySelectorAll('.preview-btn').forEach(btn => {
            btn.addEventListener('click', () => openPreview(btn.dataset.title, btn.dataset.url));
        });

        document.addEventListener('keydown', e => {
            if (e.key === 'Escape') closePreview();
        });
    </script>
@endsection