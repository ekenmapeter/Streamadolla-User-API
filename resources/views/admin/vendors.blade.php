@extends('admin.layout')

@section('header', 'Vendors')
@section('subtitle', 'Vendor fleets, device status and earnings')

@section('content')
    {{-- ── Global metrics ─────────────────────────────────────────────────── --}}
    <div class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-6 gap-4 mb-8">
        @foreach ([
            ['Total Vendors', $metrics['total'], 'fa-store', 'text-emerald-400'],
            ['Verified', $metrics['verified'], 'fa-circle-check', 'text-green-400'],
            ['Fleet Devices', $metrics['devices'], 'fa-mobile-screen-button', 'text-purple-400'],
            ['Active Today', $metrics['active_today'], 'fa-bolt', 'text-amber-400'],
            ['Vendor Streams Today', $metrics['streams_today'], 'fa-circle-play', 'text-cyan-400'],
            ['Vendor Earned Total', \App\Support\Money::format($metrics['total_earned'], \App\Support\Money::USD), 'fa-dollar-sign', 'text-emerald-400'],
            ['Fraud Sessions', $metrics['fraud_sessions'], 'fa-flag', 'text-red-400'],
            ['Pending Payouts', $metrics['pending_payouts'], 'fa-hourglass-half', 'text-orange-400'],
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
    <form method="GET" action="{{ route('admin.vendors') }}" class="bg-gray-900 border border-white/10 rounded-2xl p-5 mb-8 flex flex-wrap items-end gap-4">
        <div class="flex-1 min-w-[220px]">
            <label class="block text-xs text-gray-400 mb-1.5 font-medium uppercase tracking-wide">Search</label>
            <input type="text" name="q" value="{{ request('q') }}" placeholder="Name, email or phone…"
                class="w-full px-4 py-2.5 bg-white/5 border border-gray-700 rounded-xl focus:ring-2 focus:ring-emerald-500 outline-none transition-all text-sm">
        </div>
        <div>
            <label class="block text-xs text-gray-400 mb-1.5 font-medium uppercase tracking-wide">Sort By</label>
            <select name="sort" class="px-4 py-2.5 bg-white/5 border border-gray-700 rounded-xl focus:ring-2 focus:ring-emerald-500 outline-none transition-all text-sm text-gray-200">
                @foreach (['recent' => 'Recently Joined', 'earned' => 'Highest Earned', 'streams' => 'Most Streams', 'devices' => 'Most Devices'] as $val => $label)
                    <option value="{{ $val }}" {{ $sort === $val ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <button class="bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold px-6 py-2.5 rounded-xl transition">
            <i class="fas fa-filter mr-2"></i>Apply
        </button>
        @if (request()->has('q') || request()->has('sort'))
            <a href="{{ route('admin.vendors') }}" class="text-sm text-gray-400 hover:text-white px-3 py-2.5">Clear</a>
        @endif
    </form>

    <div class="grid grid-cols-1 xl:grid-cols-3 gap-8">
        {{-- Vendors table --}}
        <div class="xl:col-span-2 bg-gray-900 border border-white/10 rounded-2xl overflow-hidden">
            <div class="px-6 py-4 border-b border-white/10 flex items-center">
                <h2 class="font-bold"><i class="fas fa-store mr-2 text-emerald-400"></i>All Vendors</h2>
                <span class="ml-auto text-xs bg-white/5 text-gray-300 px-3 py-1 rounded-full">{{ $vendors->total() }} found</span>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-white/5 text-left text-xs uppercase tracking-wide text-gray-500">
                        <tr>
                            <th class="px-5 py-3 font-medium">Vendor</th>
                            <th class="px-4 py-3 font-medium text-center">Devices</th>
                            <th class="px-4 py-3 font-medium text-center">Streams Today</th>
                            <th class="px-4 py-3 font-medium text-right">Earned</th>
                            <th class="px-4 py-3 font-medium">Last Active</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/5">
                        @forelse ($vendors as $vendor)
                            <tr class="hover:bg-white/5 transition">
                                <td class="px-5 py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="h-9 w-9 rounded-full bg-gradient-to-tr from-emerald-500 to-teal-500 flex items-center justify-center text-white font-bold text-xs shrink-0">
                                            {{ strtoupper(substr($vendor->name, 0, 2)) }}
                                        </div>
                                        <div class="min-w-0">
                                            <a href="{{ route('admin.vendors.detail', $vendor) }}" class="font-semibold truncate hover:text-emerald-400 transition block">{{ $vendor->name }}</a>
                                            <p class="text-xs text-gray-500 truncate">{{ $vendor->email }}
                                                @if ($vendor->status !== 'active')
                                                    <span class="ml-1 text-red-400">· {{ $vendor->status }}</span>
                                                @endif
                                            </p>
                                            @if ((int) ($vendor->fraud_sessions_count ?? 0) > 0)
                                                <p class="text-[11px] text-red-400"><i class="fas fa-flag mr-1"></i>{{ $vendor->fraud_sessions_count }} fraud sessions</p>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-4 text-center">
                                    <p class="font-bold text-purple-400">{{ $vendor->devices_count ?? 0 }}</p>
                                    <p class="text-[10px] text-gray-500">fleet</p>
                                </td>
                                <td class="px-4 py-4 text-center">
                                    <p class="font-bold text-cyan-400">{{ (int) ($vendor->streams_today_count ?? 0) }}</p>
                                    <p class="text-[10px] text-gray-500">{{ (int) ($vendor->rewarded_streams_count ?? 0) }} total</p>
                                </td>
                                <td class="px-4 py-4 text-right font-semibold">{{ \App\Support\Money::format($vendor->total_earned ?? 0, \App\Support\Money::USD) }}</td>
                                <td class="px-4 py-4 text-xs text-gray-500">
                                    {{ $vendor->last_seen_at ? \Illuminate\Support\Carbon::parse($vendor->last_seen_at)->diffForHumans() : 'Never' }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-14 text-center text-gray-500">No vendors match your filters.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="px-6 py-4 border-t border-white/10">{{ $vendors->links() }}</div>
        </div>

        {{-- Sidebar --}}
        <div class="bg-gray-900 border border-white/10 rounded-2xl overflow-hidden h-fit">
            <div class="px-6 py-4 border-b border-white/10">
                <h3 class="font-bold text-sm"><i class="fas fa-trophy mr-2 text-amber-400"></i>Top-Earning Vendors</h3>
            </div>
            <div class="divide-y divide-white/5">
                @forelse ($topEarners as $earner)
                    <div class="px-6 py-3.5 flex items-center gap-3">
                        <span class="text-xs font-bold text-gray-500 w-4">{{ $loop->iteration }}</span>
                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-semibold truncate">{{ $earner->name }}</p>
                            <p class="text-xs text-gray-500">{{ $earner->devices_count ?? 0 }} devices</p>
                        </div>
                        <p class="text-sm font-bold text-emerald-400">{{ \App\Support\Money::format($earner->earned ?? 0, \App\Support\Money::USD) }}</p>
                    </div>
                @empty
                    <p class="px-6 py-8 text-center text-sm text-gray-500">No earnings yet.</p>
                @endforelse
            </div>
        </div>
    </div>
@endsection