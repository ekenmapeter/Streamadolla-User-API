@extends('admin.layout')

@section('header', 'Campaigns')
@section('subtitle', 'Monitor and control all promotion campaigns')

@section('content')
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
@endsection