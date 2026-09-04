@extends('admin.layout')

@section('header', $user ? 'Edit User' : 'Create User')
@section('subtitle', $user ? 'Update account details, role and status' : 'Add a new listener or artist account')

@section('content')
    <div class="max-w-2xl">
        <form action="{{ $user ? route('admin.users.update', $user) : route('admin.users.store') }}" method="POST" class="bg-gray-900 border border-white/10 rounded-2xl p-8">
            @csrf

            <div class="grid md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-semibold text-gray-300 mb-2">Full name</label>
                    <input type="text" name="name" value="{{ old('name', $user?->name) }}" required
                        class="w-full px-4 py-3 bg-white/5 border border-gray-700 rounded-xl focus:ring-2 focus:ring-purple-500 outline-none transition-all text-white text-sm">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-300 mb-2">Email</label>
                    <input type="email" name="email" value="{{ old('email', $user?->email) }}" required
                        class="w-full px-4 py-3 bg-white/5 border border-gray-700 rounded-xl focus:ring-2 focus:ring-purple-500 outline-none transition-all text-white text-sm">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-300 mb-2">{{ $user ? 'New password' : 'Password' }} {{ $user ? '(blank keeps current)' : '' }}</label>
                    <input type="password" name="password" {{ $user ? '' : 'required' }}
                        class="w-full px-4 py-3 bg-white/5 border border-gray-700 rounded-xl focus:ring-2 focus:ring-purple-500 outline-none transition-all text-white text-sm">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-300 mb-2">Phone</label>
                    <input type="text" name="phone" value="{{ old('phone', $user?->phone) }}"
                        class="w-full px-4 py-3 bg-white/5 border border-gray-700 rounded-xl focus:ring-2 focus:ring-purple-500 outline-none transition-all text-white text-sm">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-300 mb-2">Role</label>
                    <select name="role" class="w-full px-4 py-3 bg-white/5 border border-gray-700 rounded-xl focus:ring-2 focus:ring-purple-500 outline-none transition-all text-sm text-gray-200">
                        @foreach (['listener' => 'Listener', 'artist' => 'Artist'] as $val => $label)
                            <option value="{{ $val }}" {{ old('role', $user?->role ?? 'listener') === $val ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-300 mb-2">Status</label>
                    <select name="status" class="w-full px-4 py-3 bg-white/5 border border-gray-700 rounded-xl focus:ring-2 focus:ring-purple-500 outline-none transition-all text-sm text-gray-200">
                        @foreach (['active' => 'Active', 'suspended' => 'Suspended', 'banned' => 'Banned'] as $val => $label)
                            <option value="{{ $val }}" {{ old('status', $user?->status ?? 'active') === $val ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-300 mb-2">Genre preferences <span class="text-gray-600">(comma separated)</span></label>
                    <input type="text" name="genre_prefs" value="{{ old('genre_prefs', implode(', ', $user?->listenerProfile?->genre_prefs ?? [])) }}"
                        class="w-full px-4 py-3 bg-white/5 border border-gray-700 rounded-xl focus:ring-2 focus:ring-purple-500 outline-none transition-all text-white text-sm">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-300 mb-2">Trust level</label>
                    <select name="trust_level" class="w-full px-4 py-3 bg-white/5 border border-gray-700 rounded-xl focus:ring-2 focus:ring-purple-500 outline-none transition-all text-sm text-gray-200">
                        @for ($i = 0; $i <= 3; $i++)
                            <option value="{{ $i }}" {{ (int) old('trust_level', $user?->listenerProfile?->trust_level ?? 0) === $i ? 'selected' : '' }}>Level {{ $i }}</option>
                        @endfor
                    </select>
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm font-semibold text-gray-300 mb-2">Stage name <span class="text-gray-600">(artists only)</span></label>
                    <input type="text" name="stage_name" value="{{ old('stage_name', $user?->artistProfile?->stage_name) }}"
                        class="w-full px-4 py-3 bg-white/5 border border-gray-700 rounded-xl focus:ring-2 focus:ring-purple-500 outline-none transition-all text-white text-sm">
                </div>
            </div>

            <div class="flex items-center gap-3 mt-8">
                <button type="submit" class="bg-green-600 hover:bg-green-700 text-white text-sm font-bold px-8 py-3 rounded-xl transition">
                    <i class="fas {{ $user ? 'fa-save' : 'fa-user-plus' }} mr-2"></i>{{ $user ? 'Save Changes' : 'Create User' }}
                </button>
                <a href="{{ $user ? route('admin.listeners.detail', $user) : route('admin.listeners') }}" class="bg-white/5 hover:bg-white/10 text-gray-300 text-sm font-semibold px-6 py-3 rounded-xl transition">
                    Cancel
                </a>
            </div>
        </form>
    </div>
@endsection