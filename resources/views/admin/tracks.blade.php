@extends('admin.layout')

@section('header', 'Track List')
@section('subtitle', 'Verify artist payments and manage the streamable track catalog')

@section('content')
    <div class="flex flex-wrap items-center gap-3 mb-6">
        @if ($pending > 0)
            <span class="neu-chip text-xs font-bold" style="color: #d97706">
                <i class="fas fa-clock mr-1"></i>{{ $pending }} pending approval(s)
            </span>
        @endif

        <form method="GET" class="flex flex-wrap items-center gap-2">
            <input type="text" name="q" value="{{ request('q') }}" placeholder="Search title / artist..."
                class="neu-inset px-4 py-2.5 rounded-full text-sm outline-none w-56">
            <select name="status" onchange="this.form.submit()" class="neu-inset px-4 py-2.5 rounded-full text-sm outline-none">
                <option value="">All statuses</option>
                @foreach (['pending_payment' => 'Pending Payment', 'pending_approval' => 'Pending Approval', 'live' => 'Live', 'paused' => 'Paused', 'exhausted' => 'Exhausted', 'rejected' => 'Rejected'] as $value => $label)
                    <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                @endforeach
            </select>
            <button type="submit" class="neu-btn px-5 py-2.5 rounded-full text-sm font-bold" style="color: var(--neu-accent)">Filter</button>
        </form>
    </div>

    <div class="neu-sm overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="text-left text-xs uppercase opacity-50">
                    <th class="px-5 py-4">Track</th>
                    <th class="px-5 py-4">Artist</th>
                    <th class="px-5 py-4">Status</th>
                    <th class="px-5 py-4">Streams</th>
                    <th class="px-5 py-4">Budget</th>
                    <th class="px-5 py-4">Payment Ref</th>
                    <th class="px-5 py-4">Amount</th>
                    <th class="px-5 py-4">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($tracks as $track)
                    <tr class="border-t" style="border-color: rgba(75,90,114,.12)">
                        <td class="px-5 py-4">
                            <p class="font-bold" style="color: var(--neu-text-strong)">{{ $track->title }}</p>
                            <a href="{{ $track->media_url }}" target="_blank" class="text-xs opacity-60 hover:opacity-100 truncate block max-w-xs">{{ $track->media_url }}</a>
                        </td>
                        <td class="px-5 py-4">
                            <p class="font-semibold">{{ $track->artist?->name ?? '—' }}</p>
                            <p class="text-xs opacity-60">{{ $track->artist?->email }}</p>
                        </td>
                        <td class="px-5 py-4">
                            @php
                                $statusColors = [
                                    'pending_payment' => '#d97706',
                                    'pending_approval' => '#d97706',
                                    'live' => '#16a34a',
                                    'paused' => '#64748b',
                                    'exhausted' => '#64748b',
                                    'rejected' => '#dc2626',
                                ];
                            @endphp
                            <span class="neu-chip text-xs font-bold" style="color: {{ $statusColors[$track->status] ?? 'var(--neu-text)' }}">
                                {{ ucwords(str_replace('_', ' ', $track->status)) }}
                            </span>
                        </td>
                        <td class="px-5 py-4 font-bold" style="color: var(--neu-accent)">{{ $track->streams_served }}</td>
                        <td class="px-5 py-4">{{ $track->stream_budget }}</td>
                        <td class="px-5 py-4 font-mono text-xs">{{ $track->payment_reference ?? '—' }}</td>
                        <td class="px-5 py-4">₦{{ number_format($track->amount_paid_ngn) }}</td>
                        <td class="px-5 py-4">
                            <div class="flex flex-wrap gap-2">
                                @if ($track->status === 'pending_approval' || $track->status === 'paid' || $track->status === 'pending_payment')
                                    <form action="{{ route('admin.tracks.approve', $track) }}" method="POST">
                                        @csrf
                                        <button type="submit" class="neu-btn px-4 py-2 rounded-full text-xs font-bold" style="color: #16a34a">
                                            <i class="fas fa-check mr-1"></i>Verify & Approve
                                        </button>
                                    </form>
                                    <form action="{{ route('admin.tracks.reject', $track) }}" method="POST">
                                        @csrf
                                        <button type="submit" class="neu-btn px-4 py-2 rounded-full text-xs font-bold" style="color: #dc2626">
                                            <i class="fas fa-xmark mr-1"></i>Reject
                                        </button>
                                    </form>
                                @elseif ($track->status === 'live')
                                    <form action="{{ route('admin.tracks.pause', $track) }}" method="POST">
                                        @csrf
                                        <button type="submit" class="neu-btn px-4 py-2 rounded-full text-xs font-bold" style="color: #64748b">
                                            <i class="fas fa-pause mr-1"></i>Pause
                                        </button>
                                    </form>
                                @elseif ($track->status === 'paused' && $track->hasBudgetRemaining())
                                    <form action="{{ route('admin.tracks.resume', $track) }}" method="POST">
                                        @csrf
                                        <button type="submit" class="neu-btn px-4 py-2 rounded-full text-xs font-bold" style="color: #16a34a">
                                            <i class="fas fa-play mr-1"></i>Resume
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-5 py-10 text-center opacity-60">
                            <i class="fas fa-music text-3xl mb-3 block"></i>
                            No tracks found.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-6">
        {{ $tracks->links() }}
    </div>
@endsection