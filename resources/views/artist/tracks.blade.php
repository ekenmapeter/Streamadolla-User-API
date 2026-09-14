@extends('artist.layout')

@section('header', 'My Tracks')
@section('subheader', 'Track list submissions — approved tracks are streamed to rented devices')

@section('content')
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
        <div class="neu-sm p-5">
            <p class="text-xs font-bold uppercase opacity-50">Total Tracks</p>
            <p class="text-2xl font-extrabold mt-1" style="color: var(--neu-text-strong)">{{ $stats['total_tracks'] }}</p>
        </div>
        <div class="neu-sm p-5">
            <p class="text-xs font-bold uppercase opacity-50">Live</p>
            <p class="text-2xl font-extrabold mt-1" style="color: #16a34a">{{ $stats['live_tracks'] }}</p>
        </div>
        <div class="neu-sm p-5">
            <p class="text-xs font-bold uppercase opacity-50">Streams Served</p>
            <p class="text-2xl font-extrabold mt-1" style="color: var(--neu-text-strong)">{{ number_format($stats['total_streams']) }}</p>
        </div>
        <div class="neu-sm p-5">
            <p class="text-xs font-bold uppercase opacity-50">Total Spent</p>
            <p class="text-2xl font-extrabold mt-1" style="color: var(--neu-accent)">₦{{ number_format($stats['total_spent']) }}</p>
        </div>
    </div>

    <div class="flex justify-end mb-4">
        <a href="{{ route('artist.track.create') }}" class="neu-btn px-6 py-3 rounded-full text-sm font-bold" style="color: var(--neu-accent)">
            <i class="fas fa-plus mr-2"></i>Submit a Track
        </a>
    </div>

    <div class="neu-sm overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="text-left text-xs uppercase opacity-50">
                    <th class="px-5 py-4">Track</th>
                    <th class="px-5 py-4">Platform</th>
                    <th class="px-5 py-4">Status</th>
                    <th class="px-5 py-4">Streams</th>
                    <th class="px-5 py-4">Budget</th>
                    <th class="px-5 py-4">Paid (₦)</th>
                    <th class="px-5 py-4">Submitted</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($tracks as $track)
                    <tr class="border-t" style="border-color: rgba(75,90,114,.12)">
                        <td class="px-5 py-4">
                            <p class="font-bold" style="color: var(--neu-text-strong)">{{ $track->title }}</p>
                            <a href="{{ $track->media_url }}" target="_blank" class="text-xs opacity-60 hover:opacity-100 truncate block max-w-xs">{{ $track->media_url }}</a>
                        </td>
                        <td class="px-5 py-4">{{ ucfirst($track->platform) }}</td>
                        <td class="px-5 py-4">
                            <span class="neu-chip text-xs font-bold">{{ ucwords(str_replace('_', ' ', $track->status)) }}</span>
                        </td>
                        <td class="px-5 py-4 font-bold" style="color: var(--neu-accent)">{{ $track->streams_served }}</td>
                        <td class="px-5 py-4">{{ $track->stream_budget }}</td>
                        <td class="px-5 py-4">₦{{ number_format($track->amount_paid_ngn) }}</td>
                        <td class="px-5 py-4">{{ $track->created_at->format('M j, Y') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-5 py-10 text-center opacity-60">
                            <i class="fas fa-music text-3xl mb-3 block"></i>
                            No tracks submitted yet. Submit your first track to get streams.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection