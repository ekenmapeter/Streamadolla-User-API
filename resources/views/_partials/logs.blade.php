<!-- Activity Logs Panel -->
<div class="bg-white rounded-2xl shadow-lg overflow-hidden border border-gray-100">
    <div class="bg-gradient-to-r from-slate-800 to-gray-900 px-6 py-4 flex items-center justify-between">
        <div>
            <h2 class="text-xl font-bold text-white flex items-center"><i class="fas fa-scroll mr-3"></i> Device Activity Log</h2>
            <p class="text-gray-400 text-sm">Last 24 hours &bull; {{ $recentLogs->count() ?? 0 }} entries</p>
        </div>
        <div class="flex items-center space-x-3">
            <button class="log-filter px-3 py-1.5 rounded-lg text-xs font-medium bg-white/20 text-white hover:bg-white/30 smooth-transition" data-level="all">All</button>
            <button class="log-filter px-3 py-1.5 rounded-lg text-xs font-medium bg-blue-500/30 text-blue-200 hover:bg-blue-500/50 smooth-transition" data-level="info">Info</button>
            <button class="log-filter px-3 py-1.5 rounded-lg text-xs font-medium bg-amber-500/30 text-amber-200 hover:bg-amber-500/50 smooth-transition" data-level="warning">Warning</button>
            <button class="log-filter px-3 py-1.5 rounded-lg text-xs font-medium bg-red-500/30 text-red-200 hover:bg-red-500/50 smooth-transition" data-level="error">Errors</button>
            <div class="h-6 w-px bg-slate-700 mx-1"></div>
            <form action="{{ route('logs.clear') }}" method="POST" class="inline m-0" onsubmit="return confirm('Are you sure you want to clear all logs? This action cannot be undone.');">
                @csrf
                <button type="submit" class="px-3 py-1.5 rounded-lg text-xs font-medium bg-rose-500 text-white hover:bg-rose-400 smooth-transition shadow-sm">
                    <i class="fas fa-trash-alt mr-1"></i>Clear Logs
                </button>
            </form>
        </div>
    </div>
    <div class="p-4">
        @if($recentLogs && $recentLogs->count() > 0)
        <div class="overflow-x-auto max-h-[500px] overflow-y-auto">
            <table class="w-full text-sm">
                <thead class="sticky top-0 bg-gray-50">
                    <tr class="text-left text-gray-500 border-b border-gray-200">
                        <th class="py-3 px-3 font-medium">Level</th>
                        <th class="py-3 px-3 font-medium">Device</th>
                        <th class="py-3 px-3 font-medium">Event</th>
                        <th class="py-3 px-3 font-medium">Message</th>
                        <th class="py-3 px-3 font-medium">Time</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($recentLogs as $log)
                    @php
                        $levelConfig = [
                            'info'     => ['badge' => 'bg-blue-100 text-blue-700',   'icon' => 'fa-info-circle text-blue-500'],
                            'warning'  => ['badge' => 'bg-amber-100 text-amber-700', 'icon' => 'fa-exclamation-triangle text-amber-500'],
                            'error'    => ['badge' => 'bg-red-100 text-red-700',     'icon' => 'fa-times-circle text-red-500'],
                            'critical' => ['badge' => 'bg-red-200 text-red-900',     'icon' => 'fa-skull-crossbones text-red-700'],
                        ];
                        $lc = $levelConfig[$log->level] ?? $levelConfig['info'];
                    @endphp
                    <tr class="log-row border-b border-gray-100 hover:bg-gray-50 smooth-transition {{ $log->stack_trace ? 'cursor-pointer' : '' }}"
                        data-level="{{ $log->level }}"
                        @if($log->stack_trace) onclick="this.nextElementSibling.classList.toggle('hidden')" @endif>
                        <td class="py-3 px-3">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $lc['badge'] }}">
                                <i class="fas {{ $lc['icon'] }} mr-1.5 text-xs"></i>{{ ucfirst($log->level) }}
                            </span>
                        </td>
                        <td class="py-3 px-3">
                            <span class="text-gray-900 font-medium">{{ $log->device->name ?? 'Unknown' }}</span><br>
                            <span class="text-xs text-gray-400 font-mono">{{ Str::limit($log->device_id, 12) }}</span>
                        </td>
                        <td class="py-3 px-3"><code class="bg-gray-100 text-gray-700 px-2 py-0.5 rounded text-xs font-mono">{{ $log->event }}</code></td>
                        <td class="py-3 px-3 text-gray-600 max-w-md truncate">{{ Str::limit($log->message, 80) }}@if($log->stack_trace)<i class="fas fa-chevron-down text-xs text-gray-400 ml-1"></i>@endif</td>
                        <td class="py-3 px-3 text-gray-500 text-xs whitespace-nowrap">{{ $log->created_at->format('H:i:s') }}<br><span class="text-gray-400">{{ $log->created_at->diffForHumans() }}</span></td>
                    </tr>
                    @if($log->stack_trace)
                    <tr class="hidden">
                        <td colspan="5" class="p-4 bg-gray-900">
                            <div class="rounded-lg overflow-hidden">
                                <div class="flex items-center justify-between px-4 py-2 bg-gray-800">
                                    <span class="text-xs text-gray-400 font-medium"><i class="fas fa-code mr-2"></i>Stack Trace</span>
                                    <button class="text-xs text-gray-400 hover:text-white" onclick="navigator.clipboard.writeText(this.closest('td').querySelector('pre').textContent); this.textContent='Copied!'"><i class="far fa-copy mr-1"></i>Copy</button>
                                </div>
                                <pre class="p-4 text-xs text-green-400 font-mono overflow-x-auto max-h-48 overflow-y-auto leading-relaxed">{{ $log->stack_trace }}</pre>
                            </div>
                        </td>
                    </tr>
                    @endif
                    @endforeach
                </tbody>
            </table>
        </div>
        @else
        <div class="text-center py-16">
            <div class="h-16 w-16 rounded-full bg-gray-100 flex items-center justify-center mx-auto mb-4"><i class="fas fa-check-circle text-green-500 text-2xl"></i></div>
            <h4 class="text-lg font-medium text-gray-900 mb-2">No activity yet</h4>
            <p class="text-gray-500">Device logs will appear here once your Flutter apps start sending data.</p>
        </div>
        @endif
    </div>
</div>
