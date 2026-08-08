@extends('artist.layout')

@section('header', 'Overview')
@section('subtitle', 'Your promotion at a glance')

@section('content')
    <!-- Stats -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <div class="bg-gray-900 border border-white/10 rounded-2xl p-6">
            <div class="flex items-center justify-between">
                <p class="text-sm text-gray-400">Total Campaigns</p>
                <i class="fas fa-bullhorn text-purple-400"></i>
            </div>
            <p class="text-3xl font-extrabold mt-3">{{ $stats['total_campaigns'] }}</p>
        </div>
        <div class="bg-gray-900 border border-white/10 rounded-2xl p-6">
            <div class="flex items-center justify-between">
                <p class="text-sm text-gray-400">Active</p>
                <i class="fas fa-play text-green-400"></i>
            </div>
            <p class="text-3xl font-extrabold mt-3">{{ $stats['active_campaigns'] }}</p>
        </div>
        <div class="bg-gray-900 border border-white/10 rounded-2xl p-6">
            <div class="flex items-center justify-between">
                <p class="text-sm text-gray-400">Total Listens</p>
                <i class="fas fa-headphones text-amber-400"></i>
            </div>
            <p class="text-3xl font-extrabold mt-3">{{ $stats['total_listens'] }}</p>
        </div>
        <div class="bg-gray-900 border border-white/10 rounded-2xl p-6">
            <div class="flex items-center justify-between">
                <p class="text-sm text-gray-400">Total Spent</p>
                <i class="fas fa-naira-sign text-fuchsia-400"></i>
            </div>
            <p class="text-3xl font-extrabold mt-3">₦{{ number_format($stats['total_spent']) }}</p>
        </div>
    </div>

    <!-- Campaigns -->
    <div class="flex items-center justify-between mb-5">
        <h2 class="text-lg font-bold">Your Campaigns</h2>
        <a href="{{ route('artist.campaign.create') }}"
            class="bg-purple-600 hover:bg-purple-700 text-white text-sm font-semibold px-5 py-2.5 rounded-xl transition">
            <i class="fas fa-plus mr-2"></i>New Campaign
        </a>
    </div>

    @if ($campaigns->isEmpty())
        <div class="bg-gray-900 border border-dashed border-white/20 rounded-2xl p-14 text-center">
            <i class="fas fa-music text-4xl text-gray-600 mb-4"></i>
            <p class="text-gray-400">You haven't created any campaigns yet.</p>
            <a href="{{ route('artist.campaign.create') }}" class="inline-block mt-4 text-purple-400 hover:text-purple-300 text-sm font-semibold">
                Create your first campaign →
            </a>
        </div>
    @else
        <div class="space-y-4">
            @foreach ($campaigns as $campaign)
                <a href="{{ route('artist.campaign.show', $campaign) }}"
                    class="block bg-gray-900 border border-white/10 hover:border-purple-500/50 rounded-2xl p-6 transition group">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <div class="flex items-center gap-3">
                                <h3 class="font-bold text-lg group-hover:text-purple-300 transition">{{ $campaign->title }}</h3>
                                @php
                                    $badge = match ($campaign->status) {
                                        'active' => ['text-green-400', 'active'],
                                        'pending_payment' => ['text-amber-400', 'pending payment'],
                                        'draft' => ['text-gray-400', 'draft'],
                                        'paused' => ['text-orange-400', 'paused'],
                                        'completed' => ['text-blue-400', 'completed'],
                                        default => ['text-gray-500', $campaign->status],
                                    };
                                @endphp
                                <span class="text-xs font-semibold uppercase tracking-wide px-2.5 py-1 rounded-full {{ $badge[1] === 'active' ? 'bg-green-500/10' : 'bg-white/5' }} {{ $badge[0] }}">{{ $badge[1] }}</span>
                            </div>
                            <p class="text-sm text-gray-500 mt-1">
                                <i class="fas fa-link mr-1"></i>{{ \Illuminate\Support\Str::limit($campaign->track_url, 60) }}
                                · {{ $campaign->platform }}
                            </p>
                            <p class="text-xs text-gray-600 mt-1">
                                Target: {{ $campaign->listen_target ?? $campaign->review_target }} listens · ₦{{ number_format($campaign->reward_per_review) }}/listen
                                @if ($campaign->payment_reference)
                                    · <span class="text-gray-500">Ref: {{ $campaign->payment_reference }}</span>
                                @endif
                            </p>
                        </div>
                        <div class="text-right shrink-0">
                            <p class="text-2xl font-extrabold">{{ $campaign->rewarded_sessions_count }}</p>
                            <p class="text-xs text-gray-500">paid listens</p>
                        </div>
                    </div>
                </a>
            @endforeach
        </div>
    @endif
@endsection