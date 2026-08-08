@extends('admin.layout')

@section('header', 'App Settings')
@section('subtitle', 'Reward rates, rules and feature toggles pushed to the mobile app')

@section('content')
    <form action="{{ route('admin.settings.save') }}" method="POST">
        @csrf

        <div class="bg-gray-900 border border-white/10 rounded-2xl p-8 mb-6">
            <h2 class="font-bold mb-1">Rewards & Rules</h2>
            <p class="text-sm text-gray-500 mb-6">Minimums and rates enforced by the fraud engine and wallet.</p>

            <div class="grid md:grid-cols-3 gap-6">
                @foreach ($fields as $key => $def)
                    <div>
                        <label class="block text-sm font-semibold text-gray-300 mb-2">{{ $def[1] }}</label>
                        <input type="text" name="settings[{{ $key }}][key]" value="{{ $def[0] }}" class="hidden">
                        <input type="number" name="settings[{{ $key }}][value]"
                            value="{{ $existing[$def[0]] ?? $def[2] }}"
                            class="w-full px-4 py-3 bg-white/5 border border-gray-700 rounded-xl focus:ring-2 focus:ring-purple-500 outline-none transition-all text-white text-sm">
                    </div>
                @endforeach
            </div>

            <div class="grid md:grid-cols-2 gap-6 mt-6">
                <div>
                    <label class="block text-sm font-semibold text-gray-300 mb-2">Maintenance mode (block app usage)</label>
                    <input type="hidden" name="settings[maintenance][key]" value="maintenance_mode">
                    <label class="flex items-center space-x-3 cursor-pointer">
                        <input type="checkbox" name="settings[maintenance][value]" value="1"
                            {{ ! empty($existing['maintenance_mode']) ? 'checked' : '' }}
                            class="w-4 h-4 accent-purple-500">
                        <span class="text-sm text-gray-400">Enabled</span>
                    </label>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-300 mb-2">App version gate</label>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <input type="hidden" name="settings[appv][key]" value="app_version">
                            <input type="text" name="settings[appv][value]" placeholder="Current (e.g. 1.2.0)"
                                value="{{ $existing['app_version'] ?? '' }}"
                                class="w-full px-4 py-3 bg-white/5 border border-gray-700 rounded-xl focus:ring-2 focus:ring-purple-500 outline-none text-white text-sm">
                        </div>
                        <div>
                            <input type="hidden" name="settings[minv][key]" value="min_app_version">
                            <input type="text" name="settings[minv][value]" placeholder="Minimum required"
                                value="{{ $existing['min_app_version'] ?? '' }}"
                                class="w-full px-4 py-3 bg-white/5 border border-gray-700 rounded-xl focus:ring-2 focus:ring-purple-500 outline-none text-white text-sm">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-gray-900 border border-white/10 rounded-2xl p-8 mb-6">
            <h2 class="font-bold mb-4">Push Notification</h2>
            <p class="text-sm text-gray-500 mb-4">Broadcast a push notification to listener / artist apps (new tracks, announcements).</p>
            <form action="{{ route('admin.push') }}" method="POST">
                @csrf
                <div class="grid md:grid-cols-3 gap-4 mb-4">
                    <input type="text" name="title" placeholder="Title" class="w-full px-4 py-3 bg-white/5 border border-gray-700 rounded-xl focus:ring-2 focus:ring-purple-500 outline-none text-white text-sm">
                    <input type="text" name="message" placeholder="Message" class="w-full px-4 py-3 bg-white/5 border border-gray-700 rounded-xl focus:ring-2 focus:ring-purple-500 outline-none text-white text-sm">
                    <select name="audience" class="w-full px-4 py-3 bg-white/5 border border-gray-700 rounded-xl focus:ring-2 focus:ring-purple-500 outline-none text-white text-sm">
                        <option value="all">All devices</option>
                        <option value="listeners">Listeners only</option>
                        <option value="artists">Artists only</option>
                    </select>
                </div>
                <button type="submit" class="bg-purple-600 hover:bg-purple-700 text-white text-sm font-semibold px-6 py-2.5 rounded-xl transition">
                    <i class="fas fa-paper-plane mr-1"></i>Send Push
                </button>
            </form>
        </div>

        <div class="flex justify-end">
            <button type="submit" class="bg-green-600 hover:bg-green-700 text-white font-bold px-8 py-3 rounded-xl transition active:scale-[0.98]">
                Save All Settings
            </button>
        </div>
    </form>

    <div class="mt-10">
        <h3 class="font-bold mb-4 text-sm uppercase tracking-wide text-gray-400">Current settings table</h3>
        <div class="bg-gray-900 border border-white/10 rounded-2xl overflow-hidden">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-white/10 text-gray-400 text-left">
                        <th class="px-6 py-3 font-semibold">Key</th>
                        <th class="px-6 py-3 font-semibold">Value</th>
                        <th class="px-6 py-3 font-semibold">Group</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($settings as $setting)
                        <tr class="border-b border-white/5">
                            <td class="px-6 py-3 font-mono text-xs">{{ $setting->key }}</td>
                            <td class="px-6 py-3">{{ json_encode($setting->value) }}</td>
                            <td class="px-6 py-3 text-gray-400">{{ $setting->group }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="px-6 py-8 text-center text-gray-500">No settings yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection