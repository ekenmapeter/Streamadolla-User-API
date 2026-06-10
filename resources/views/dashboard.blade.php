<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Stream Farm Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: {
                            '50': '#eff6ff',
                            '100': '#dbeafe',
                            '200': '#bfdbfe',
                            '300': '#93c5fd',
                            '400': '#60a5fa',
                            '500': '#3b82f6',
                            '600': '#2563eb',
                            '700': '#1d4ed8',
                            '800': '#1e40af',
                            '900': '#1e3a8a',
                            '950': '#172554'
                        },
                    }
                }
            }
        }
    </script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            font-family: 'Inter', sans-serif;
        }

        .glass-card {
            backdrop-filter: blur(10px);
        }

        .smooth-transition {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .stat-card:hover {
            transform: translateY(-5px);
        }

        .tab-btn.active {
            border-color: #3b82f6;
            color: #2563eb;
            background: #eff6ff;
        }

        .device-checkbox:checked+.device-select-card {
            border-color: #3b82f6;
            background: #eff6ff;
        }

        @keyframes pulse-dot {

            0%,
            100% {
                opacity: 1;
            }

            50% {
                opacity: 0.4;
            }
        }

        .pulse-dot {
            animation: pulse-dot 2s infinite;
        }

        @keyframes slideIn {
            from {
                transform: translateX(100%);
                opacity: 0;
            }

            to {
                transform: translateX(0);
                opacity: 1;
            }
        }

        .slide-in {
            animation: slideIn 0.3s ease-out;
        }

        /* Modal fix - ensure flex when visible */
        [id$="Modal"]:not(.hidden) {
            display: flex !important;
        }
    </style>
</head>

<body class="bg-gradient-to-br from-gray-50 to-gray-100 min-h-screen text-gray-800">

    <!-- Top Navigation -->
    <nav class="bg-white/80 glass-card border-b border-gray-200 sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <div class="flex items-center space-x-3">
                    <div class="bg-gradient-to-r from-primary-600 to-blue-500 p-2 rounded-lg">
                        <i class="fas fa-satellite-dish text-white text-xl"></i>
                    </div>
                    <div>
                        <h1
                            class="text-2xl font-bold bg-gradient-to-r from-primary-700 to-gray-900 bg-clip-text text-transparent">
                            Stream Farm Control</h1>
                        <p class="text-xs text-gray-500">Managing <span id="nav-total-count">{{ $totalCount }}</span>
                            devices &bull; <span id="nav-active-count">{{ $activeAssignmentCount ?? 0 }}</span> active
                            tasks</p>
                    </div>
                </div>
                <div class="hidden md:flex items-center space-x-6">
                    <a href="{{ route('dashboard') }}" class="text-sm font-bold text-primary-700 hover:text-primary-800 transition-colors">
                        <i class="fas fa-chart-line mr-1.5"></i>Dashboard
                    </a>
                    <a href="{{ route('assignments.index') }}" class="text-sm font-medium text-gray-500 hover:text-primary-600 transition-colors">
                        <i class="fas fa-list-check mr-1.5"></i>Assignments
                    </a>
                </div>
                <div class="flex items-center space-x-4">
                    <a href="{{ route('assignments.index') }}"
                        class="px-4 py-2 bg-primary-50 hover:bg-primary-100 text-primary-700 rounded-lg smooth-transition font-medium text-sm border border-primary-100">
                        <i class="fas fa-list-check mr-2"></i>View Assignments
                    </a>
                    <button onclick="document.getElementById('manualRegisterModal').classList.remove('hidden')"
                        class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg smooth-transition font-medium text-sm border border-slate-200">
                        <i class="fas fa-plus mr-2"></i>Manual Register
                    </button>
                    <div class="relative group">
                        <button
                            class="h-10 w-10 rounded-full bg-gradient-to-r from-cyan-500 to-primary-500 flex items-center justify-center text-white font-bold shadow-lg hover:ring-4 hover:ring-primary-100 transition-all">
                            {{ substr(auth()->user()->name ?? 'AD', 0, 2) }}
                        </button>
                        <div
                            class="absolute right-0 mt-2 w-48 bg-white rounded-xl shadow-xl py-2 invisible group-hover:visible opacity-0 group-hover:opacity-100 transition-all z-[60] border border-gray-100">
                            <div class="px-4 py-2 border-b border-gray-100">
                                <p class="text-xs text-gray-400">Signed in as</p>
                                <p class="text-sm font-bold text-gray-800 truncate">{{ auth()->user()->email }}</p>
                            </div>
                            <form action="{{ route('logout') }}" method="POST">
                                @csrf
                                <button type="submit"
                                    class="w-full text-left px-4 py-3 text-sm text-red-600 hover:bg-red-50 flex items-center transition-colors">
                                    <i class="fas fa-sign-out-alt mr-2"></i> Sign Out
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Alerts -->
        @if (session('success'))
            <div
                class="mb-6 p-4 bg-gradient-to-r from-green-50 to-emerald-50 border border-green-200 rounded-xl flex items-center justify-between slide-in">
                <div class="flex items-center">
                    <div class="h-10 w-10 rounded-full bg-green-100 flex items-center justify-center mr-4"><i
                            class="fas fa-check-circle text-green-600"></i></div>
                    <div>
                        <p class="font-medium text-green-800">{{ session('success') }}</p>
                    </div>
                </div>
                <button onclick="this.parentElement.remove()" class="text-green-800 hover:text-green-900"><i
                        class="fas fa-times"></i></button>
            </div>
        @endif
        @if (session('error'))
            <div
                class="mb-6 p-4 bg-gradient-to-r from-red-50 to-rose-50 border border-red-200 rounded-xl flex items-center justify-between slide-in">
                <div class="flex items-center">
                    <div class="h-10 w-10 rounded-full bg-red-100 flex items-center justify-center mr-4"><i
                            class="fas fa-exclamation-triangle text-red-600"></i></div>
                    <div>
                        <p class="font-medium text-red-800">{{ session('error') }}</p>
                    </div>
                </div>
                <button onclick="this.parentElement.remove()" class="text-red-800 hover:text-red-900"><i
                        class="fas fa-times"></i></button>
            </div>
        @endif

        <!-- Stats Grid -->
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4 mb-8">
            <div
                class="bg-white rounded-2xl shadow-lg p-5 smooth-transition stat-card border border-gray-100 hover:shadow-xl">
                <div class="flex justify-between items-start">
                    <div>
                        <p class="text-xs font-medium text-gray-500 mb-1">Online</p>
                        <p class="text-2xl font-bold text-gray-900" id="stat-online">{{ $onlineCount ?? 0 }}</p>
                    </div>
                    <div
                        class="h-10 w-10 rounded-full bg-gradient-to-r from-green-100 to-emerald-100 flex items-center justify-center">
                        <i class="fas fa-wifi text-green-600"></i>
                    </div>
                </div>
            </div>
            <div
                class="bg-white rounded-2xl shadow-lg p-5 smooth-transition stat-card border border-gray-100 hover:shadow-xl">
                <div class="flex justify-between items-start">
                    <div>
                        <p class="text-xs font-medium text-gray-500 mb-1">Streaming</p>
                        <p class="text-2xl font-bold text-gray-900" id="stat-streaming">{{ $streamingCount ?? 0 }}</p>
                    </div>
                    <div
                        class="h-10 w-10 rounded-full bg-gradient-to-r from-blue-100 to-cyan-100 flex items-center justify-center">
                        <i class="fas fa-music text-blue-600"></i>
                    </div>
                </div>
            </div>
            <div
                class="bg-white rounded-2xl shadow-lg p-5 smooth-transition stat-card border border-gray-100 hover:shadow-xl">
                <div class="flex justify-between items-start">
                    <div>
                        <p class="text-xs font-medium text-gray-500 mb-1">Offline</p>
                        <p class="text-2xl font-bold text-gray-900" id="stat-offline">{{ $offlineCount ?? 0 }}</p>
                    </div>
                    <div
                        class="h-10 w-10 rounded-full bg-gradient-to-r from-gray-100 to-slate-100 flex items-center justify-center">
                        <i class="fas fa-power-off text-gray-500"></i>
                    </div>
                </div>
            </div>
            <div
                class="bg-white rounded-2xl shadow-lg p-5 smooth-transition stat-card border border-gray-100 hover:shadow-xl">
                <div class="flex justify-between items-start">
                    <div>
                        <p class="text-xs font-medium text-gray-500 mb-1">Total</p>
                        <p class="text-2xl font-bold text-gray-900" id="stat-total">{{ $totalCount ?? 0 }}</p>
                    </div>
                    <div
                        class="h-10 w-10 rounded-full bg-gradient-to-r from-purple-100 to-violet-100 flex items-center justify-center">
                        <i class="fas fa-mobile-alt text-purple-600"></i>
                    </div>
                </div>
            </div>
            <div
                class="bg-white rounded-2xl shadow-lg p-5 smooth-transition stat-card border border-gray-100 hover:shadow-xl">
                <div class="flex justify-between items-start">
                    <div>
                        <p class="text-xs font-medium text-gray-500 mb-1">Active Tasks</p>
                        <p class="text-2xl font-bold text-primary-600" id="stat-active">
                            {{ $activeAssignmentCount ?? 0 }}</p>
                    </div>
                    <div
                        class="h-10 w-10 rounded-full bg-gradient-to-r from-primary-100 to-blue-100 flex items-center justify-center">
                        <i class="fas fa-tasks text-primary-600"></i>
                    </div>
                </div>
            </div>
            <div
                class="bg-white rounded-2xl shadow-lg p-5 smooth-transition stat-card border border-gray-100 hover:shadow-xl">
                <div class="flex justify-between items-start">
                    <div>
                        <p class="text-xs font-medium text-gray-500 mb-1">Errors (24h)</p>
                        <p class="text-2xl font-bold {{ ($errorCount ?? 0) > 0 ? 'text-red-600' : 'text-gray-900' }}">
                            {{ $errorCount ?? 0 }}</p>
                    </div>
                    <div
                        class="h-10 w-10 rounded-full bg-gradient-to-r from-red-100 to-rose-100 flex items-center justify-center">
                        <i class="fas fa-exclamation-triangle text-red-600"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tab Navigation -->
        <div class="flex space-x-1 mb-6 bg-white rounded-xl p-1 shadow-sm border border-gray-200 max-w-3xl">
            <button
                class="tab-btn active flex-1 px-4 py-2.5 rounded-lg text-sm font-semibold border-2 border-transparent smooth-transition"
                data-tab="assign">
                <i class="fas fa-tasks mr-2"></i>Assign Tasks
            </button>
            <button
                class="tab-btn flex-1 px-4 py-2.5 rounded-lg text-sm font-semibold border-2 border-transparent smooth-transition text-gray-500 hover:text-gray-700"
                data-tab="campaigns">
                <i class="fas fa-list-music mr-2"></i>Campaigns
            </button>
            <button
                class="tab-btn flex-1 px-4 py-2.5 rounded-lg text-sm font-semibold border-2 border-transparent smooth-transition text-gray-500 hover:text-gray-700"
                data-tab="broadcast">
                <i class="fas fa-broadcast-tower mr-2"></i>Broadcast All
            </button>
            <button
                class="tab-btn flex-1 px-4 py-2.5 rounded-lg text-sm font-semibold border-2 border-transparent smooth-transition text-gray-500 hover:text-gray-700"
                data-tab="logs">
                <i class="fas fa-scroll mr-2"></i>Activity Log
            </button>
        </div>

        <!-- TAB: Assign Tasks -->
        <div id="tab-assign" class="tab-content">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Assignment Form -->
                <div class="lg:col-span-2">
                    <div class="bg-white rounded-2xl shadow-lg overflow-hidden border border-gray-100">
                        <div class="bg-gradient-to-r from-primary-600 to-blue-500 px-6 py-4">
                            <h2 class="text-xl font-bold text-white flex items-center"><i
                                    class="fas fa-tasks mr-3"></i>
                                Assign Track / Playlist to Devices</h2>
                            <p class="text-primary-100 text-sm">Select devices and assign individual media to play</p>
                        </div>
                        <div class="p-6">
                            <form action="{{ route('assign.task') }}" method="POST" id="assignForm">
                                @csrf
                                <!-- Platform Selection -->
                                <div class="mb-6">
                                    <label class="block text-sm font-medium text-gray-700 mb-2"><i
                                            class="fas fa-headphones mr-2 text-primary-500"></i>Platform</label>
                                    <div class="grid grid-cols-3 gap-3">
                                        <label
                                            class="assign-platform-btn flex items-center p-3 border-2 rounded-xl cursor-pointer smooth-transition border-primary-500 bg-primary-50"
                                            data-platform="spotify">
                                            <input type="radio" name="platform" value="spotify" class="hidden"
                                                checked>
                                            <i class="fab fa-spotify text-green-500 text-xl mr-2"></i>
                                            <span class="font-medium">Spotify</span>
                                        </label>
                                        <label
                                            class="assign-platform-btn flex items-center p-3 border-2 rounded-xl cursor-pointer smooth-transition border-gray-200"
                                            data-platform="apple_music">
                                            <input type="radio" name="platform" value="apple_music" class="hidden">
                                            <i class="fab fa-apple text-red-600 text-xl mr-2"></i>
                                            <span class="font-medium">Apple Music</span>
                                        </label>
                                        <label
                                            class="assign-platform-btn flex items-center p-3 border-2 rounded-xl cursor-pointer smooth-transition border-gray-200"
                                            data-platform="youtube">
                                            <input type="radio" name="platform" value="youtube" class="hidden">
                                            <i class="fab fa-youtube text-red-500 text-xl mr-2"></i>
                                            <span class="font-medium">YouTube</span>
                                        </label>
                                    </div>
                                </div>

                                <!-- Media URL -->
                                <div class="mb-6">
                                    <div id="assign-spotify-group">
                                        <label class="block text-sm font-medium text-gray-700 mb-2"><i
                                                class="fab fa-spotify mr-2 text-green-500"></i>Spotify URI or
                                            URL</label>
                                        <input type="text" id="assign_media_spotify"
                                            placeholder="spotify:track:xxx or spotify:playlist:xxx"
                                            class="w-full p-4 border border-gray-300 rounded-xl focus:ring-2 focus:ring-primary-500 focus:border-primary-500 smooth-transition">
                                        <p class="text-xs text-gray-500 mt-1">Supports track, album, or playlist URIs
                                        </p>
                                    </div>
                                    <div id="assign-apple_music-group" class="hidden">
                                        <label class="block text-sm font-medium text-gray-700 mb-2"><i
                                                class="fab fa-apple mr-2 text-red-600"></i>Apple Music URL</label>
                                        <input type="text" id="assign_media_apple_music"
                                            placeholder="https://music.apple.com/..."
                                            class="w-full p-4 border border-gray-300 rounded-xl focus:ring-2 focus:ring-red-500 focus:border-red-500 smooth-transition">
                                        <p class="text-xs text-gray-500 mt-1">Supports song, album, or playlist URLs</p>
                                    </div>
                                    <div id="assign-youtube-group" class="hidden">
                                        <label class="block text-sm font-medium text-gray-700 mb-2"><i
                                                class="fab fa-youtube mr-2 text-red-500"></i>YouTube URL</label>
                                        <input type="text" id="assign_media_youtube"
                                            placeholder="https://www.youtube.com/watch?v=..."
                                            class="w-full p-4 border border-gray-300 rounded-xl focus:ring-2 focus:ring-red-500 focus:border-red-500 smooth-transition">
                                        <p class="text-xs text-gray-500 mt-1">Supports video or playlist URLs</p>
                                    </div>
                                    <input type="hidden" name="media_url" id="assign_media_url">
                                </div>

                                <!-- Optional Title -->
                                <div class="mb-6">
                                    <label class="block text-sm font-medium text-gray-700 mb-2"><i
                                            class="fas fa-tag mr-2 text-amber-500"></i>Task Label <span
                                            class="text-gray-400">(optional)</span></label>
                                    <input type="text" name="media_title"
                                        placeholder="e.g. Morning Playlist, Album Drop..."
                                        class="w-full p-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-primary-500 focus:border-primary-500 smooth-transition">
                                </div>

                                <!-- Device Selection -->
                                <div class="mb-6">
                                    <div class="flex items-center justify-between mb-3">
                                        <label class="block text-sm font-medium text-gray-700"><i
                                                class="fas fa-mobile-alt mr-2 text-purple-500"></i>Select
                                            Devices</label>
                                        <div class="flex space-x-2">
                                            <button type="button" id="selectAllDevices"
                                                class="text-xs px-3 py-1 bg-primary-50 text-primary-600 rounded-lg hover:bg-primary-100 smooth-transition font-medium">Select
                                                All Online</button>
                                            <button type="button" id="deselectAllDevices"
                                                class="text-xs px-3 py-1 bg-gray-50 text-gray-600 rounded-lg hover:bg-gray-100 smooth-transition font-medium">Clear</button>
                                        </div>
                                    </div>
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 max-h-[300px] overflow-y-auto pr-2"
                                        id="assign-device-list">
                                        @foreach ($devices as $device)
                                            @php
                                                $sc = [
                                                    'online' => [
                                                        'c' => 'text-green-600',
                                                        'b' => 'bg-green-100',
                                                        'i' => 'fa-wifi',
                                                    ],
                                                    'streaming' => [
                                                        'c' => 'text-blue-600',
                                                        'b' => 'bg-blue-100',
                                                        'i' => 'fa-music',
                                                    ],
                                                    'offline' => [
                                                        'c' => 'text-gray-400',
                                                        'b' => 'bg-gray-100',
                                                        'i' => 'fa-power-off',
                                                    ],
                                                ];
                                                $cfg = $sc[$device->status] ?? $sc['offline'];
                                                $ca = $device->currentAssignment;
                                            @endphp
                                            <label class="relative cursor-pointer"
                                                id="assign-device-{{ $device->id }}">
                                                <input type="checkbox" name="device_ids[]"
                                                    value="{{ $device->id }}" class="device-checkbox hidden peer"
                                                    {{ in_array($device->status, ['online', 'streaming']) ? '' : 'disabled' }}>
                                                <div
                                                    class="device-select-card p-3 border-2 border-gray-200 rounded-xl smooth-transition hover:border-primary-300 peer-checked:border-primary-500 peer-checked:bg-primary-50 {{ $device->status === 'offline' ? 'opacity-50' : '' }}">
                                                    <div class="flex items-center">
                                                        <div
                                                            class="h-8 w-8 rounded-full {{ $cfg['b'] }} flex items-center justify-center mr-3 flex-shrink-0">
                                                            <i
                                                                class="fas {{ $cfg['i'] }} {{ $cfg['c'] }} text-xs"></i>
                                                        </div>
                                                        <div class="min-w-0 flex-1">
                                                            <p class="font-medium text-gray-900 text-sm truncate">
                                                                {{ $device->name ?? 'Unnamed' }}</p>
                                                            <p class="text-xs text-gray-400 truncate">
                                                                {{ $device->device_id }}</p>
                                                            @if ($ca)
                                                                <p class="text-xs mt-1 truncate">
                                                                    <i
                                                                        class="fab fa-{{ $ca->platform === 'apple_music' ? 'apple' : $ca->platform }} {{ $ca->platform === 'spotify' ? 'text-green-500' : ($ca->platform === 'apple_music' ? 'text-red-600' : 'text-red-500') }} mr-1"></i>
                                                                    <span
                                                                        class="text-gray-500">{{ $ca->media_title ?? Str::limit($ca->media_url, 25) }}</span>
                                                                </p>
                                                            @endif
                                                        </div>
                                                        <div class="ml-2 flex-shrink-0">
                                                            <div
                                                                class="h-5 w-5 rounded border-2 border-gray-300 peer-checked:bg-primary-500 peer-checked:border-primary-500 flex items-center justify-center smooth-transition">
                                                                <i class="fas fa-check text-white text-xs hidden"></i>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </label>
                                        @endforeach
                                    </div>
                                    <p class="text-xs text-gray-400 mt-2"><span id="selectedCount">0</span> device(s)
                                        selected</p>
                                </div>

                                <button type="submit" id="assignSubmitBtn"
                                    class="w-full bg-gradient-to-r from-primary-600 to-blue-500 hover:from-primary-700 hover:to-blue-600 text-white font-semibold py-4 px-6 rounded-xl smooth-transition flex items-center justify-center shadow-lg hover:shadow-xl disabled:opacity-50"
                                    disabled>
                                    <i class="fas fa-paper-plane mr-3"></i> Assign Task to Selected Devices
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Devices & Active Assignments Panel -->
                <div class="space-y-6">
                    <!-- Active Assignments -->
                    <div class="bg-white rounded-2xl shadow-lg overflow-hidden border border-gray-100">
                        <div class="bg-gradient-to-r from-violet-600 to-purple-500 px-6 py-4">
                            <h2 class="text-lg font-bold text-white flex items-center">
                                <i class="fas fa-list-check mr-3"></i> Active Tasks
                                <span
                                    class="ml-auto bg-white/20 text-white text-sm font-medium px-3 py-1 rounded-full">{{ $activeAssignmentCount ?? 0 }}</span>
                            </h2>
                        </div>
                        <div class="p-4" id="active-tasks-container">
                            @if (isset($activeAssignments) && $activeAssignments->count() > 0)
                                <div class="space-y-3 max-h-[400px] overflow-y-auto pr-1">
                                    @foreach ($activeAssignments as $assignment)
                                        <div class="p-3 border border-gray-200 rounded-xl smooth-transition hover:shadow-sm"
                                            data-assignment-id="{{ $assignment->id }}">
                                            <div class="flex items-start justify-between mb-2">
                                                <div class="flex items-center min-w-0">
                                                    <i
                                                        class="fab fa-{{ $assignment->platform === 'apple_music' ? 'apple' : $assignment->platform }} {{ $assignment->platform === 'spotify' ? 'text-green-500' : ($assignment->platform === 'apple_music' ? 'text-red-600' : 'text-red-500') }} text-lg mr-2 flex-shrink-0"></i>
                                                    <div class="min-w-0">
                                                        <p class="font-medium text-sm text-gray-900 truncate">
                                                            {{ $assignment->media_title ?? 'Untitled' }}</p>
                                                        <p class="text-xs text-gray-400 truncate">
                                                            {{ $assignment->device->name ?? 'Unknown Device' }}</p>
                                                    </div>
                                                </div>
                                                @php
                                                    $statusColors = [
                                                        'pending' => 'bg-amber-100 text-amber-700',
                                                        'playing' => 'bg-green-100 text-green-700',
                                                        'paused' => 'bg-blue-100 text-blue-700',
                                                        'stopped' => 'bg-gray-100 text-gray-700',
                                                        'failed' => 'bg-red-100 text-red-700',
                                                    ];
                                                @endphp
                                                <span
                                                    class="text-xs px-2 py-0.5 rounded-full font-medium {{ $statusColors[$assignment->status] ?? 'bg-gray-100' }} flex-shrink-0">
                                                    {{ ucfirst($assignment->status) }}
                                                </span>
                                            </div>
                                            <p class="text-xs text-gray-400 font-mono truncate mb-2">
                                                {{ Str::limit($assignment->media_url, 40) }}</p>
                                            <div class="flex space-x-2">
                                                @if ($assignment->status === 'paused' || $assignment->status === 'pending')
                                                    <button
                                                        class="assignment-control flex-1 text-xs px-2 py-1.5 bg-green-50 text-green-600 rounded-lg hover:bg-green-100 smooth-transition font-medium"
                                                        data-id="{{ $assignment->id }}" data-action="play"><i
                                                            class="fas fa-play mr-1"></i>Play</button>
                                                @endif
                                                @if ($assignment->status === 'playing')
                                                    <button
                                                        class="assignment-control flex-1 text-xs px-2 py-1.5 bg-blue-50 text-blue-600 rounded-lg hover:bg-blue-100 smooth-transition font-medium"
                                                        data-id="{{ $assignment->id }}" data-action="pause"><i
                                                            class="fas fa-pause mr-1"></i>Pause</button>
                                                @endif
                                                <button
                                                    class="assignment-control flex-1 text-xs px-2 py-1.5 bg-red-50 text-red-600 rounded-lg hover:bg-red-100 smooth-transition font-medium"
                                                    data-id="{{ $assignment->id }}" data-action="stop"><i
                                                        class="fas fa-stop mr-1"></i>Stop</button>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <div class="text-center py-8">
                                    <div
                                        class="h-14 w-14 rounded-full bg-gray-100 flex items-center justify-center mx-auto mb-3">
                                        <i class="fas fa-tasks text-gray-400 text-xl"></i>
                                    </div>
                                    <p class="text-sm text-gray-500">No active tasks</p>
                                    <p class="text-xs text-gray-400">Assign a track to a device to get started</p>
                                </div>
                            @endif
                        </div>
                    </div>

                    <!-- Connected Devices -->
                    <div class="bg-white rounded-2xl shadow-lg overflow-hidden border border-gray-100">
                        <div class="bg-gradient-to-r from-gray-800 to-slate-900 px-6 py-4">
                            <h2 class="text-lg font-bold text-white flex items-center">
                                <i class="fas fa-server mr-3"></i> Devices
                                <span class="ml-auto bg-white/20 text-white text-sm font-medium px-3 py-1 rounded-full"
                                    id="device-count-badge">{{ $devices->count() }}</span>
                            </h2>
                        </div>
                        <div class="p-4">
                            @if ($devices->count() > 0)
                                <div class="space-y-3 max-h-[350px] overflow-y-auto pr-1" id="connected-devices-list">
                                    @foreach ($devices as $device)
                                        @php
                                            $sc2 = [
                                                'online' => [
                                                    'c' => 'text-green-600',
                                                    'b' => 'bg-green-100',
                                                    'i' => 'fa-wifi',
                                                    'd' => 'bg-green-500',
                                                ],
                                                'streaming' => [
                                                    'c' => 'text-blue-600',
                                                    'b' => 'bg-blue-100',
                                                    'i' => 'fa-music',
                                                    'd' => 'bg-blue-500',
                                                ],
                                                'offline' => [
                                                    'c' => 'text-gray-400',
                                                    'b' => 'bg-gray-100',
                                                    'i' => 'fa-power-off',
                                                    'd' => 'bg-gray-400',
                                                ],
                                            ];
                                            $cfg2 = $sc2[$device->status] ?? $sc2['offline'];
                                            $ca2 = $device->currentAssignment;
                                        @endphp
                                        <div class="p-3 border border-gray-200 rounded-xl smooth-transition hover:border-primary-300 hover:shadow-sm"
                                            id="device-card-{{ $device->id }}">
                                            <div class="flex justify-between items-start">
                                                <div class="flex items-center min-w-0">
                                                    <div class="relative mr-3 flex-shrink-0">
                                                        <div
                                                            class="h-8 w-8 rounded-full {{ $cfg2['b'] }} flex items-center justify-center">
                                                            <i
                                                                class="fas {{ $cfg2['i'] }} {{ $cfg2['c'] }} text-xs"></i>
                                                        </div>
                                                        <div
                                                            class="absolute -bottom-0.5 -right-0.5 h-3 w-3 rounded-full {{ $cfg2['d'] }} border-2 border-white {{ $device->status !== 'offline' ? 'pulse-dot' : '' }}">
                                                        </div>
                                                    </div>
                                                    <div class="min-w-0">
                                                        <p class="font-medium text-sm text-gray-900 truncate">
                                                            {{ $device->name ?? 'Unnamed' }}</p>
                                                        <p class="text-xs text-gray-400">
                                                            {{ $device->last_seen ? $device->last_seen->diffForHumans() : 'Never' }}
                                                        </p>
                                                        @if ($ca2)
                                                            <p class="text-xs mt-0.5"><i
                                                                    class="fab fa-{{ $ca2->platform === 'apple_music' ? 'apple' : $ca2->platform }} {{ $ca2->platform === 'spotify' ? 'text-green-500' : ($ca2->platform === 'apple_music' ? 'text-red-600' : 'text-red-500') }} mr-1"></i><span
                                                                    class="text-gray-500">{{ $ca2->media_title ?? Str::limit($ca2->media_url, 20) }}</span>
                                                            </p>
                                                        @endif
                                                    </div>
                                                </div>
                                                <div class="flex space-x-1 flex-shrink-0">
                                                    <button
                                                        class="send-single h-8 w-8 rounded-lg bg-primary-50 text-primary-600 hover:bg-primary-100 smooth-transition flex items-center justify-center"
                                                        data-device-id="{{ $device->id }}" title="Assign Track"><i
                                                            class="fas fa-play text-xs"></i></button>
                                                    <button
                                                        class="edit-device h-8 w-8 rounded-lg bg-amber-50 text-amber-600 hover:bg-amber-100 smooth-transition flex items-center justify-center"
                                                        data-device-id="{{ $device->id }}"
                                                        data-device-name="{{ $device->name }}"
                                                        title="Rename Device"><i
                                                            class="fas fa-edit text-xs"></i></button>
                                                    <button
                                                        class="remove-device h-8 w-8 rounded-lg bg-red-50 text-red-600 hover:bg-red-100 smooth-transition flex items-center justify-center"
                                                        data-device-id="{{ $device->id }}" title="Remove"><i
                                                            class="fas fa-trash-alt text-xs"></i></button>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <div class="text-center py-8">
                                    <div
                                        class="h-14 w-14 rounded-full bg-gray-100 flex items-center justify-center mx-auto mb-3">
                                        <i class="fas fa-mobile-alt text-gray-400 text-xl"></i>
                                    </div>
                                    <p class="text-sm text-gray-500">No devices connected</p>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- TAB: Campaigns -->
        <div id="tab-campaigns" class="tab-content hidden">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Create Campaign Form -->
                <div class="lg:col-span-1">
                    <div class="bg-white rounded-2xl shadow-lg overflow-hidden border border-gray-100">
                        <div class="bg-gradient-to-r from-emerald-600 to-green-500 px-6 py-4">
                            <h2 class="text-lg font-bold text-white flex items-center"><i
                                    class="fas fa-plus-circle mr-3"></i> New Campaign</h2>
                            <p class="text-emerald-100 text-sm">Create a looping playlist for devices</p>
                        </div>
                        <div class="p-6">
                            <!-- Campaign Name -->
                            <div class="mb-4">
                                <label class="block text-sm font-medium text-gray-700 mb-2"><i
                                        class="fas fa-tag mr-2 text-emerald-500"></i>Campaign Name</label>
                                <input type="text" id="campaign_name" placeholder="e.g. Morning HipHop Loop"
                                    class="w-full p-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 smooth-transition">
                            </div>

                            <!-- Platform -->
                            <div class="mb-4">
                                <label class="block text-sm font-medium text-gray-700 mb-2"><i
                                        class="fas fa-headphones mr-2 text-emerald-500"></i>Platform</label>
                                <div class="grid grid-cols-3 gap-3">
                                    <label
                                        class="campaign-platform-btn flex items-center p-3 border-2 rounded-xl cursor-pointer smooth-transition border-emerald-500 bg-emerald-50"
                                        data-platform="spotify">
                                        <input type="radio" name="campaign_platform" value="spotify"
                                            class="hidden" checked>
                                        <i class="fab fa-spotify text-green-500 text-xl mr-2"></i><span
                                            class="font-medium">Spotify</span>
                                    </label>
                                    <label
                                        class="campaign-platform-btn flex items-center p-3 border-2 rounded-xl cursor-pointer smooth-transition border-gray-200"
                                        data-platform="apple_music">
                                        <input type="radio" name="campaign_platform" value="apple_music"
                                            class="hidden">
                                        <i class="fab fa-apple text-red-600 text-xl mr-2"></i><span
                                            class="font-medium">Apple Music</span>
                                    </label>
                                    <label
                                        class="campaign-platform-btn flex items-center p-3 border-2 rounded-xl cursor-pointer smooth-transition border-gray-200"
                                        data-platform="youtube">
                                        <input type="radio" name="campaign_platform" value="youtube"
                                            class="hidden">
                                        <i class="fab fa-youtube text-red-500 text-xl mr-2"></i><span
                                            class="font-medium">YouTube</span>
                                    </label>
                                </div>
                            </div>

                            <!-- Channel URL (shown for YouTube) -->
                            <div id="campaign-channel-url-group" class="mb-4 hidden">
                                <label class="block text-sm font-medium text-gray-700 mb-2"><i
                                        class="fab fa-youtube mr-2 text-red-500"></i>YouTube Channel URL</label>
                                <input type="text" id="campaign_channel_url"
                                    placeholder="https://www.youtube.com/@ChannelName"
                                    class="w-full p-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-red-500 focus:border-red-500 smooth-transition">
                                <p class="text-xs text-gray-400 mt-1">Add channel URL to mark tracks as channel/similar videos</p>
                            </div>

                            <!-- Interstitial Settings -->
                            <div class="mb-4 p-4 bg-amber-50 rounded-xl border border-amber-200">
                                <div class="flex items-center justify-between mb-3">
                                    <label class="block text-sm font-medium text-amber-800"><i
                                            class="fas fa-random mr-2 text-amber-600"></i>Interstitial Content</label>
                                    <label class="flex items-center text-xs text-amber-700 cursor-pointer">
                                        <input type="checkbox" id="campaign_interstitial_enabled" class="h-4 w-4 text-amber-600 rounded border-amber-300 mr-1">
                                        <span>Enabled</span>
                                    </label>
                                </div>
                                <div id="campaign-interstitial-fields" class="space-y-3 hidden">
                                    <div>
                                        <label class="block text-xs font-medium text-amber-700 mb-1">Play interstitial after every</label>
                                        <div class="flex items-center space-x-2">
                                            <input type="number" id="campaign_interstitial_every" value="5" min="1" max="100"
                                                class="w-20 p-2.5 border border-amber-300 rounded-lg text-sm text-center focus:ring-2 focus:ring-amber-500">
                                            <span class="text-xs text-amber-600">playlist tracks</span>
                                        </div>
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-amber-700 mb-1">Interstitial Media URL</label>
                                        <input type="text" id="campaign_interstitial_media_url"
                                            placeholder="spotify:track:xxx or YouTube URL"
                                            class="w-full p-2.5 border border-amber-300 rounded-lg text-sm focus:ring-2 focus:ring-amber-500">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-amber-700 mb-1">Interstitial Duration (seconds)</label>
                                        <input type="number" id="campaign_interstitial_duration" value="120" min="30" max="7200"
                                            class="w-20 p-2.5 border border-amber-300 rounded-lg text-sm text-center focus:ring-2 focus:ring-amber-500">
                                    </div>
                                </div>
                            </div>

                            <!-- Tracks -->
                            <div class="mb-4">
                                <div class="flex items-center justify-between mb-2">
                                    <label class="block text-sm font-medium text-gray-700"><i
                                            class="fas fa-music mr-2 text-emerald-500"></i>Tracks</label>
                                    <button type="button" id="addTrackBtn"
                                        class="text-xs px-3 py-1 bg-emerald-50 text-emerald-600 rounded-lg hover:bg-emerald-100 smooth-transition font-medium"><i
                                            class="fas fa-plus mr-1"></i>Add Track</button>
                                </div>
                                <div id="campaignTracksContainer"
                                    class="space-y-2 max-h-[300px] overflow-y-auto pr-1">
                                    <div class="campaign-track-row flex items-center space-x-2">
                                        <span class="text-xs text-gray-400 font-bold w-5 flex-shrink-0">1</span>
                                        <input type="text" placeholder="URL"
                                            class="campaign-track-url flex-1 p-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-emerald-500">
                                        <input type="text" placeholder="Title"
                                            class="campaign-track-title w-24 p-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-emerald-500">
                                        <input type="number" placeholder="180" value="180" min="30"
                                            max="7200"
                                            class="campaign-track-duration w-16 p-2.5 border border-gray-300 rounded-lg text-sm text-center focus:ring-2 focus:ring-emerald-500"
                                            title="Duration in seconds">
                                        <select class="campaign-track-type w-20 p-2.5 border border-gray-300 rounded-lg text-xs focus:ring-2 focus:ring-emerald-500 bg-white" title="Track type">
                                            <option value="playlist">Playlist</option>
                                            <option value="channel_video">Channel</option>
                                            <option value="similar_video">Similar</option>
                                        </select>
                                    </div>
                                </div>
                                <p class="text-xs text-gray-400 mt-2">Duration = seconds per track. Track type: Playlist (normal), Channel (from your channel), Similar (other creators)</p>
                            </div>

                            <button type="button" id="createCampaignBtn"
                                class="w-full bg-gradient-to-r from-emerald-600 to-green-500 hover:from-emerald-700 hover:to-green-600 text-white font-semibold py-3 px-6 rounded-xl smooth-transition flex items-center justify-center shadow-lg">
                                <i class="fas fa-rocket mr-3"></i> Create Campaign
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Campaign List + Deploy -->
                <div class="lg:col-span-2">
                    <div class="bg-white rounded-2xl shadow-lg overflow-hidden border border-gray-100">
                        <div class="bg-gradient-to-r from-violet-600 to-purple-500 px-6 py-4">
                            <h2 class="text-lg font-bold text-white flex items-center">
                                <i class="fas fa-layer-group mr-3"></i> Your Campaigns
                                <span
                                    class="ml-auto bg-white/20 text-white text-sm font-medium px-3 py-1 rounded-full">{{ $campaigns->count() ?? 0 }}</span>
                            </h2>
                        </div>
                        <div class="p-4">
                            @if (isset($campaigns) && $campaigns->count() > 0)
                                <div class="space-y-4 max-h-[600px] overflow-y-auto pr-1">
                                    @foreach ($campaigns as $campaign)
                                        <div class="p-4 border border-gray-200 rounded-xl smooth-transition hover:shadow-md"
                                            data-campaign-id="{{ $campaign->id }}">
                                            <div class="flex items-start justify-between mb-3">
                                                <div>
                                                    <div class="flex items-center">
                                                        <i
                                                            class="fab fa-{{ $campaign->platform === 'apple_music' ? 'apple' : $campaign->platform }} {{ $campaign->platform === 'spotify' ? 'text-green-500' : ($campaign->platform === 'apple_music' ? 'text-red-600' : 'text-red-500') }} text-lg mr-2"></i>
                                                        <h3 class="font-bold text-gray-900">{{ $campaign->name }}</h3>
                                                    </div>
                                                    <p class="text-xs text-gray-400 mt-1">
                                                        {{ $campaign->tracks->count() }}
                                                        tracks &bull; {{ $campaign->assignments_count ?? 0 }}
                                                        assignments
                                                        @if ($campaign->interstitial_every)
                                                            &bull; <span class="text-amber-600 font-medium">Interstitial every {{ $campaign->interstitial_every }} tracks</span>
                                                        @endif
                                                        @if ($campaign->channel_url)
                                                            &bull; <span class="text-red-500"><i class="fab fa-youtube mr-1"></i>Channel</span>
                                                        @endif
                                                    </p>
                                                </div>
                                                <div class="flex space-x-2">
                                                    <button
                                                         class="edit-campaign px-3 py-1.5 bg-amber-50 text-amber-600 text-xs rounded-lg hover:bg-amber-100 smooth-transition font-medium"
                                                         data-campaign-id="{{ $campaign->id }}"
                                                         data-campaign-name="{{ $campaign->name }}"
                                                         data-campaign-platform="{{ $campaign->platform }}"
                                                         data-channel-url="{{ $campaign->channel_url ?? '' }}"
                                                         data-interstitial-every="{{ $campaign->interstitial_every ?? '' }}"
                                                         data-interstitial-media-url="{{ $campaign->interstitial_media_url ?? '' }}"
                                                         data-interstitial-duration="{{ $campaign->interstitial_duration_seconds ?? 120 }}"
                                                         data-tracks="{{ json_encode($campaign->tracks) }}">
                                                         <i class="fas fa-edit mr-1"></i>Edit
                                                     </button>
                                                    <button
                                                        class="deploy-campaign px-3 py-1.5 bg-gradient-to-r from-emerald-500 to-green-500 text-white text-xs rounded-lg hover:from-emerald-600 hover:to-green-600 smooth-transition font-medium"
                                                        data-campaign-id="{{ $campaign->id }}"
                                                        data-campaign-name="{{ $campaign->name }}">
                                                        <i class="fas fa-rocket mr-1"></i>Deploy
                                                    </button>
                                                    <button
                                                        class="delete-campaign px-3 py-1.5 bg-red-50 text-red-600 text-xs rounded-lg hover:bg-red-100 smooth-transition font-medium"
                                                        data-campaign-id="{{ $campaign->id }}">
                                                        <i class="fas fa-trash-alt mr-1"></i>Delete
                                                    </button>
                                                </div>
                                            </div>
                                            <!-- Track List -->
                                            <div class="bg-gray-50 rounded-lg p-3">
                                                <div class="space-y-1.5">
                                                    @foreach ($campaign->tracks as $track)
                                                        <div class="flex items-center text-sm">
                                                            <span
                                                                class="text-xs font-bold text-gray-400 w-5">{{ $loop->iteration }}</span>
                                                            @if ($track->track_type === 'channel_video')
                                                                <i class="fab fa-youtube text-red-400 text-xs mr-2"></i>
                                                            @elseif ($track->track_type === 'similar_video')
                                                                <i class="fas fa-users text-purple-400 text-xs mr-2"></i>
                                                            @else
                                                                <i class="fas fa-music text-gray-300 text-xs mr-2"></i>
                                                            @endif
                                                            <span
                                                                class="text-gray-700 flex-1 truncate">{{ $track->media_title ?? Str::limit($track->media_url, 40) }}</span>
                                                            @if ($track->track_type !== 'playlist')
                                                                <span class="text-xs {{ $track->track_type === 'channel_video' ? 'text-red-400' : 'text-purple-400' }} mr-1 font-medium">{{ $track->track_type === 'channel_video' ? 'CH' : 'SIM' }}</span>
                                                            @endif
                                                            <span
                                                                class="text-xs text-gray-400 ml-2">{{ gmdate('i:s', $track->duration_seconds) }}</span>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <div class="text-center py-12">
                                    <div
                                        class="h-16 w-16 rounded-full bg-purple-100 flex items-center justify-center mx-auto mb-4">
                                        <i class="fas fa-layer-group text-purple-400 text-2xl"></i>
                                    </div>
                                    <p class="text-sm text-gray-500 font-medium">No campaigns yet</p>
                                    <p class="text-xs text-gray-400 mt-1">Create your first campaign to start
                                        auto-looping
                                        tracks on your devices</p>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- TAB: Broadcast All (existing functionality) -->
        <div id="tab-broadcast" class="tab-content hidden">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                <div class="bg-white rounded-2xl shadow-lg overflow-hidden border border-gray-100">
                    <div class="bg-gradient-to-r from-primary-600 to-blue-500 px-6 py-4">
                        <h2 class="text-xl font-bold text-white flex items-center"><i
                                class="fas fa-broadcast-tower mr-3"></i> Broadcast Command</h2>
                        <p class="text-primary-100 text-sm">Send the same action to all connected devices at once</p>
                    </div>
                    <div class="p-6">
                        <form action="{{ route('send.command') }}" method="POST">
                            @csrf
                            <div class="grid grid-cols-3 gap-3 mb-4">
                                <label
                                    class="bcast-platform-btn flex items-center p-3 border-2 rounded-xl cursor-pointer smooth-transition border-primary-500 bg-primary-50"
                                    data-platform="spotify">
                                    <input type="radio" name="platform" value="spotify" class="hidden" checked>
                                    <i class="fab fa-spotify text-green-500 mr-2"></i><span
                                        class="font-medium">Spotify</span>
                                </label>
                                <label
                                    class="bcast-platform-btn flex items-center p-3 border-2 rounded-xl cursor-pointer smooth-transition border-gray-200"
                                    data-platform="apple_music">
                                    <input type="radio" name="platform" value="apple_music" class="hidden">
                                    <i class="fab fa-apple text-red-600 mr-2"></i><span
                                        class="font-medium">Apple Music</span>
                                </label>
                                <label
                                    class="bcast-platform-btn flex items-center p-3 border-2 rounded-xl cursor-pointer smooth-transition border-gray-200"
                                    data-platform="youtube">
                                    <input type="radio" name="platform" value="youtube" class="hidden">
                                    <i class="fab fa-youtube text-red-500 mr-2"></i><span
                                        class="font-medium">YouTube</span>
                                </label>
                            </div>
                            <div class="grid grid-cols-2 gap-3 mb-4">
                                <label
                                    class="flex items-center p-3 border rounded-xl cursor-pointer smooth-transition hover:border-primary-300"><input
                                        type="radio" name="action" value="play"
                                        class="h-4 w-4 text-primary-600" checked><span
                                        class="ml-2 text-sm font-medium">Play</span></label>
                                <label
                                    class="flex items-center p-3 border rounded-xl cursor-pointer smooth-transition hover:border-primary-300"><input
                                        type="radio" name="action" value="pause"
                                        class="h-4 w-4 text-primary-600"><span
                                        class="ml-2 text-sm font-medium">Pause</span></label>
                            </div>
                            <div id="bcast-spotify-group" class="mb-4">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Spotify URI</label>
                                <input type="text" name="spotify_uri" value="spotify:track:4cOdK2wGLETKBW3PvgPWqT"
                                    class="w-full p-4 border border-gray-300 rounded-xl focus:ring-2 focus:ring-primary-500">
                            </div>
                            <div id="bcast-apple_music-group" class="hidden mb-4">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Apple Music URL</label>
                                <input type="text" name="apple_music_url"
                                    placeholder="https://music.apple.com/..."
                                    class="w-full p-4 border border-gray-300 rounded-xl focus:ring-2 focus:ring-red-500">
                            </div>
                            <div id="bcast-youtube-group" class="hidden mb-4">
                                <label class="block text-sm font-medium text-gray-700 mb-2">YouTube URL</label>
                                <input type="text" name="youtube_url"
                                    placeholder="https://www.youtube.com/watch?v=..."
                                    class="w-full p-4 border border-gray-300 rounded-xl focus:ring-2 focus:ring-red-500">
                            </div>
                            <button type="submit"
                                class="w-full bg-gradient-to-r from-primary-600 to-blue-500 hover:from-primary-700 hover:to-blue-600 text-white font-semibold py-4 px-6 rounded-xl smooth-transition flex items-center justify-center shadow-lg">
                                <i class="fas fa-satellite mr-3"></i> Broadcast to All Online Devices
                            </button>
                        </form>
                    </div>
                </div>
                <div
                    class="bg-white rounded-2xl shadow-lg p-8 border border-gray-100 flex flex-col items-center justify-center text-center">
                    <div
                        class="h-20 w-20 rounded-full bg-gradient-to-r from-primary-100 to-blue-100 flex items-center justify-center mb-4">
                        <i class="fas fa-broadcast-tower text-primary-600 text-3xl"></i>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-2">Broadcast Mode</h3>
                    <p class="text-gray-500 max-w-sm">This sends the <strong>same command to ALL online
                            devices</strong>. For assigning different tracks to different devices, use the
                        <strong>Assign Tasks</strong> tab instead.
                    </p>
                </div>
            </div>
        </div>

        <!-- TAB: Activity Logs -->
        <div id="tab-logs" class="tab-content hidden">
            @include('_partials.logs', ['recentLogs' => $recentLogs])
        </div>
    </main>

    @include('_partials.modals', ['devices' => $devices])
    @include('_partials.scripts')
</body>

</html>
