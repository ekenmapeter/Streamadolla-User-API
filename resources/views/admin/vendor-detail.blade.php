@extends('admin.layout')

@section('header', $user->name)
@section('subtitle', 'Vendor fleet, streams, earnings and wallet history')

@section('content')
    {{-- ── Profile header ─────────────────────────────────────────────────── --}}
    <div class="bg-gray-900 border border-white/10 rounded-2xl p-6 mb-8">
        <div class="flex flex-wrap items-start justify-between gap-6">
            <div class="flex items-center gap-5">
                <div class="h-16 w-16 rounded-full bg-gradient-to-tr from-emerald-500 to-teal-500 flex items-center justify-center text-white font-extrabold text-xl shrink-0">
                    {{ strtoupper(substr($user->name, 0, 2)) }}
                </div>
                <div>
                    <div class="flex items-center gap-3 flex-wrap">
                        <h2 class="text-xl font-extrabold">{{ $user->name }}</h2>
                        <span class="text-xs font-semibold uppercase px-2.5 py-1 rounded-full bg-emerald-500/10 text-emerald-400">Vendor</span>
                        <span class="text-xs font-semibold uppercase px-2.5 py-1 rounded-full {{ $user->status === 'active' ? 'bg-green-500/10 text-green-400' : 'bg-red-500/10 text-red-400' }}">{{ $user->status }}</span>
                        @if ($user->email_verified_at)
                            <span class="text-xs font-semibold uppercase px-2.5 py-1 rounded-full bg-blue-500/10 text-blue-400"><i class="fas fa-circle-check mr-1"></i>Verified</span>
                        @endif
                    </div>
                    <p class="text-sm text-gray-500 mt-1">{{ $user->email }}@if ($user->phone) · {{ $user->phone }}@endif</p>
                    <p class="text-xs text-gray-600 mt-1">
                        <i class="fas fa-calendar mr-1"></i>Joined {{ $user->created_at?->format('M j, Y') }}
                        @if ($user->ip_address)
                            · <i class="fas fa-globe mr-1"></i><span class="font-mono">{{ $user->ip_address }}</span>
                        @endif
                    </p>
                </div>
            </div>
            <a href="{{ route('admin.vendors') }}" class="bg-white/5 hover:bg-white/10 text-gray-300 text-sm font-semibold px-4 py-2 rounded-xl transition">
                <i class="fas fa-arrow-left mr-1"></i>Back
            </a>
        </div>
    </div>

    {{-- ── Stats ──────────────────────────────────────────────────────────── --}}
    <div class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-6 gap-4 mb-8">
        @foreach ([
            ['Fleet Devices', $devices->count(), 'fa-mobile-screen-button', 'text-purple-400'],
            ['Rewarded Streams', $stats['total_streams'], 'fa-circle-play', 'text-emerald-400'],
            ['Fraud Sessions', $stats['fraud_sessions'], 'fa-flag', 'text-red-400'],
            ['Open Sessions', $stats['open_sessions'], 'fa-hourglass-half', 'text-amber-400'],
            ['Total Earned', '₦' . number_format($stats['total_earned']), 'fa-naira-sign', 'text-emerald-400'],
            ['Paid Out', '₦' . number_format($stats['total_paid_out']), 'fa-money-bill-transfer', 'text-blue-400'],
            ['Wallet Balance', '₦' . number_format($stats['balance']), 'fa-wallet', 'text-green-400'],
            ['Payout Requests', $user->payoutRequests()->count(), 'fa-money-bill-wave', 'text-orange-400'],
        ] as [$label, $value, $icon, $color])
            <div class="bg-gray-900 border border-white/10 rounded-2xl p-5">
                <div class="flex items-center justify-between mb-2">
                    <p class="text-xs text-gray-400">{{ $label }}</p>
                    <i class="fas {{ $icon }} {{ $color }}"></i>
                </div>
                <p class="text-xl font-extrabold truncate">{{ $value }}</p>
            </div>
        @endforeach
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-2 gap-8">
        {{-- ── Fleet devices ──────────────────────────────────────────────── --}}
        <div class="bg-gray-900 border border-white/10 rounded-2xl overflow-hidden">
            <div class="px-6 py-4 border-b border-white/10">
                <h3 class="font-bold text-sm"><i class="fas fa-mobile-screen-button mr-2 text-purple-400"></i>Fleet Devices</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-white/5 text-left text-xs uppercase tracking-wide text-gray-500">
                        <tr>
                            <th class="px-5 py-3 font-medium">Device</th>
                            <th class="px-4 py-3 font-medium">Status</th>
                            <th class="px-4 py-3 font-medium text-center">Streams</th>
                            <th class="px-4 py-3 font-medium text-right">Earned</th>
                            <th class="px-4 py-3 font-medium">Last Seen</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/5">
                        @forelse ($devices as $device)
                            @php
                                $statusBadge = match ($device->status) {
                                    'streaming' => 'bg-purple-500/10 text-purple-400',
                                    'online' => 'bg-green-500/10 text-green-400',
                                    default => 'bg-white/5 text-gray-400',
                                };
                            @endphp
                            <tr class="hover:bg-white/5 transition">
                                <td class="px-5 py-3.5">
                                    <p class="font-semibold truncate max-w-[180px]">{{ $device->name ?? 'Unnamed device' }}</p>
                                    <p class="text-[11px] font-mono text-gray-600 truncate">{{ $device->device_id }}</p>
                                </td>
                                <td class="px-4 py-3.5">
                                    <span class="text-xs px-2.5 py-1 rounded-full font-medium {{ $statusBadge }}">{{ $device->status }}</span>
                                </td>
                                <td class="px-4 py-3.5 text-center font-semibold text-emerald-400">
                                    {{ (int) ($deviceTotals[$device->id] ?? 0) }}
                                    <p class="text-[10px] text-amber-400 font-medium">{{ (int) $device->total_streams }} all-time</p>
                                </td>
                                <td class="px-4 py-3.5 text-right font-semibold">₦{{ number_format((int) ($deviceEarned[$device->id] ?? 0)) }}</td>
                                <td class="px-4 py-3.5 text-xs text-gray-500">{{ $device->last_seen?->diffForHumans() ?? 'Never' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-10 text-center text-gray-500">No devices registered yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- ── Wallet transactions ─────────────────────────────────────────-- }}
        <div class="bg-gray-900 border border-white/10 rounded-2xl overflow-hidden">
            <div class="px-6 py-4 border-b border-white/10">
                <h3 class="font-bold text-sm"><i class="fas fa-wallet mr-2 text-green-400"></i>Wallet Transactions</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-white/5 text-left text-xs uppercase tracking-wide text-gray-500">
                        <tr>
                            <th class="px-5 py-3 font-medium">Type</th>
                            <th class="px-4 py-3 font-medium">Amount</th>
                            <th class="px-4 py-3 font-medium">Balance</th>
                            <th class="px-4 py-3 font-medium">Date</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/5">
                        @forelse ($transactions as $txn)
                            @php
                                $isCredit = in_array($txn->type, [\App\Models\WalletTransaction::TYPE_VENDOR, \App\Models\WalletTransaction::TYPE_RENTAL]);
                                $tcolors = [
                                    'vendor' => 'bg-emerald-500/10 text-emerald-400',
                                    'rental' => 'bg-cyan-500/10 text-cyan-400',
                                    'payout' => 'bg-blue-500/10 text-blue-400',
                                ];
                            @endphp
                            <tr class="hover:bg-white/5 transition">
                                <td class="px-5 py-3.5">
                                    <span class="text-xs px-2.5 py-1 rounded-full font-medium {{ $tcolors[$txn->type] ?? 'bg-white/5 text-gray-400' }}">{{ $txn->type }}</span>
                                </td>
                                <td class="px-4 py-3.5 font-semibold {{ $isCredit ? 'text-green-400' : 'text-blue-400' }}">{{ $isCredit ? '+' : '−' }}₦{{ number_format($txn->amount) }}</td>
                                <td class="px-4 py-3.5 text-xs text-gray-400">₦{{ number_format($txn->balance_after) }}</td>
                                <td class="px-4 py-3.5 text-xs text-gray-500">{{ $txn->created_at->diffForHumans() }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-6 py-10 text-center text-gray-500">No wallet transactions yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- ── Earnings record ────────────────────────────────────────────── --}}
        <div class="bg-gray-900 border border-white/10 rounded-2xl overflow-hidden">
            <div class="px-6 py-4 border-b border-white/10">
                <h3 class="font-bold text-sm"><i class="fas fa-circle-play mr-2 text-emerald-400"></i>Earnings Record (StreamPlays)</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-white/5 text-left text-xs uppercase tracking-wide text-gray-500">
                        <tr>
                            <th class="px-5 py-3 font-medium">Track / Title</th>
                            <th class="px-4 py-3 font-medium">Device</th>
                            <th class="px-4 py-3 font-medium text-right">Reward</th>
                            <th class="px-4 py-3 font-medium">Streamed</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/5">
                        @forelse ($streams as $play)
                            <tr class="hover:bg-white/5 transition">
                                <td class="px-5 py-3.5">
                                    <p class="font-semibold truncate max-w-[200px]">{{ $play->assignment?->media_title ?? $play->track?->title ?? 'Untitled' }}</p>
                                    <p class="text-[11px] text-gray-600 truncate max-w-[200px]">{{ $play->media_url }}</p>
                                </td>
                                <td class="px-4 py-3.5 text-xs text-gray-400">{{ $play->assignment?->device?->name ?? '—' }}</td>
                                <td class="px-4 py-3.5 text-right font-semibold text-emerald-400">₦{{ number_format((int) $play->reward_amount) }}</td>
                                <td class="px-4 py-3.5 text-xs text-gray-500">{{ $play->created_at?->diffForHumans() }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-6 py-10 text-center text-gray-500">No streams rewarded yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- ── Vendor sessions ────────────────────────────────────────────── --}}
        <div class="bg-gray-900 border border-white/10 rounded-2xl overflow-hidden">
            <div class="px-6 py-4 border-b border-white/10">
                <h3 class="font-bold text-sm"><i class="fas fa-shield-halved mr-2 text-fuchsia-400"></i>Recent Device Sessions (fraud visibility)</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-white/5 text-left text-xs uppercase tracking-wide text-gray-500">
                        <tr>
                            <th class="px-5 py-3 font-medium">Device</th>
                            <th class="px-4 py-3 font-medium">Elapsed</th>
                            <th class="px-4 py-3 font-medium">Checkpoints</th>
                            <th class="px-4 py-3 font-medium">Status</th>
                            <th class="px-4 py-3 font-medium">Completed</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/5">
                        @forelse ($sessions as $session)
                            @php
                                $scolors = [
                                    'rewarded' => 'bg-green-500/10 text-green-400',
                                    'open' => 'bg-amber-500/10 text-amber-400',
                                    'abandoned' => 'bg-white/5 text-gray-400',
                                    'fraud' => 'bg-red-500/10 text-red-400',
                                ];
                            @endphp
                            <tr class="hover:bg-white/5 transition">
                                <td class="px-5 py-3.5 text-xs text-gray-300 truncate max-w-[150px]">{{ $session->device?->name ?? '—' }}</td>
                                <td class="px-4 py-3.5 text-xs text-gray-400">{{ $session->elapsed_seconds }}s / {{ $session->min_duration_seconds }}s</td>
                                <td class="px-4 py-3.5 text-xs text-gray-400">{{ count($session->checkpoints ?? []) }}</td>
                                <td class="px-4 py-3.5">
                                    <span class="text-xs px-2.5 py-1 rounded-full font-medium {{ $scolors[$session->status] ?? 'bg-white/5 text-gray-400' }}">{{ $session->status }}</span>
                                </td>
                                <td class="px-4 py-3.5 text-xs text-gray-500">{{ $session->completed_at?->diffForHumans() ?? $session->started_at?->diffForHumans() ?? '—' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-10 text-center text-gray-500">No device sessions yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection