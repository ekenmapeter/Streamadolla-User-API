<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Device Assignments | Stream Farm</title>
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
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            font-family: 'Outfit', sans-serif;
        }

        .glass-card {
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.3);
        }

        .premium-shadow {
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.05), 0 10px 10px -5px rgba(0, 0, 0, 0.02);
        }

        .timer-glow {
            text-shadow: 0 0 15px rgba(59, 130, 246, 0.5);
        }

        @keyframes pulse-ring {
            0% { transform: scale(0.8); opacity: 0.5; }
            100% { transform: scale(1.2); opacity: 0; }
        }

        .pulse-ring::before {
            content: '';
            position: absolute;
            width: 100%;
            height: 100%;
            border-radius: inherit;
            border: 2px solid currentColor;
            animation: pulse-ring 2s infinite;
        }

        .assignment-row:hover {
            transform: scale(1.005);
            background: rgba(249, 250, 251, 1);
        }

        .progress-bar {
            transition: width 1s linear;
        }
    </style>
</head>
<body class="bg-[#F8FAFC] text-slate-800 min-h-screen">

    <!-- Navigation -->
    <nav class="glass-card sticky top-0 z-50 border-b border-slate-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-20">
                <div class="flex items-center space-x-4">
                    <a href="{{ route('dashboard') }}" class="h-12 w-12 rounded-2xl bg-primary-600 flex items-center justify-center text-white shadow-lg shadow-primary-200 hover:scale-110 transition-transform">
                        <i class="fas fa-arrow-left text-xl"></i>
                    </a>
                    <div>
                        <h1 class="text-2xl font-bold tracking-tight text-slate-900">Device Assignments</h1>
                        <p class="text-sm text-slate-500 font-medium">Monitoring track deployments & status</p>
                    </div>
                </div>

                <div class="hidden md:flex items-center space-x-8">
                    <a href="{{ route('dashboard') }}" class="text-sm font-medium text-slate-500 hover:text-primary-600 transition-colors">
                        <i class="fas fa-chart-line mr-1.5"></i>Dashboard
                    </a>
                    <a href="{{ route('assignments.index') }}" class="text-sm font-bold text-primary-600">
                        <i class="fas fa-list-check mr-1.5"></i>Assignments
                    </a>
                </div>

                </div>
            </div>
        </div>
    </nav>

    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
        @if (session('success'))
            <div class="mb-6 p-4 bg-emerald-50 border border-emerald-200 rounded-2xl flex items-center justify-between">
                <div class="flex items-center">
                    <div class="h-10 w-10 rounded-full bg-emerald-100 flex items-center justify-center mr-4"><i class="fas fa-check-circle text-emerald-600"></i></div>
                    <div>
                        <p class="font-bold text-emerald-800">{{ session('success') }}</p>
                    </div>
                </div>
                <button onclick="this.parentElement.remove()" class="text-emerald-800 hover:text-emerald-900"><i class="fas fa-times"></i></button>
            </div>
        @endif

        <!-- Control Header -->
        <div class="flex flex-col md:flex-row md:items-center justify-between mb-10 gap-6">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 flex-1">
                <div class="bg-white p-5 rounded-3xl border border-slate-100 premium-shadow">
                    <p class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">Total Tasks</p>
                    <p class="text-2xl font-black text-slate-900">{{ $assignments->count() }}</p>
                </div>
                <div class="bg-white p-5 rounded-3xl border border-slate-100 premium-shadow">
                    <p class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">Active</p>
                    <p class="text-2xl font-black text-emerald-600">{{ $assignments->where('status', 'playing')->count() }}</p>
                </div>
                <div class="bg-white p-5 rounded-3xl border border-slate-100 premium-shadow">
                    <p class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">Pending</p>
                    <p class="text-2xl font-black text-amber-500">{{ $assignments->where('status', 'pending')->count() }}</p>
                </div>
                <div class="bg-white p-5 rounded-3xl border border-slate-100 premium-shadow">
                    <p class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">Errors</p>
                    <p class="text-2xl font-black text-rose-500">{{ $assignments->where('status', 'failed')->count() }}</p>
                </div>
            </div>
            
            <div class="flex items-center">
                <form action="{{ route('assignments.clear') }}" method="POST" onsubmit="return confirm('Are you sure you want to clear all assignments? Devices streaming will be set to online.');">
                    @csrf
                    <button type="submit" class="px-6 py-4 bg-rose-50 hover:bg-rose-100 text-rose-600 rounded-2xl font-bold transition-colors border border-rose-200 shadow-sm">
                        <i class="fas fa-trash-alt mr-2"></i>Clear All
                    </button>
                </form>
            </div>
        </div>

        <!-- Assignments Table -->
        <div class="bg-white rounded-[2.5rem] border border-slate-100 premium-shadow overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="border-b border-slate-100 bg-slate-50/50">
                            <th class="px-8 py-6 text-xs font-bold text-slate-500 uppercase tracking-widest">Device</th>
                            <th class="px-8 py-6 text-xs font-bold text-slate-500 uppercase tracking-widest">Content</th>
                            <th class="px-8 py-6 text-xs font-bold text-slate-500 uppercase tracking-widest">Platform</th>
                            <th class="px-8 py-6 text-xs font-bold text-slate-500 uppercase tracking-widest">Status</th>
                            <th class="px-8 py-6 text-xs font-bold text-slate-500 uppercase tracking-widest text-right">Assigned / Time</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($assignments as $assignment)
                            <tr class="assignment-row transition-all duration-200" id="row-{{ $assignment->id }}">
                                <td class="px-8 py-6">
                                    <div class="flex items-center space-x-4">
                                        <div class="h-12 w-12 rounded-2xl bg-slate-100 flex items-center justify-center text-slate-500">
                                            <i class="fas fa-mobile-screen text-xl"></i>
                                        </div>
                                        <div>
                                            <p class="font-bold text-slate-900">{{ $assignment->device->name ?? 'Unknown Device' }}</p>
                                            <p class="text-xs text-slate-400 font-mono">{{ $assignment->device->device_id ?? 'N/A' }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-8 py-6">
                                    <div class="max-w-xs">
                                        <p class="font-bold text-slate-900 truncate" title="{{ $assignment->media_title }}">
                                            {{ $assignment->media_title ?? 'Untitled Media' }}
                                        </p>
                                        <p class="text-xs text-slate-400 truncate font-mono mb-3">
                                            {{ $assignment->media_url }}
                                        </p>

                                        @if($assignment->campaign && $assignment->campaignTrack)
                                            @php
                                                $allTracks = $assignment->campaign->tracks->sortBy('position_order')->values();
                                                $assignedTracks = collect();
                                                for ($i = $assignment->subset_start_index; $i <= $assignment->subset_end_index; $i++) {
                                                    if (isset($allTracks[$i])) $assignedTracks->push($allTracks[$i]);
                                                }
                                            @endphp
                                            
                                            <div class="bg-slate-50 rounded-lg p-2.5 border border-slate-100">
                                                <div class="space-y-1.5">
                                                    @foreach($assignedTracks as $idx => $track)
                                                        @php
                                                            $isPlaying = $track->id === $assignment->campaign_track_id;
                                                        @endphp
                                                        <div class="flex items-center text-sm {{ $isPlaying ? 'bg-white rounded-md shadow-sm border border-slate-100 px-2 -mx-2 py-1.5' : '' }}">
                                                            <span class="text-xs font-bold {{ $isPlaying ? 'text-primary-500' : 'text-slate-400' }} w-5">{{ $idx + 1 }}</span>
                                                            @if($isPlaying)
                                                                <i class="fas fa-volume-up text-primary-500 text-[10px] mr-2"></i>
                                                            @else
                                                                <i class="fas fa-music text-slate-300 text-[10px] mr-2"></i>
                                                            @endif
                                                            <span class="flex-1 truncate {{ $isPlaying ? 'text-primary-700 font-bold' : 'text-slate-600' }}" title="{{ $track->media_title ?? $track->media_url }}">
                                                                {{ $track->media_title ?? \Illuminate\Support\Str::limit($track->media_url, 30) }}
                                                            </span>
                                                            <span class="text-[10px] {{ $isPlaying ? 'text-primary-400 font-bold' : 'text-slate-400' }} ml-2 font-mono">{{ gmdate('i:s', $track->duration_seconds) }}</span>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            </div>
                                        @else
                                            <p class="font-bold text-slate-900 truncate" title="{{ $assignment->media_title }}">
                                                {{ $assignment->media_title ?? 'Untitled Media' }}
                                            </p>
                                            <p class="text-xs text-slate-400 truncate font-mono">
                                                {{ $assignment->media_url }}
                                            </p>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-8 py-6">
                                    <div class="inline-flex items-center space-x-2 px-3 py-1.5 rounded-xl bg-slate-100">
                                        <i class="fab fa-{{ $assignment->platform }} {{ $assignment->platform === 'spotify' ? 'text-emerald-500' : 'text-rose-500' }}"></i>
                                        <span class="text-xs font-bold capitalize text-slate-700">{{ $assignment->platform }}</span>
                                    </div>
                                </td>
                                <td class="px-8 py-6">
                                    @php
                                        $colors = [
                                            'pending'   => 'bg-amber-100 text-amber-700 border-amber-200',
                                            'playing'   => 'bg-emerald-100 text-emerald-700 border-emerald-200',
                                            'paused'    => 'bg-blue-100 text-blue-700 border-blue-200',
                                            'stopped'   => 'bg-slate-100 text-slate-700 border-slate-200',
                                            'failed'    => 'bg-rose-100 text-rose-700 border-rose-200',
                                            'completed' => 'bg-indigo-100 text-indigo-700 border-indigo-200',
                                        ];
                                        $colorClass = $colors[$assignment->status] ?? 'bg-slate-100 text-slate-700 border-slate-200';
                                    @endphp
                                    <div class="inline-flex items-center px-4 py-1.5 rounded-full border {{ $colorClass }} text-xs font-black uppercase tracking-widest">
                                        {{ $assignment->status }}
                                    </div>
                                </td>
                                <td class="px-8 py-6 text-right">
                                    <p class="text-sm font-bold text-slate-900">{{ $assignment->created_at->format('H:i:s') }}</p>
                                    
                                    @php
                                        $duration = $assignment->campaignTrack ? $assignment->campaignTrack->duration_seconds : 180;
                                        $timeRemaining = 0;
                                        $isTracking = false;
                                        if ($assignment->status === 'playing' && $assignment->started_at) {
                                            $elapsed = now()->diffInSeconds($assignment->started_at);
                                            $timeRemaining = max(0, $duration - $elapsed);
                                            $isTracking = true;
                                        }
                                    @endphp

                                    @if($isTracking)
                                        <div class="mt-2 text-xs font-bold text-primary-600 bg-primary-50 px-2 py-1 rounded-md inline-block track-timer" 
                                             data-assignment-id="{{ $assignment->id }}" 
                                             data-remaining="{{ $timeRemaining }}">
                                            <i class="fas fa-clock mr-1"></i> <span class="time-display">{{ gmdate('i:s', $timeRemaining) }}</span>
                                        </div>
                                    @elseif($assignment->status === 'pending' || $assignment->status === 'paused')
                                        <div class="mt-2 text-xs font-bold text-slate-500 bg-slate-100 px-2 py-1 rounded-md inline-block">
                                            <i class="fas fa-clock mr-1"></i> {{ gmdate('i:s', $duration) }}
                                        </div>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-8 py-20 text-center">
                                    <div class="max-w-xs mx-auto">
                                        <div class="h-20 w-20 rounded-3xl bg-slate-50 flex items-center justify-center text-slate-200 mx-auto mb-6">
                                            <i class="fas fa-clipboard-list text-4xl"></i>
                                        </div>
                                        <p class="text-lg font-bold text-slate-900">No Assignments Found</p>
                                        <p class="text-sm text-slate-400">Start by assigning a track to a device from the dashboard.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <script>
        // Individual Track Timers
        document.addEventListener('DOMContentLoaded', () => {
            const timers = document.querySelectorAll('.track-timer');
            
            timers.forEach(timer => {
                let remaining = parseInt(timer.getAttribute('data-remaining'), 10);
                const assignmentId = timer.getAttribute('data-assignment-id');
                const display = timer.querySelector('.time-display');
                const row = document.getElementById(`row-${assignmentId}`);
                
                if (remaining > 0) {
                    const interval = setInterval(() => {
                        // Dynamically read remaining so AJAX can sync it
                        let currentRemaining = parseInt(timer.getAttribute('data-remaining'), 10);
                        currentRemaining--;
                        timer.setAttribute('data-remaining', currentRemaining);
                        
                        if (currentRemaining <= 0) {
                            clearInterval(interval);
                            display.innerText = '00:00';
                            
                            // Visual feedback
                            timer.classList.remove('bg-primary-50', 'text-primary-600');
                            timer.classList.add('bg-amber-50', 'text-amber-600');
                            
                            // Fetch next track API
                            fetch(`/api/assignments/${assignmentId}/next`, {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'Accept': 'application/json',
                                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                                }
                            })
                            .then(res => res.json())
                            .then(data => {
                                if(data.success) {
                                    if(row) {
                                        row.style.opacity = '0';
                                        setTimeout(() => { window.location.reload(); }, 500);
                                    } else {
                                        window.location.reload();
                                    }
                                }
                            })
                            .catch(err => console.error('Next track error:', err));
                        } else {
                            const m = Math.floor(currentRemaining / 60).toString().padStart(2, '0');
                            const s = (currentRemaining % 60).toString().padStart(2, '0');
                            display.innerText = `${m}:${s}`;
                        }
                    }, 1000);
                }
            });

            // AJAX Real-time Synchronization
            function syncTimers() {
                fetch('{{ route('assignments.stats') }}')
                    .then(r => r.json())
                    .then(data => {
                        if (data.success) {
                            data.assignments.forEach(assignment => {
                                const timerEl = document.querySelector(`.track-timer[data-assignment-id="${assignment.id}"]`);
                                if (timerEl) {
                                    const domRemaining = parseInt(timerEl.getAttribute('data-remaining') || 0, 10);
                                    // Snap to server time if desynced by more than 2 seconds
                                    if (Math.abs(domRemaining - assignment.remaining) > 2) {
                                        timerEl.setAttribute('data-remaining', assignment.remaining);
                                    }
                                }
                            });
                        }
                    })
                    .catch(err => console.error('Error syncing timers:', err));
            }

            // Sync every 5 seconds
            setInterval(syncTimers, 5000);
            // Quick sync on load
            setTimeout(syncTimers, 500);
        });
    </script>
</body>
</html>
