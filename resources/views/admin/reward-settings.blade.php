@extends('admin.layout')

@section('header', 'Reward Settings')
@section('subtitle', 'Country-based reward rates detected from the listener device IP')

@section('content')
    @if (session('lookup_result'))
        @php $r = session('lookup_result'); @endphp
        <div class="mb-6 neu-sm px-5 py-4 text-sm font-bold" style="color: var(--neu-accent)">
            <i class="fas fa-globe mr-2"></i>
            {{ $r['ip'] }} &rarr; {{ $r['country_name'] ?? $r['country_code'] ?? 'Unknown' }}
            ({{ $r['country_code'] ?? '—' }}) &middot; ₦{{ number_format($r['amount']) }}/listen
            <span class="opacity-60">[{{ $r['source'] }} rate]</span>
        </div>
    @endif

    <div class="grid lg:grid-cols-2 gap-6 mb-6">
        <div class="bg-gray-900 border border-white/10 rounded-2xl p-8">
            <h2 class="font-bold mb-1">Default rate</h2>
            <p class="text-sm text-gray-500 mb-6">Paid per completed listen for countries without a configured rate.</p>
            <form action="{{ route('admin.rewards.default') }}" method="POST" class="flex items-end gap-4">
                @csrf
                <div class="flex-1">
                    <label class="block text-sm font-semibold text-gray-300 mb-2">Amount (₦) per listen</label>
                    <input type="number" name="reward_per_listen_default" min="0" value="{{ $default }}"
                        class="w-full px-4 py-3 bg-white/5 border border-gray-700 rounded-xl focus:ring-2 focus:ring-purple-500 outline-none transition-all text-white text-sm">
                </div>
                <button type="submit" class="bg-green-600 hover:bg-green-700 text-white text-sm font-bold px-6 py-3 rounded-xl transition">
                    Save
                </button>
            </form>
        </div>

        <div class="bg-gray-900 border border-white/10 rounded-2xl p-8">
            <h2 class="font-bold mb-1">Add country rate</h2>
            <p class="text-sm text-gray-500 mb-6">New countries default to the rate above until configured here.</p>
            <form action="{{ route('admin.rewards.store') }}" method="POST" class="grid sm:grid-cols-2 gap-4">
                @csrf
                <div>
                    <label class="block text-sm font-semibold text-gray-300 mb-2">Country code (ISO-2)</label>
                    <input type="text" name="country_code" maxlength="2" placeholder="NG" required
                        class="w-full px-4 py-3 bg-white/5 border border-gray-700 rounded-xl focus:ring-2 focus:ring-purple-500 outline-none text-white text-sm uppercase">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-300 mb-2">Country name</label>
                    <input type="text" name="country_name" placeholder="Nigeria" required
                        class="w-full px-4 py-3 bg-white/5 border border-gray-700 rounded-xl focus:ring-2 focus:ring-purple-500 outline-none text-white text-sm">
                </div>
                <div class="sm:col-span-2">
                    <label class="block text-sm font-semibold text-gray-300 mb-2">Amount (₦) per listen</label>
                    <input type="number" name="amount_per_listen" min="0" required
                        class="w-full px-4 py-3 bg-white/5 border border-gray-700 rounded-xl focus:ring-2 focus:ring-purple-500 outline-none text-white text-sm">
                </div>
                <div class="sm:col-span-2 flex justify-end">
                    <button type="submit" class="bg-purple-600 hover:bg-purple-700 text-white text-sm font-bold px-6 py-3 rounded-xl transition">
                        <i class="fas fa-plus mr-1"></i>Add Country
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="bg-gray-900 border border-white/10 rounded-2xl overflow-hidden mb-6">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-white/10 text-gray-400 text-left">
                    <th class="px-6 py-3 font-semibold">Country</th>
                    <th class="px-6 py-3 font-semibold">Code</th>
                    <th class="px-6 py-3 font-semibold">Amount / listen</th>
                    <th class="px-6 py-3 font-semibold">Active</th>
                    <th class="px-6 py-3 font-semibold text-right">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($countries as $country)
                    <tr class="border-b border-white/5">
                        <form action="{{ route('admin.rewards.update', $country) }}" method="POST">
                            @csrf
                            @method('PUT')
                            <td class="px-6 py-3">
                                <input type="text" name="country_name" value="{{ $country->country_name }}" required
                                    class="w-full px-3 py-2 bg-white/5 border border-gray-700 rounded-lg focus:ring-2 focus:ring-purple-500 outline-none text-white text-sm">
                            </td>
                            <td class="px-6 py-3 font-mono text-xs uppercase text-gray-400">{{ $country->country_code }}</td>
                            <td class="px-6 py-3">
                                <div class="flex items-center gap-1">
                                    <span class="text-gray-500">₦</span>
                                    <input type="number" name="amount_per_listen" min="0" value="{{ $country->amount_per_listen }}" required
                                        class="w-32 px-3 py-2 bg-white/5 border border-gray-700 rounded-lg focus:ring-2 focus:ring-purple-500 outline-none text-white text-sm">
                                </div>
                            </td>
                            <td class="px-6 py-3">
                                <label class="flex items-center space-x-2 cursor-pointer">
                                    <input type="hidden" name="is_active" value="0">
                                    <input type="checkbox" name="is_active" value="1" {{ $country->is_active ? 'checked' : '' }}
                                        class="w-4 h-4 accent-purple-500">
                                    <span class="text-xs {{ $country->is_active ? 'text-green-400' : 'text-gray-500' }}">{{ $country->is_active ? 'On' : 'Off' }}</span>
                                </label>
                            </td>
                            <td class="px-6 py-3">
                                <div class="flex items-center justify-end space-x-2">
                                    <button type="submit" class="text-xs font-bold text-purple-400 hover:text-purple-300 px-3 py-1.5 rounded-lg hover:bg-white/5 transition">
                                        Save
                                    </button>
                                </div>
                            </td>
                        </form>
                    </tr>
                    <tr class="border-b border-white/5">
                        <td colspan="5" class="px-6 py-1">
                            <form action="{{ route('admin.rewards.destroy', $country) }}" method="POST" class="flex justify-end"
                                onsubmit="return confirm('Remove {{ $country->country_name }}?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-xs font-bold text-red-500 hover:text-red-400 px-3 py-1 rounded-lg hover:bg-white/5 transition">
                                    <i class="fas fa-trash mr-1"></i>Delete
                                </button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-6 py-8 text-center text-gray-500">No country rates yet. Add one above.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="bg-gray-900 border border-white/10 rounded-2xl p-8">
        <h2 class="font-bold mb-1">IP lookup tester</h2>
        <p class="text-sm text-gray-500 mb-6">Resolve any IP to its country and see which rate would apply.</p>
        <form action="{{ route('admin.rewards.lookup') }}" method="POST" class="flex items-end gap-4">
            @csrf
            <div class="flex-1">
                <label class="block text-sm font-semibold text-gray-300 mb-2">IP address</label>
                <input type="text" name="ip" placeholder="e.g. 8.8.8.8" required
                    class="w-full px-4 py-3 bg-white/5 border border-gray-700 rounded-xl focus:ring-2 focus:ring-purple-500 outline-none text-white text-sm">
            </div>
            <button type="submit" class="bg-purple-600 hover:bg-purple-700 text-white text-sm font-bold px-6 py-3 rounded-xl transition">
                <i class="fas fa-magnifying-glass mr-1"></i>Lookup
            </button>
        </form>
        <p class="text-xs text-gray-500 mt-6 leading-relaxed">
            Country detection uses the free
            <a href="https://db-ip.com/" target="_blank" class="text-purple-400 hover:text-purple-300">IP Geolocation by DB-IP</a>
            (CC BY 4.0) database, refreshed automatically from the open-source
            <a href="https://github.com/sapics/ip-location-db" target="_blank" class="text-purple-400 hover:text-purple-300">ip-location-db</a>
            project. Reward amounts are in Nigerian Naira (₦).
        </p>
    </div>
@endsection