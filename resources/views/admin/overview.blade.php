@extends('admin.layout')

@section('header', 'Overview')
@section('subtitle', 'AudioReach platform monitor')

@section('content')
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <div class="bg-gray-900 border border-white/10 rounded-2xl p-6">
            <div class="flex items-center justify-between">
                <p class="text-sm text-gray-400">Listeners</p>
                <i class="fas fa-users text-purple-400"></i>
            </div>
            <p class="text-3xl font-extrabold mt-3">{{ $stats['listeners'] }}</p>
        </div>
        <div class="bg-gray-900 border border-white/10 rounded-2xl p-6">
            <div class="flex items-center justify-between">
                <p class="text-sm text-gray-400">Artists</p>
                <i class="fas fa-microphone text-fuchsia-400"></i>
            </div>
            <p class="text-3xl font-extrabold mt-3">{{ $stats['artists'] }}</p>
        </div>
        <div class="bg-gray-900 border border-white/10 rounded-2xl p-6">
            <div class="flex items-center justify-between">
                <p class="text-sm text-gray-400">Active Campaigns</p>
                <i class="fas fa-bullhorn text-green-400"></i>
            </div>
            <p class="text-3xl font-extrabold mt-3">{{ $stats['active_campaigns'] }}</p>
        </div>
        <div class="bg-gray-900 border border-white/10 rounded-2xl p-6">
            <div class="flex items-center justify-between">
                <p class="text-sm text-gray-400">App Devices (24h)</p>
                <i class="fas fa-mobile-screen text-blue-400"></i>
            </div>
            <p class="text-3xl font-extrabold mt-3">{{ $stats['fcm_devices'] }}</p>
        </div>
    </div>

    <div class="grid grid-cols-2 lg:grid-cols-4 gap-6 mb-10">
        <div class="bg-gray-900 border border-white/10 rounded-2xl p-6">
            <p class="text-sm text-gray-400">Listens Today</p>
            <p class="text-2xl font-extrabold mt-2">{{ $stats['sessions_today'] }}</p>
        </div>
        <div class="bg-gray-900 border border-white/10 rounded-2xl p-6">
            <p class="text-sm text-gray-400">Rewards Paid</p>
            <p class="text-2xl font-extrabold mt-2 text-green-400">{{ $stats['rewarded'] }}</p>
        </div>
        <div class="bg-gray-900 border border-white/10 rounded-2xl p-6">
            <p class="text-sm text-gray-400">Flagged Sessions</p>
            <p class="text-2xl font-extrabold mt-2 {{ $stats['fraud'] ? 'text-red-400' : '' }}">{{ $stats['fraud'] }}</p>
        </div>
        <div class="bg-gray-900 border border-white/10 rounded-2xl p-6">
            <p class="text-sm text-gray-400">Pending Payouts</p>
            <p class="text-2xl font-extrabold mt-2 text-amber-400">{{ $stats['payouts_pending'] }}</p>
        </div>
    </div>

    <div class="flex items-center justify-between mb-5">
        <h2 class="text-lg font-bold">Recent Listens</h2>
    </div>

    <div class="space-y-4">
        @forelse ($recentSessions as $session)
            <div class="bg-gray-900 border border-white/10 rounded-2xl p-6">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p class="font-semibold">{{ $session->listener?->name }}</p>
                        <p class="text-sm text-gray-500">on "{{ $session->campaign?->title }}" · {{ $session->completed_at?->diffForHumans() }}</p>
                    </div>
                    <div class="flex items-center space-x-2 shrink-0">
                        <span class="text-xs text-gray-500">{{ $session->elapsed_seconds }}s</span>
                        <span class="text-xs uppercase px-2.5 py-1 rounded-full bg-white/5
                            {{ $session->status === 'rewarded' ? 'text-green-400' : ($session->status === 'fraud' ? 'text-red-400' : 'text-amber-400') }}">
                            {{ $session->status }}
                        </span>
                    </div>
                </div>
            </div>
        @empty
            <div class="bg-gray-900 border border-dashed border-white/20 rounded-2xl p-14 text-center">
                <p class="text-gray-400">No listens yet.</p>
            </div>
        @endforelse
    </div>
@endsection