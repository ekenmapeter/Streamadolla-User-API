@extends('admin.layout')

@section('header', $user->name)
@section('subtitle', 'Listener profile, devices, sessions and wallet history')

@section('content')
    {{-- ── Profile header ─────────────────────────────────────────────────── --}}
    <div class="bg-gray-900 border border-white/10 rounded-2xl p-6 mb-8">
        <div class="flex flex-wrap items-start justify-between gap-6">
            <div class="flex items-center gap-5">
                <div class="h-16 w-16 rounded-full bg-gradient-to-tr from-purple-500 to-fuchsia-500 flex items-center justify-center text-white font-extrabold text-xl shrink-0">
                    {{ strtoupper(substr($user->name, 0, 2)) }}
                </div>
                <div>
                    <div class="flex items-center gap-3 flex-wrap">
                        <h2 class="text-xl font-extrabold">{{ $user->name }}</h2>
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

            <div class="flex flex-wrap items-center gap-3">
                @if ($user->trashed())
                    <form action="{{ route('admin.users.restore', $user) }}" method="POST">
                        @csrf
                        <button class="bg-emerald-600/20 hover:bg-emerald-600/30 text-emerald-400 text-sm font-semibold px-4 py-2 rounded-xl transition">
                            <i class="fas fa-rotate-left mr-1"></i>Restore User
                        </button>
                    </form>
                @else
                    <form action="{{ route('admin.listeners.trust', $user) }}" method="POST" class="flex items-center gap-2">
                        @csrf
                        <select name="trust_level" class="px-3 py-2 bg-white/5 border border-gray-700 rounded-xl focus:ring-2 focus:ring-purple-500 outline-none text-sm text-gray-200">
                            @for ($i = 0; $i <= 3; $i++)
                                <option value="{{ $i }}" {{ ($profile?->trust_level ?? 0) === $i ? 'selected' : '' }}>Trust L{{ $i }}</option>
                            @endfor
                        </select>
                        <button class="bg-purple-600 hover:bg-purple-700 text-white text-sm font-semibold px-4 py-2 rounded-xl transition">Update</button>
                    </form>
                    <a href="{{ route('admin.users.edit', $user) }}" class="bg-white/5 hover:bg-white/10 text-gray-200 text-sm font-semibold px-4 py-2 rounded-xl transition">
                        <i class="fas fa-pen mr-1"></i>Edit
                    </a>
                    @if ($user->status === 'active')
                        <form action="{{ route('admin.users.suspend', $user) }}" method="POST"
                            onsubmit="return confirm('Suspend {{ $user->name }}? They will not be able to listen or withdraw.');">
                            @csrf
                            <button class="bg-amber-600/20 hover:bg-amber-600/30 text-amber-400 text-sm font-semibold px-4 py-2 rounded-xl transition">
                                <i class="fas fa-pause mr-1"></i>Suspend
                            </button>
                        </form>
                    @else
                        <form action="{{ route('admin.users.activate', $user) }}" method="POST">
                            @csrf
                            <button class="bg-green-600/20 hover:bg-green-600/30 text-green-400 text-sm font-semibold px-4 py-2 rounded-xl transition">
                                <i class="fas fa-play mr-1"></i>Reactivate
                            </button>
                        </form>
                    @endif
                    <form action="{{ route('admin.users.delete', $user) }}" method="POST"
                        onsubmit="return confirm('Move {{ $user->name }} to trash? Their account will be deactivated but all history (wallet, sessions, devices) is retained and can be restored.');">
                        @csrf
                        <button class="bg-red-600/20 hover:bg-red-600/30 text-red-400 text-sm font-semibold px-4 py-2 rounded-xl transition">
                            <i class="fas fa-trash mr-1"></i>Delete
                        </button>
                    </form>
                @endif
                <a href="{{ route('admin.listeners') }}" class="bg-white/5 hover:bg-white/10 text-gray-300 text-sm font-semibold px-4 py-2 rounded-xl transition">
                    <i class="fas fa-arrow-left mr-1"></i>Back
                </a>
            </div>
        </div>
    </div>

    {{-- ── Stats ──────────────────────────────────────────────────────────── --}}
    <div class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-6 gap-4 mb-8">
        @foreach ([
            ['Rewarded Listens', $stats['rewarded_sessions'], 'fa-headphones', 'text-emerald-400'],
            ['Total Sessions', $stats['total_sessions'], 'fa-list-check', 'text-cyan-400'],
            ['Fraud Sessions', $stats['fraud_sessions'], 'fa-flag', 'text-red-400'],
            ['Open Sessions', $stats['open_sessions'], 'fa-hourglass-half', 'text-amber-400'],
            ['Trust Level', 'L' . ($profile?->trust_level ?? 0), 'fa-shield-halved', 'text-fuchsia-400'],
            ['Streak', $profile?->streak ?? 0, 'fa-fire', 'text-yellow-400'],
            ['Total Earned', '₦' . number_format($stats['total_earned']), 'fa-naira-sign', 'text-emerald-400'],
            ['Paid Out', '₦' . number_format($stats['total_paid_out']), 'fa-money-bill-transfer', 'text-blue-400'],
            ['Wallet Balance', '₦' . number_format($stats['balance']), 'fa-wallet', 'text-green-400'],
            ['Devices', $stats['devices'], 'fa-mobile-screen', 'text-purple-400'],
            ['Genres', count($profile?->genre_prefs ?? []), 'fa-music', 'text-cyan-400'],
            ['Last Active', $stats['last_listened_at'] ? \Illuminate\Support\Carbon::parse($stats['last_listened_at'])->diffForHumans() : 'Never', 'fa-clock', 'text-slate-400'],
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
        {{-- ── Devices ───────────────────────────────────────────────────── --}}
        <div class="bg-gray-900 border border-white/10 rounded-2xl overflow-hidden">
            <div class="px-6 py-4 border-b border-white/10">
                <h3 class="font-bold text-sm"><i class="fas fa-mobile-screen mr-2 text-purple-400"></i>Devices</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-white/5 text-left text-xs uppercase tracking-wide text-gray-500">
                        <tr>
                            <th class="px-5 py-3 font-medium">Platform</th>
                            <th class="px-4 py-3 font-medium">App</th>
                            <th class="px-4 py-3 font-medium">Fingerprint</th>
                            <th class="px-4 py-3 font-medium">Last Seen</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/5">
                        @forelse ($devices as $device)
                            <tr class="hover:bg-white/5 transition">
                                <td class="px-5 py-3.5">
                                    <span class="text-xs px-2.5 py-1 rounded-full font-medium bg-white/5 text-gray-300">{{ $device->platform ?? 'unknown' }}</span>
                                    @if ($device->free_move)
                                        <span class="text-xs px-2.5 py-1 rounded-full font-medium bg-amber-500/10 text-amber-400 ml-1">free-move</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3.5 text-xs text-gray-400">{{ $device->app_version ?? '—' }}</td>
                                <td class="px-4 py-3.5 text-xs font-mono text-gray-500 truncate max-w-[140px]">{{ $device->fingerprint }}</td>
                                <td class="px-4 py-3.5 text-xs text-gray-500">{{ $device->last_seen_at?->diffForHumans() ?? '—' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-6 py-10 text-center text-gray-500">No devices registered.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- ── Payouts ───────────────────────────────────────────────────── --}}
        <div class="bg-gray-900 border border-white/10 rounded-2xl overflow-hidden">
            <div class="px-6 py-4 border-b border-white/10">
                <h3 class="font-bold text-sm"><i class="fas fa-money-bill-transfer mr-2 text-blue-400"></i>Payout Requests</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-white/5 text-left text-xs uppercase tracking-wide text-gray-500">
                        <tr>
                            <th class="px-5 py-3 font-medium">Amount</th>
                            <th class="px-4 py-3 font-medium">Method</th>
                            <th class="px-4 py-3 font-medium">Status</th>
                            <th class="px-4 py-3 font-medium">Requested</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/5">
                        @forelse ($payouts as $payout)
                            @php
                                $colors = [
                                    'requested' => 'bg-amber-500/10 text-amber-400',
                                    'processing' => 'bg-blue-500/10 text-blue-400',
                                    'paid' => 'bg-green-500/10 text-green-400',
                                    'rejected' => 'bg-red-500/10 text-red-400',
                                ];
                            @endphp
                            <tr class="hover:bg-white/5 transition">
                                <td class="px-5 py-3.5 font-semibold">₦{{ number_format($payout->amount) }}</td>
                                <td class="px-4 py-3.5 text-xs text-gray-400">{{ $payout->method }}</td>
                                <td class="px-4 py-3.5">
                                    <span class="text-xs px-2.5 py-1 rounded-full font-medium {{ $colors[$payout->status] ?? 'bg-white/5 text-gray-400' }}">{{ $payout->status }}</span>
                                </td>
                                <td class="px-4 py-3.5 text-xs text-gray-500">{{ $payout->created_at->diffForHumans() }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-6 py-10 text-center text-gray-500">No payout requests yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- ── Sessions ──────────────────────────────────────────────────── --}}
        <div class="bg-gray-900 border border-white/10 rounded-2xl overflow-hidden">
            <div class="px-6 py-4 border-b border-white/10">
                <h3 class="font-bold text-sm"><i class="fas fa-headphones mr-2 text-emerald-400"></i>Recent Listen Sessions</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-white/5 text-left text-xs uppercase tracking-wide text-gray-500">
                        <tr>
                            <th class="px-5 py-3 font-medium">Campaign</th>
                            <th class="px-4 py-3 font-medium">Duration</th>
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
                                <td class="px-5 py-3.5 truncate max-w-[200px]">{{ $session->assignment?->campaign?->title ?? '—' }}</td>
                                <td class="px-4 py-3.5 text-xs text-gray-400">{{ floor($session->elapsed_seconds / 60) }}m {{ $session->elapsed_seconds % 60 }}s</td>
                                <td class="px-4 py-3.5">
                                    <span class="text-xs px-2.5 py-1 rounded-full font-medium {{ $scolors[$session->status] ?? 'bg-white/5 text-gray-400' }}">{{ $session->status }}</span>
                                </td>
                                <td class="px-4 py-3.5 text-xs text-gray-500">{{ $session->completed_at?->diffForHumans() ?? $session->started_at?->diffForHumans() ?? '—' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-6 py-10 text-center text-gray-500">No listen sessions yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- ── Wallet transactions ───────────────────────────────────────── --}}
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
                                $isCredit = in_array($txn->type, [\App\Models\WalletTransaction::TYPE_REWARD, \App\Models\WalletTransaction::TYPE_BONUS]);
                                $tcolors = [
                                    'reward' => 'bg-green-500/10 text-green-400',
                                    'bonus' => 'bg-cyan-500/10 text-cyan-400',
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
    {{-- ── User footprint (IP / country history) ────────────────────── --}}
        <div class="bg-gray-900 border border-white/10 rounded-2xl overflow-hidden xl:col-span-2">
            <div class="px-6 py-4 border-b border-white/10">
                <h3 class="font-bold text-sm"><i class="fas fa-fingerprint mr-2 text-cyan-400"></i>User Footprint — IP & Country History</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-white/5 text-left text-xs uppercase tracking-wide text-gray-500">
                        <tr>
                            <th class="px-5 py-3 font-medium">IP Address</th>
                            <th class="px-4 py-3 font-medium">Country</th>
                            <th class="px-4 py-3 font-medium text-center">Sessions</th>
                            <th class="px-4 py-3 font-medium">First Seen</th>
                            <th class="px-4 py-3 font-medium">Last Seen</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/5">
                        @forelse ($footprint as $fp)
                            <tr class="hover:bg-white/5 transition">
                                <td class="px-5 py-3.5 font-mono text-xs text-gray-300">{{ $fp->ip_address }}</td>
                                <td class="px-4 py-3.5">
                                    @if ($fp->country_code)
                                        <span class="text-xs px-2.5 py-1 rounded-full font-medium bg-purple-500/10 text-purple-400">{{ $fp->country_code }}</span>
                                    @else
                                        <span class="text-xs text-gray-600">—</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3.5 text-center font-semibold">{{ $fp->sessions }}</td>
                                <td class="px-4 py-3.5 text-xs text-gray-500">{{ \Illuminate\Support\Carbon::parse($fp->first_seen)->diffForHumans() }}</td>
                                <td class="px-4 py-3.5 text-xs text-gray-500">{{ \Illuminate\Support\Carbon::parse($fp->last_seen)->diffForHumans() }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-10 text-center text-gray-500">No IP activity recorded yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection