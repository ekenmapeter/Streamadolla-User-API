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

    <div class="grid grid-cols-2 lg:grid-cols-4 gap-6 mb-10">
        <div class="bg-gray-900 border border-white/10 rounded-2xl p-6">
            <p class="text-sm text-gray-400">Active Device Rentals</p>
            <p class="text-2xl font-extrabold mt-2 text-blue-400">{{ $stats['active_rentals'] }}</p>
        </div>
        <div class="bg-gray-900 border border-white/10 rounded-2xl p-6">
            <p class="text-sm text-gray-400">Rental Streams Today</p>
            <p class="text-2xl font-extrabold mt-2">{{ $stats['rental_streams_today'] }}</p>
        </div>
        <div class="bg-gray-900 border border-white/10 rounded-2xl p-6">
            <p class="text-sm text-gray-400">Rental Paid Today</p>
            <p class="text-2xl font-extrabold mt-2 text-green-400">₦{{ number_format($stats['rental_paid_today']) }}</p>
        </div>
        <a href="{{ route('admin.tracks') }}" class="bg-gray-900 border border-white/10 rounded-2xl p-6 hover:border-purple-500/50 transition">
            <p class="text-sm text-gray-400">Tracks Awaiting Approval</p>
            <p class="text-2xl font-extrabold mt-2 {{ $stats['tracks_pending'] ? 'text-amber-400' : '' }}">{{ $stats['tracks_pending'] }}</p>
        </a>
    </div>

    <div class="grid grid-cols-2 lg:grid-cols-4 gap-6 mb-10">
        <a href="{{ route('admin.vendors') }}" class="bg-gray-900 border border-white/10 rounded-2xl p-6 hover:border-emerald-500/50 transition">
            <p class="text-sm text-gray-400">Vendors</p>
            <p class="text-2xl font-extrabold mt-2 text-emerald-400">{{ $stats['vendors'] }}</p>
        </a>
        <div class="bg-gray-900 border border-white/10 rounded-2xl p-6">
            <p class="text-sm text-gray-400">Vendor Streams Today</p>
            <p class="text-2xl font-extrabold mt-2">{{ $stats['vendor_streams_today'] }}</p>
        </div>
        <div class="bg-gray-900 border border-white/10 rounded-2xl p-6">
            <p class="text-sm text-gray-400">Vendor Paid Today</p>
            <p class="text-2xl font-extrabold mt-2 text-green-400">{{ \App\Support\Money::format($stats['vendor_paid_today'], \App\Support\Money::USD) }}</p>
        </div>
        <div class="bg-gray-900 border border-white/10 rounded-2xl p-6">
            <p class="text-sm text-gray-400">Vendor Rate / Stream</p>
            <p class="text-2xl font-extrabold mt-2 text-emerald-400">{{ \App\Support\Money::format(\App\Models\AppSetting::get('vendor_rate_per_stream', 0.001), \App\Support\Money::USD) }}</p>
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