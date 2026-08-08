@extends('admin.layout')

@section('header', 'API Documentation')
@section('subtitle', 'Complete reference for the fleet API and the AudioReach listener API')

@section('content')
    @php
        $m = fn (string $method, string $color) => "<span class=\"inline-block w-14 text-center text-[10px] font-bold px-2 py-1 rounded-md {$color}\">{$method}</span>";
        $endpoint = fn (string $route, string $desc, array $extra = []) => [
            'route' => $route,
            'desc' => $desc,
            ...$extra,
        ];
    @endphp

    {{-- ── Overview ─────────────────────────────────────────────────────── --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-10">
        <div class="bg-gray-900 border border-white/10 rounded-2xl p-6">
            <div class="flex items-center gap-3 mb-3">
                <div class="h-10 w-10 rounded-xl bg-blue-500/20 text-blue-400 flex items-center justify-center"><i class="fas fa-satellite-dish"></i></div>
                <div>
                    <h3 class="font-bold">Fleet Zone</h3>
                    <p class="text-xs text-gray-500">Device automation API</p>
                </div>
            </div>
            <p class="text-sm text-gray-400 leading-relaxed">Machine-to-machine API used by the Android device fleet. Every request must send <code class="text-blue-300 bg-blue-500/10 px-1.5 py-0.5 rounded text-xs">X-API-Key</code> in the header. Unauthorized requests return <code class="text-red-300 bg-red-500/10 px-1.5 py-0.5 rounded text-xs">401</code>.</p>
            <div class="mt-4 bg-white/5 rounded-xl p-3 font-mono text-xs text-gray-300">
                <p class="text-gray-500 mb-1">// Headers for every fleet request</p>
                <p>X-API-Key: <span class="text-purple-300">your_fleet_api_key</span></p>
                <p>Content-Type: application/json</p>
            </div>
            <p class="mt-3 text-xs text-gray-500">Base URL: <code class="text-gray-300">https://your-domain.com/api</code></p>
        </div>

        <div class="bg-gray-900 border border-white/10 rounded-2xl p-6">
            <div class="flex items-center gap-3 mb-3">
                <div class="h-10 w-10 rounded-xl bg-fuchsia-500/20 text-fuchsia-400 flex items-center justify-center"><i class="fas fa-headphones"></i></div>
                <div>
                    <h3 class="font-bold">AudioReach v1</h3>
                    <p class="text-xs text-gray-500">Listener mobile app API</p>
                </div>
            </div>
            <p class="text-sm text-gray-400 leading-relaxed">REST API for the listener app, protected with <b class="text-gray-300">Laravel Sanctum</b> tokens. Register → verify email → receive token → send it as <code class="text-blue-300 bg-blue-500/10 px-1.5 py-0.5 rounded text-xs">Authorization: Bearer &lt;token&gt;</code>. Listener-only routes return <code class="text-red-300 bg-red-500/10 px-1.5 py-0.5 rounded text-xs">403</code> for other roles.</p>
            <div class="mt-4 bg-white/5 rounded-xl p-3 font-mono text-xs text-gray-300">
                <p class="text-gray-500 mb-1">// Auth header for authenticated routes</p>
                <p>Authorization: Bearer <span class="text-purple-300">1|xxxxxxxxxxxx</span></p>
                <p>X-App-Version: 1.0.0 <span class="text-gray-600">// optional, used for version checks</span></p>
            </div>
            <p class="mt-3 text-xs text-gray-500">Base URL: <code class="text-gray-300">https://your-domain.com/api/v1</code></p>
        </div>
    </div>

    {{-- ── Fleet Zone endpoints ─────────────────────────────────────────── --}}
    <h2 class="text-xl font-bold mb-1 flex items-center"><i class="fas fa-satellite-dish mr-3 text-blue-400"></i>Fleet Zone — Device Automation</h2>
    <p class="text-sm text-gray-500 mb-6">Secured with <code class="text-blue-300">X-API-Key</code> · Base: <code class="text-gray-400">/api</code></p>

    <div class="space-y-8 mb-12">
        <div class="bg-gray-900 border border-white/10 rounded-2xl overflow-hidden">
            <div class="px-6 py-4 border-b border-white/10 font-bold"><i class="fas fa-mobile-screen mr-2 text-blue-400"></i>Devices</div>
            <div class="divide-y divide-white/5 text-sm">
                @foreach ([
                    ['GET', '/devices', 'List all registered devices with their current assignment'],
                    ['POST', '/devices/register', 'Register a device (name, device_id, platform, fcm_token)'],
                    ['POST', '/devices/heartbeat/{device}', 'Report device status / last_seen heartbeat'],
                    ['PUT', '/devices/{device}', 'Update device details (name, proxy_url, etc.)'],
                    ['DELETE', '/devices/{device}', 'Remove a device from the fleet'],
                ] as [$method, $route, $desc])
                    <div class="px-6 py-3.5 flex items-center gap-4">
                        {!! $m($method, $method === 'GET' ? 'bg-green-500/15 text-green-400' : ($method === 'POST' ? 'bg-blue-500/15 text-blue-400' : ($method === 'PUT' ? 'bg-amber-500/15 text-amber-400' : 'bg-red-500/15 text-red-400'))) !!}
                        <code class="text-gray-300 font-mono w-52 shrink-0">{{ $route }}</code>
                        <p class="text-gray-500 flex-1">{{ $desc }}</p>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="bg-gray-900 border border-white/10 rounded-2xl overflow-hidden">
            <div class="px-6 py-4 border-b border-white/10 font-bold"><i class="fas fa-scroll mr-2 text-blue-400"></i>Device Logs</div>
            <div class="divide-y divide-white/5 text-sm">
                @foreach ([
                    ['POST', '/devices/log', 'Store a single device log entry'],
                    ['POST', '/devices/logs/batch', 'Store multiple log entries in one request (array of entries)'],
                    ['GET', '/devices/logs', 'List device logs (query: device_id, level, limit)'],
                ] as [$method, $route, $desc])
                    <div class="px-6 py-3.5 flex items-center gap-4">
                        {!! $m($method, $method === 'GET' ? 'bg-green-500/15 text-green-400' : 'bg-blue-500/15 text-blue-400') !!}
                        <code class="text-gray-300 font-mono w-52 shrink-0">{{ $route }}</code>
                        <p class="text-gray-500 flex-1">{{ $desc }}</p>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="bg-gray-900 border border-white/10 rounded-2xl overflow-hidden">
            <div class="px-6 py-4 border-b border-white/10 font-bold"><i class="fas fa-broadcast-tower mr-2 text-blue-400"></i>Commands</div>
            <div class="divide-y divide-white/5 text-sm">
                @foreach ([
                    ['POST', '/commands/send-to-all', 'Broadcast a command (play/pause/stop/open) to all devices'],
                    ['POST', '/commands/send-to-device/{device}', 'Send a command to a single device'],
                    ['POST', '/commands/send-to-group', 'Send a command to a group of devices (device_ids[])'],
                ] as [$method, $route, $desc])
                    <div class="px-6 py-3.5 flex items-center gap-4">
                        {!! $m($method, 'bg-blue-500/15 text-blue-400') !!}
                        <code class="text-gray-300 font-mono w-52 shrink-0">{{ $route }}</code>
                        <p class="text-gray-500 flex-1">{{ $desc }}</p>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="bg-gray-900 border border-white/10 rounded-2xl overflow-hidden">
            <div class="px-6 py-4 border-b border-white/10 font-bold"><i class="fas fa-list-check mr-2 text-blue-400"></i>Assignments</div>
            <div class="divide-y divide-white/5 text-sm">
                @foreach ([
                    ['GET', '/assignments', 'List all track assignments'],
                    ['POST', '/assignments', 'Assign a track to devices (platform, media_url, device_ids[])'],
                    ['PUT', '/assignments/{assignment}/status', 'Update assignment status (pending/playing/paused/stopped/failed)'],
                    ['POST', '/assignments/{assignment}/control', 'Send a control action to an assignment (play/pause/stop)'],
                    ['POST', '/assignments/{assignment}/next', 'Advance to the next track in the assignment'],
                    ['DELETE', '/assignments/{assignment}', 'Delete an assignment'],
                ] as [$method, $route, $desc])
                    <div class="px-6 py-3.5 flex items-center gap-4">
                        {!! $m($method, $method === 'GET' ? 'bg-green-500/15 text-green-400' : ($method === 'POST' ? 'bg-blue-500/15 text-blue-400' : ($method === 'PUT' ? 'bg-amber-500/15 text-amber-400' : 'bg-red-500/15 text-red-400'))) !!}
                        <code class="text-gray-300 font-mono w-52 shrink-0">{{ $route }}</code>
                        <p class="text-gray-500 flex-1">{{ $desc }}</p>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="bg-gray-900 border border-white/10 rounded-2xl overflow-hidden">
            <div class="px-6 py-4 border-b border-white/10 font-bold"><i class="fas fa-layer-group mr-2 text-blue-400"></i>Legacy Campaigns</div>
            <div class="divide-y divide-white/5 text-sm">
                @foreach ([
                    ['GET', '/campaigns', 'List looping campaigns with tracks'],
                    ['POST', '/campaigns', 'Create a campaign (name, platform, tracks[] with media_url + duration_seconds)'],
                    ['POST', '/campaigns/bulk-delete', 'Delete multiple campaigns (ids[])'],
                    ['PUT', '/campaigns/{campaign}', 'Update campaign or its tracks'],
                    ['DELETE', '/campaigns/{campaign}', 'Delete a campaign'],
                    ['POST', '/campaigns/{campaign}/deploy', 'Deploy a campaign to assigned devices'],
                ] as [$method, $route, $desc])
                    <div class="px-6 py-3.5 flex items-center gap-4">
                        {!! $m($method, $method === 'GET' ? 'bg-green-500/15 text-green-400' : ($method === 'POST' ? 'bg-blue-500/15 text-blue-400' : ($method === 'PUT' ? 'bg-amber-500/15 text-amber-400' : 'bg-red-500/15 text-red-400'))) !!}
                        <code class="text-gray-300 font-mono w-52 shrink-0">{{ $route }}</code>
                        <p class="text-gray-500 flex-1">{{ $desc }}</p>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="bg-gray-900 border border-white/10 rounded-2xl overflow-hidden">
            <div class="px-6 py-4 border-b border-white/10 font-bold"><i class="fas fa-globe mr-2 text-blue-400"></i>Proxy Automation &amp; Stats</div>
            <div class="divide-y divide-white/5 text-sm">
                @foreach ([
                    ['GET', '/proxies/refresh', 'Fetch and distribute new premium proxies to the fleet'],
                    ['GET', '/dashboard/stats', 'Fleet dashboard statistics (online, streaming, errors, assignments)'],
                ] as [$method, $route, $desc])
                    <div class="px-6 py-3.5 flex items-center gap-4">
                        {!! $m($method, 'bg-green-500/15 text-green-400') !!}
                        <code class="text-gray-300 font-mono w-52 shrink-0">{{ $route }}</code>
                        <p class="text-gray-500 flex-1">{{ $desc }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    {{-- ── AudioReach v1 endpoints ─────────────────────────────────────── --}}
    <h2 class="text-xl font-bold mb-1 flex items-center"><i class="fas fa-headphones mr-3 text-fuchsia-400"></i>AudioReach v1 — Listener API</h2>
    <p class="text-sm text-gray-500 mb-6">Sanctum Bearer tokens · Base: <code class="text-gray-400">/api/v1</code></p>

    <div class="space-y-8 mb-12">
        {{-- Auth --}}
        <div class="bg-gray-900 border border-white/10 rounded-2xl overflow-hidden">
            <div class="px-6 py-4 border-b border-white/10 font-bold"><i class="fas fa-key mr-2 text-fuchsia-400"></i>Authentication</div>
            <div class="divide-y divide-white/5 text-sm">
                @foreach ([
                    ['POST', '/auth/register', 'Create a listener account. Body: name, email, password, password_confirmation, genre_prefs[]. Issues a 6-digit email code.'],
                    ['POST', '/auth/verify-email', 'Verify the code. Body: email, code. Returns the Bearer token on success.'],
                    ['POST', '/auth/login', 'Login. Body: email, password. Unverified accounts get 403 with requires_verification=true.'],
                    ['POST', '/auth/logout', 'Revoke the current token.'],
                    ['GET', '/me', 'Return the authenticated user profile.'],
                    ['PUT', '/me', 'Update profile. Body: name, phone, genre_prefs[].'],
                ] as [$method, $route, $desc])
                    <div class="px-6 py-3.5 flex items-start gap-4">
                        {!! $m($method, $method === 'GET' ? 'bg-green-500/15 text-green-400' : 'bg-blue-500/15 text-blue-400') !!}
                        <code class="text-gray-300 font-mono w-44 shrink-0">{{ $route }}</code>
                        <p class="text-gray-500 flex-1">{{ $desc }}</p>
                    </div>
                @endforeach
            </div>
            <div class="px-6 py-5 bg-white/5 border-t border-white/10">
                <p class="text-xs font-bold text-gray-400 uppercase tracking-wide mb-3">Register → token flow</p>
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 font-mono text-xs leading-relaxed">
                    <div>
                        <p class="text-gray-500 mb-2">// POST /api/v1/auth/register</p>
                        <pre class="text-gray-300">{
  "name": "Chidi",
  "email": "chidi@example.com",
  "password": "password123",
  "password_confirmation": "password123",
  "genre_prefs": ["afrobeats", "amapiano"]
}</pre>
                    </div>
                    <div>
                        <p class="text-gray-500 mb-2">// POST /api/v1/auth/verify-email → 200</p>
                        <pre class="text-gray-300">{
  "success": true,
  "message": "Email verified. Welcome!",
  "token": "1|abc123...",
  "user": { "id": 5, "email_verified": true, "role": "listener" }
}</pre>
                    </div>
                </div>
            </div>
        </div>

        {{-- Listen flow --}}
        <div class="bg-gray-900 border border-white/10 rounded-2xl overflow-hidden">
            <div class="px-6 py-4 border-b border-white/10 font-bold"><i class="fas fa-headphones mr-2 text-fuchsia-400"></i>Listen Sessions <span class="ml-2 text-xs font-normal text-gray-500">(auth required · listener only)</span></div>
            <div class="divide-y divide-white/5 text-sm">
                @foreach ([
                    ['GET', '/feed', 'List campaigns assigned to you (limit up to 50).'],
                    ['POST', '/listen/{campaign}/start', 'Open a listen session. Returns session_token + min_duration_seconds.'],
                    ['POST', '/listen/{session}/checkpoint', 'Report progress. Body: elapsed_seconds, foreground. Returns can_complete.'],
                    ['POST', '/listen/{session}/complete', 'Finish the session — reward is credited automatically after fraud checks.'],
                ] as [$method, $route, $desc])
                    <div class="px-6 py-3.5 flex items-start gap-4">
                        {!! $m($method, $method === 'GET' ? 'bg-green-500/15 text-green-400' : 'bg-blue-500/15 text-blue-400') !!}
                        <code class="text-gray-300 font-mono w-44 shrink-0">{{ $route }}</code>
                        <p class="text-gray-500 flex-1">{{ $desc }}</p>
                    </div>
                @endforeach
            </div>
            <div class="px-6 py-5 bg-white/5 border-t border-white/10">
                <p class="text-xs font-bold text-gray-400 uppercase tracking-wide mb-3">Complete listen → reward flow</p>
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 font-mono text-xs leading-relaxed">
                    <div>
                        <p class="text-gray-500 mb-2">1 · Start</p>
                        <pre class="text-gray-300">POST /listen/12/start
→ 201 {
  "session": {
    "id": 88,
    "session_token": "...",
    "min_duration_seconds": 30,
    "can_complete": false
  }
}</pre>
                    </div>
                    <div>
                        <p class="text-gray-500 mb-2">2 · Checkpoint</p>
                        <pre class="text-gray-300">POST /listen/88/checkpoint
{ "elapsed_seconds": 45, "foreground": true }
→ { "can_complete": true }</pre>
                    </div>
                    <div>
                        <p class="text-gray-500 mb-2">3 · Complete</p>
                        <pre class="text-gray-300">POST /listen/88/complete
→ 201 {
  "success": true,
  "session": { "status": "rewarded", "reward": 150 }
}</pre>
                    </div>
                </div>
            </div>
        </div>

        {{-- Wallet --}}
        <div class="bg-gray-900 border border-white/10 rounded-2xl overflow-hidden">
            <div class="px-6 py-4 border-b border-white/10 font-bold"><i class="fas fa-wallet mr-2 text-fuchsia-400"></i>Wallet &amp; Payouts</div>
            <div class="divide-y divide-white/5 text-sm">
                @foreach ([
                    ['GET', '/wallet', 'Wallet balance, min payout and the latest 50 transactions.'],
                    ['POST', '/wallet/payout-request', 'Request a payout. Body: amount, method (bank|airtime), account{} or phone. Enforces min payout + one pending request.'],
                ] as [$method, $route, $desc])
                    <div class="px-6 py-3.5 flex items-start gap-4">
                        {!! $m($method, $method === 'GET' ? 'bg-green-500/15 text-green-400' : 'bg-blue-500/15 text-blue-400') !!}
                        <code class="text-gray-300 font-mono w-44 shrink-0">{{ $route }}</code>
                        <p class="text-gray-500 flex-1">{{ $desc }}</p>
                    </div>
                @endforeach
            </div>
            <div class="px-6 py-5 bg-white/5 border-t border-white/10">
                <p class="text-xs font-bold text-gray-400 uppercase tracking-wide mb-3">Payout request examples</p>
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 font-mono text-xs leading-relaxed">
                    <div>
                        <p class="text-gray-500 mb-2">// Bank transfer</p>
                        <pre class="text-gray-300">POST /wallet/payout-request
{
  "amount": 5000,
  "method": "bank",
  "account": {
    "bank_code": "044",
    "account_number": "0123456789",
    "account_name": "Chidi Okafor"
  }
} → 201</pre>
                    </div>
                    <div>
                        <p class="text-gray-500 mb-2">// Airtime</p>
                        <pre class="text-gray-300">POST /wallet/payout-request
{
  "amount": 5000,
  "method": "airtime",
  "phone": "08012345678"
} → 201</pre>
                    </div>
                </div>
                <p class="mt-4 text-xs text-gray-500">Below minimum → <code class="text-amber-300">422</code> "Minimum payout is 1000." · Existing pending request → <code class="text-amber-300">422</code> "You already have a pending payout request."</p>
            </div>
        </div>

        {{-- Settings / device / webhooks --}}
        <div class="bg-gray-900 border border-white/10 rounded-2xl overflow-hidden">
            <div class="px-6 py-4 border-b border-white/10 font-bold"><i class="fas fa-cog mr-2 text-fuchsia-400"></i>Settings, Devices &amp; Webhooks</div>
            <div class="divide-y divide-white/5 text-sm">
                @foreach ([
                    ['GET', '/settings', 'App settings map + maintenance_mode flag. Compares X-App-Version against min_app_version.'],
                    ['POST', '/device/register', 'Register this phone for push. Body: fingerprint, fcm_token, platform, app_version.'],
                    ['POST', '/device/heartbeat', 'Refresh device last_seen. Body: fingerprint.'],
                    ['POST', '/webhooks/paystack', 'Paystack webhook (public). Handles payment confirmation for campaign funding.'],
                ] as [$method, $route, $desc])
                    <div class="px-6 py-3.5 flex items-start gap-4">
                        {!! $m($method, $method === 'GET' ? 'bg-green-500/15 text-green-400' : 'bg-blue-500/15 text-blue-400') !!}
                        <code class="text-gray-300 font-mono w-44 shrink-0">{{ $route }}</code>
                        <p class="text-gray-500 flex-1">{{ $desc }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    {{-- ── Conventions ─────────────────────────────────────────────────── --}}
    <h2 class="text-xl font-bold mb-6 flex items-center"><i class="fas fa-book mr-3 text-emerald-400"></i>Conventions</h2>
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="bg-gray-900 border border-white/10 rounded-2xl p-6">
            <h3 class="font-bold text-sm mb-3"><i class="fas fa-check-circle mr-2 text-green-400"></i>Success</h3>
            <p class="text-sm text-gray-400 leading-relaxed">Both APIs return <code class="text-green-300 bg-green-500/10 px-1.5 py-0.5 rounded text-xs">{"success": true, ...}</code> with the resource payload. Creation endpoints return <code class="text-green-300">201</code>, reads <code class="text-green-300">200</code>.</p>
        </div>
        <div class="bg-gray-900 border border-white/10 rounded-2xl p-6">
            <h3 class="font-bold text-sm mb-3"><i class="fas fa-times-circle mr-2 text-red-400"></i>Errors</h3>
            <p class="text-sm text-gray-400 leading-relaxed">Validation errors → <code class="text-amber-300">422</code> with <code class="text-gray-300">{"errors": {...}}</code>. Business rule failures → <code class="text-amber-300">422</code> with a <code class="text-gray-300">message</code>. Unauthenticated → <code class="text-red-300">401</code>. Wrong role → <code class="text-red-300">403</code>.</p>
        </div>
        <div class="bg-gray-900 border border-white/10 rounded-2xl p-6">
            <h3 class="font-bold text-sm mb-3"><i class="fas fa-clock mr-2 text-blue-400"></i>Notes</h3>
            <p class="text-sm text-gray-400 leading-relaxed">Timestamps are ISO-8601. Amounts are integers in Naira (₦). Listener daily session limit is enforced server-side (default 50/day). Rewards credit via the queue — allow a few seconds after <code class="text-gray-300">complete</code>.</p>
        </div>
    </div>
@endsection
