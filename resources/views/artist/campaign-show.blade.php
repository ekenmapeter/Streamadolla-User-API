@extends('artist.layout')

@section('header', $campaign->title)
@section('subtitle', 'Campaign details · status: ' . ucfirst(str_replace('_', ' ', $campaign->status)))

@section('content')
    <!-- Stats -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <div class="bg-gray-900 border border-white/10 rounded-2xl p-6">
            <div class="flex items-center justify-between">
                <p class="text-sm text-gray-400">Paid Listens</p>
                <i class="fas fa-headphones text-amber-400"></i>
            </div>
            <p class="text-3xl font-extrabold mt-3">{{ $stats['total_listens'] }}</p>
            <p class="text-xs text-gray-500 mt-1">target: {{ $stats['target_listens'] }}</p>
        </div>
        <div class="bg-gray-900 border border-white/10 rounded-2xl p-6">
            <div class="flex items-center justify-between">
                <p class="text-sm text-gray-400">Active Listeners</p>
                <i class="fas fa-users text-purple-400"></i>
            </div>
            <p class="text-3xl font-extrabold mt-3">{{ $stats['active_listeners'] }}</p>
        </div>
        <div class="bg-gray-900 border border-white/10 rounded-2xl p-6">
            <div class="flex items-center justify-between">
                <p class="text-sm text-gray-400">Reward / Listen</p>
                <i class="fas fa-naira-sign text-green-400"></i>
            </div>
            <p class="text-3xl font-extrabold mt-3">₦{{ number_format($campaign->reward_per_review) }}</p>
        </div>
        <div class="bg-gray-900 border border-white/10 rounded-2xl p-6">
            <div class="flex items-center justify-between">
                <p class="text-sm text-gray-400">Platform</p>
                <i class="fas fa-music text-fuchsia-400"></i>
            </div>
            <p class="text-3xl font-extrabold mt-3 capitalize">{{ $campaign->platform }}</p>
            <p class="text-xs text-gray-500 mt-1 truncate"><a href="{{ $campaign->track_url }}" target="_blank" class="text-purple-400 hover:text-purple-300">{{ \Illuminate\Support\Str::limit($campaign->track_url, 30) }}</a></p>
        </div>
    </div>

    @if ($campaign->status === 'pending_payment')
        <div class="bg-amber-500/10 border border-amber-500/30 text-amber-300 rounded-2xl p-5 mb-8 text-sm flex items-center justify-between">
            <div>
                <p class="font-semibold">This campaign is waiting for payment confirmation.</p>
                <p class="text-amber-300/70 mt-0.5">Reference: {{ $campaign->payment_reference ?? '—' }} · Once confirmed it goes live automatically.</p>
            </div>
        </div>
    @endif

    <!-- Listen activity -->
    <h3 class="font-bold mb-4 text-sm uppercase tracking-wide text-gray-400">Listen Activity</h3>
    <div class="space-y-4">
        @forelse ($sessions as $session)
            <div class="bg-gray-900 border border-white/10 rounded-2xl p-6 flex items-center gap-4">
                <div class="h-10 w-10 rounded-full bg-purple-500/10 flex items-center justify-center shrink-0">
                    <i class="fas fa-headphones text-purple-400"></i>
                </div>
                <div class="min-w-0 flex-1">
                    <p class="font-semibold">{{ $session->listener?->name ?? 'Listener' }}</p>
                    <p class="text-xs text-gray-500">{{ $session->completed_at?->diffForHumans() }}</p>
                </div>
                <div class="text-right shrink-0">
                    <p class="text-sm text-gray-400">{{ gmdate('i:s', $session->elapsed_seconds) }} listened</p>
                    <p class="text-xs font-semibold uppercase {{ $session->status === 'rewarded' ? 'text-green-400' : ($session->status === 'fraud' ? 'text-red-400' : 'text-amber-400') }}">{{ $session->status }}</p>
                </div>
            </div>
        @empty
            <div class="bg-gray-900 border border-dashed border-white/20 rounded-2xl p-14 text-center">
                <i class="fas fa-headphones text-4xl text-gray-600 mb-4"></i>
                <p class="text-gray-400">No listens yet. They'll land here as listeners engage with your track.</p>
            </div>
        @endforelse
    </div>

    <div class="mt-6">
        {{ $sessions->links() }}
    </div>
@endsection
