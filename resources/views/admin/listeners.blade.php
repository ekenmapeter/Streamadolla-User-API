@extends('admin.layout')

@section('header', 'Listeners')
@section('subtitle', 'All listeners — engagement, trust and earnings at a glance')

@section('content')
    {{-- ── Global metrics ─────────────────────────────────────────────────── --}}
    <div class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-6 gap-4 mb-8">
        @foreach ([
            ['Total Listeners', $metrics['total'], 'fa-users', 'text-purple-400'],
            ['Verified', $metrics['verified'], 'fa-circle-check', 'text-green-400'],
            ['Active Today', $metrics['active_today'], 'fa-bolt', 'text-amber-400'],
            ['Active (7d)', $metrics['active_week'], 'fa-calendar-week', 'text-cyan-400'],
            ['Rewarded Listens', $metrics['rewarded_sessions'], 'fa-headphones', 'text-emerald-400'],
            ['Fraud Sessions', $metrics['fraud_sessions'], 'fa-flag', 'text-red-400'],
            ['Pending Payouts', $metrics['pending_payouts'], 'fa-hourglass-half', 'text-orange-400'],
            ['Total Earned', '₦' . number_format($metrics['total_earned']), 'fa-naira-sign', 'text-emerald-400'],
            ['Paid Out', '₦' . number_format($metrics['total_paid_out']), 'fa-money-bill-transfer', 'text-blue-400'],
            ['Avg Trust Level', $metrics['avg_trust'], 'fa-shield-halved', 'text-fuchsia-400'],
            ['Avg Streak', $metrics['avg_streak'], 'fa-fire', 'text-yellow-400'],
            ['FCM Devices (24h)', $metrics['fcm_devices'], 'fa-bell', 'text-slate-400'],
        ] as [$label, $value, $icon, $color])
            <div class="bg-gray-900 border border-white/10 rounded-2xl p-5">
                <div class="flex items-center justify-between mb-2">
                    <p class="text-xs text-gray-400">{{ $label }}</p>
                    <i class="fas {{ $icon }} {{ $color }}"></i>
                </div>
                <p class="text-2xl font-extrabold">{{ $value }}</p>
            </div>
        @endforeach
    </div>

    {{-- ── Filters ─────────────────────────────────────────────────────────-- }}
    <form method="GET" action="{{ route('admin.listeners') }}" class="bg-gray-900 border border-white/10 rounded-2xl p-5 mb-8 flex flex-wrap items-end gap-4">
        <div class="flex-1 min-w-[220px]">
            <label class="block text-xs text-gray-400 mb-1.5 font-medium uppercase tracking-wide">Search</label>
            <input type="text" name="q" value="{{ request('q') }}" placeholder="Name, email or phone…"
                class="w-full px-4 py-2.5 bg-white/5 border border-gray-700 rounded-xl focus:ring-2 focus:ring-purple-500 outline-none transition-all text-sm">
        </div>
        <div>
            <label class="block text-xs text-gray-400 mb-1.5 font-medium uppercase tracking-wide">Trust Level</label>
            <select name="trust" class="px-4 py-2.5 bg-white/5 border border-gray-700 rounded-xl focus:ring-2 focus:ring-purple-500 outline-none transition-all text-sm text-gray-200">
                <option value="">All</option>
                @for ($i = 0; $i <= 3; $i++)
                    <option value="{{ $i }}" {{ request('trust') !== null && (int) request('trust') === $i ? 'selected' : '' }}>Level {{ $i }}</option>
                @endfor
            </select>
        </div>
        <div>
            <label class="block text-xs text-gray-400 mb-1.5 font-medium uppercase tracking-wide">Status</label>
            <select name="status" class="px-4 py-2.5 bg-white/5 border border-gray-700 rounded-xl focus:ring-2 focus:ring-purple-500 outline-none transition-all text-sm text-gray-200">
                <option value="">All</option>
                @foreach (['active', 'suspended', 'banned'] as $st)
                    <option value="{{ $st }}" {{ request('status') === $st ? 'selected' : '' }}>{{ ucfirst($st) }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-xs text-gray-400 mb-1.5 font-medium uppercase tracking-wide">Sort By</label>
            <select name="sort" class="px-4 py-2.5 bg-white/5 border border-gray-700 rounded-xl focus:ring-2 focus:ring-purple-500 outline-none transition-all text-sm text-gray-200">
                @foreach (['recent' => 'Recently Joined', 'earned' => 'Highest Earned', 'listens' => 'Most Listens', 'fraud' => 'Most Fraud'] as $val => $label)
                    <option value="{{ $val }}" {{ $sort === $val ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <button class="bg-purple-600 hover:bg-purple-700 text-white text-sm font-semibold px-6 py-2.5 rounded-xl transition">
            <i class="fas fa-filter mr-2"></i>Apply
        </button>
        @if (request()->has('q') || request()->has('trust') || request()->has('status') || request()->has('sort'))
            <a href="{{ route('admin.listeners') }}" class="text-sm text-gray-400 hover:text-white px-3 py-2.5">Clear</a>
        @endif
    </form>

    {{-- ── Main grid ───────────────────────────────────────────────────────-- }}
    <div class="grid grid-cols-1 xl:grid-cols-3 gap-8">
        {{-- Listeners table --}}
        <div class="xl:col-span-2 bg-gray-900 border border-white/10 rounded-2xl overflow-hidden">
            <div class="px-6 py-4 border-b border-white/10 flex items-center">
                <h2 class="font-bold"><i class="fas fa-users mr-2 text-purple-400"></i>All Listeners</h2>
                <span class="ml-auto text-xs bg-white/5 text-gray-300 px-3 py-1 rounded-full">{{ $listeners->total() }} found</span>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-white/5 text-left text-xs uppercase tracking-wide text-gray-500">
                        <tr>
                            <th class="px-5 py-3 font-medium">Listener</th>
                            <th class="px-4 py-3 font-medium">Trust</th>
                            <th class="px-4 py-3 font-medium text-center">Listens</th>
                            <th class="px-4 py-3 font-medium text-right">Earned</th>
                            <th class="px-4 py-3 font-medium text-right">Balance</th>
                            <th class="px-4 py-3 font-medium">Last Active</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/5">
                        @forelse ($listeners as $listener)
                            @php
                                $trust = (int) ($listener->listenerProfile?->trust_level ?? 0);
                                $trustBadge = match ($trust) {
                                    3 => 'bg-green-500/10 text-green-400',
                                    2 => 'bg-blue-500/10 text-blue-400',
                                    1 => 'bg-amber-500/10 text-amber-400',
                                    default => 'bg-white/5 text-gray-400',
                                };
                                $earned = (int) ($listener->total_earned ?? 0);
                                $paidOut = (int) ($listener->total_paid_out ?? 0);
                                $balance = $earned - $paidOut;
                            @endphp
                            <tr class="hover:bg-white/5 transition">
                                <td class="px-5 py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="h-9 w-9 rounded-full bg-gradient-to-tr from-purple-500 to-fuchsia-500 flex items-center justify-center text-white font-bold text-xs shrink-0">
                                            {{ strtoupper(substr($listener->name, 0, 2)) }}
                                        </div>
                                        <div class="min-w-0">
                                            <p class="font-semibold truncate">{{ $listener->name }}</p>
                                            <p class="text-xs text-gray-500 truncate">{{ $listener->email }}
                                                @if ($listener->status !== 'active')
                                                    <span class="ml-1 text-red-400">· {{ $listener->status }}</span>
                                                @endif
                                            </p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-4">
                                    <span class="text-xs px-2.5 py-1 rounded-full font-medium {{ $trustBadge }}">L{{ $trust }}</span>
                                </td>
                                <td class="px-4 py-4 text-center">
                                    <p class="font-bold text-emerald-400">{{ $listener->rewarded_sessions_count ?? 0 }}</p>
                                    <p class="text-[10px] text-gray-500">
                                        {{ ($listener->fraud_sessions_count ?? 0) > 0 ? ($listener->fraud_sessions_count . ' flagged') : '·' }}
                                    </p>
                                </td>
                                <td class="px-4 py-4 text-right font-semibold">₦{{ number_format($earned) }}</td>
                                <td class="px-4 py-4 text-right {{ $balance > 0 ? 'text-green-400' : 'text-gray-500' }}">₦{{ number_format($balance) }}</td>
                                <td class="px-4 py-4 text-xs text-gray-500">
                                    {{ $listener->last_listened_at ? \Illuminate\Support\Carbon::parse($listener->last_listened_at)->diffForHumans() : 'Never' }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-14 text-center text-gray-500">No listeners match your filters.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="px-6 py-4 border-t border-white/10">{{ $listeners->links() }}</div>
        </div>

        {{-- Sidebar --}}
        <div class="space-y-8">
            {{-- Top earners --}}
            <div class="bg-gray-900 border border-white/10 rounded-2xl overflow-hidden">
                <div class="px-6 py-4 border-b border-white/10">
                    <h3 class="font-bold text-sm"><i class="fas fa-trophy mr-2 text-amber-400"></i>Top Earners</h3>
                </div>
                <div class="divide-y divide-white/5">
                    @forelse ($topEarners as $earner)
                        <div class="px-6 py-3.5 flex items-center gap-3">
                            <span class="text-xs font-bold text-gray-500 w-4">{{ $loop->iteration }}</span>
                            <div class="min-w-0 flex-1">
                                <p class="text-sm font-semibold truncate">{{ $earner->name }}</p>
                                <p class="text-xs text-gray-500">L{{ $earner->listenerProfile?->trust_level ?? 0 }} · streak {{ $earner->listenerProfile?->streak ?? 0 }}</p>
                            </div>
                            <p class="text-sm font-bold text-emerald-400">₦{{ number_format((int) $earner->earned) }}</p>
                        </div>
                    @empty
                        <p class="px-6 py-8 text-center text-sm text-gray-500">No earnings yet.</p>
                    @endforelse
                </div>
            </div>

            {{-- Trust distribution --}}
            <div class="bg-gray-900 border border-white/10 rounded-2xl overflow-hidden">
                <div class="px-6 py-4 border-b border-white/10">
                    <h3 class="font-bold text-sm"><i class="fas fa-shield-halved mr-2 text-fuchsia-400"></i>Trust Distribution</h3>
                </div>
                <div class="p-6 space-y-4">
                    @foreach (range(0, 3) as $level)
                        @php
                            $dist = $trustDist->get($level);
                            $count = $dist->total ?? 0;
                            $pct = $metrics['total'] > 0 ? round($count / $metrics['total'] * 100) : 0;
                            $colors = ['bg-white/20', 'bg-amber-500/60', 'bg-blue-500/60', 'bg-green-500/60'];
                        @endphp
                        <div>
                            <div class="flex justify-between text-xs mb-1.5">
                                <span class="text-gray-400">Level {{ $level }} <span class="text-gray-600">(₦{{ number_format((int) ($dist->earned ?? 0)) }} earned)</span></span>
                                <span class="text-gray-300 font-semibold">{{ $count }} · {{ $pct }}%</span>
                            </div>
                            <div class="h-2 bg-white/5 rounded-full overflow-hidden">
                                <div class="h-full {{ $colors[$level] }} rounded-full" style="width: {{ $pct }}%"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- Genre distribution --}}
            <div class="bg-gray-900 border border-white/10 rounded-2xl overflow-hidden">
                <div class="px-6 py-4 border-b border-white/10">
                    <h3 class="font-bold text-sm"><i class="fas fa-music mr-2 text-cyan-400"></i>Top Genres</h3>
                </div>
                <div class="p-6 space-y-3">
                    @forelse ($topGenres as $genre => $count)
                        @php
                            $max = max($topGenres) ?: 1;
                            $pct = round($count / $max * 100);
                        @endphp
                        <div class="flex items-center gap-3">
                            <span class="text-sm text-gray-300 w-28 truncate">{{ ucfirst($genre) }}</span>
                            <div class="flex-1 h-2 bg-white/5 rounded-full overflow-hidden">
                                <div class="h-full bg-gradient-to-r from-cyan-500 to-purple-500 rounded-full" style="width: {{ $pct }}%"></div>
                            </div>
                            <span class="text-xs text-gray-400 w-8 text-right">{{ $count }}</span>
                        </div>
                    @empty
                        <p class="text-sm text-gray-500 text-center py-4">No genre preferences recorded yet.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
@endsection
