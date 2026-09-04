@extends('admin.layout')

@section('header', 'Payouts')
@section('subtitle', 'Listener payout reconciliation')

@section('content')
    <div class="bg-gray-900 border border-white/10 rounded-2xl overflow-hidden">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-white/10 text-gray-400 text-left">
                    <th class="px-6 py-4 font-semibold">Listener</th>
                    <th class="px-6 py-4 font-semibold">Amount</th>
                    <th class="px-6 py-4 font-semibold">Method</th>
                    <th class="px-6 py-4 font-semibold">Status</th>
                    <th class="px-6 py-4 font-semibold">Hold until</th>
                    <th class="px-6 py-4 font-semibold">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($payouts as $payout)
                    <tr class="border-b border-white/5">
                        <td class="px-6 py-4">
                            <p class="font-semibold">
                                @if ($payout->user)
                                    <a href="{{ route('admin.listeners.detail', $payout->user) }}" class="hover:text-purple-400 transition">{{ $payout->user->name }}</a>
                                @else
                                    —
                                @endif
                            </p>
                            <p class="text-xs text-gray-500">{{ $payout->user?->email }}</p>
                            <p class="text-xs text-gray-600 mt-0.5">
                                @if ($payout->user?->phone)<i class="fas fa-phone mr-1 text-gray-700"></i>{{ $payout->user->phone }}@endif
                                @if ($payout->user?->ip_address)
                                    @if ($payout->user->phone) · @endif
                                    <i class="fas fa-globe mr-1 text-gray-700"></i><span class="font-mono">{{ $payout->user->ip_address }}</span>
                                @endif
                            </p>
                        </td>
                        <td class="px-6 py-4 font-bold">₦{{ number_format($payout->amount) }}</td>
                        <td class="px-6 py-4 capitalize">{{ $payout->method }}</td>
                        <td class="px-6 py-4">
                            @if ($payout->destination && $payout->method === 'bank')
                                <p class="text-xs text-gray-400">{{ ($payout->destination['account_name'] ?? '') }} · {{ ($payout->destination['account_number'] ?? '') }}</p>
                            @elseif ($payout->destination)
                                <p class="text-xs text-gray-400">{{ $payout->destination['phone'] ?? '' }}</p>
                            @endif
                            <span class="text-xs uppercase px-2.5 py-1 rounded-full
                                {{ $payout->status === 'paid' ? 'bg-green-500/10 text-green-400' : ($payout->status === 'rejected' ? 'bg-red-500/10 text-red-400' : 'bg-amber-500/10 text-amber-400') }}">
                                {{ $payout->status }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-gray-400">{{ $payout->hold_until_at?->format('d M, H:i') ?? '—' }}</td>
                        <td class="px-6 py-4">
                            <div class="flex items-center space-x-2">
                                @if ($payout->status !== 'paid')
                                    <form action="{{ route('admin.payouts.mark-paid', $payout) }}" method="POST">
                                        @csrf
                                        <button class="bg-green-600 hover:bg-green-700 text-white text-xs font-semibold px-3 py-1.5 rounded-lg"><i class="fas fa-check mr-1"></i>Paid</button>
                                    </form>
                                @endif
                                @if ($payout->status === 'requested')
                                    <form action="{{ route('admin.payouts.reject', $payout) }}" method="POST">
                                        @csrf
                                        <button class="bg-red-600/10 hover:bg-red-600/20 text-red-400 text-xs font-semibold px-3 py-1.5 rounded-lg"><i class="fas fa-xmark mr-1"></i>Reject</button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-6 py-16 text-center text-gray-400">No payout requests.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-6">{{ $payouts->links() }}</div>
@endsection