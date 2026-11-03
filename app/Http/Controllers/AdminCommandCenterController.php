<?php

namespace App\Http\Controllers;

use App\Models\AppSetting;
use App\Models\CampaignPackage;
use App\Models\Device;
use App\Models\DeviceRental;
use App\Models\DeviceSession;
use App\Models\ListenSession;
use App\Models\ListenerProfile;
use App\Models\PromoCampaign;
use App\Models\StreamPlay;
use App\Models\Track;
use App\Models\User;
use App\Models\UserDevice;
use App\Models\PayoutRequest;
use App\Models\WalletTransaction;
use App\Services\FirebaseMessagingService;
use App\Services\WalletService;
use App\Support\Money;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AdminCommandCenterController extends Controller
{
    public function index()
    {
        $stats = [
            'listeners' => User::where('role', User::ROLE_LISTENER)->count(),
            'artists' => User::where('role', User::ROLE_ARTIST)->count(),
            'active_campaigns' => PromoCampaign::active()->count(),
            'sessions_today' => ListenSession::whereDate('completed_at', today())->count(),
            'rewarded' => ListenSession::where('status', ListenSession::STATUS_REWARDED)->count(),
            'fraud' => ListenSession::where('status', ListenSession::STATUS_FRAUD)->count(),
            'payouts_pending' => PayoutRequest::pending()->count(),
            'fcm_devices' => UserDevice::whereNotNull('fcm_token')->where('last_seen_at', '>=', now()->subDay())->count(),
            'active_rentals' => DeviceRental::active()->count(),
            'rental_streams_today' => StreamPlay::where('status', StreamPlay::STATUS_REWARDED)
                ->whereNotNull('rental_id')
                ->whereDate('created_at', today())
                ->count(),
            'rental_paid_today' => Money::number(StreamPlay::where('status', StreamPlay::STATUS_REWARDED)
                ->whereNotNull('rental_id')
                ->whereDate('created_at', today())
                ->sum('reward_amount')),
            'tracks_pending' => Track::where('status', Track::STATUS_PENDING_APPROVAL)->count(),
            'vendors' => User::where('role', User::ROLE_VENDOR)->count(),
            'vendor_streams_today' => StreamPlay::where('status', StreamPlay::STATUS_REWARDED)
                ->whereDate('created_at', today())
                ->whereHas('user', fn ($q) => $q->where('role', User::ROLE_VENDOR))
                ->count(),
            'vendor_paid_today' => Money::number(StreamPlay::where('status', StreamPlay::STATUS_REWARDED)
                ->whereDate('created_at', today())
                ->whereHas('user', fn ($q) => $q->where('role', User::ROLE_VENDOR))
                ->sum('reward_amount')),
        ];

        $recentSessions = ListenSession::with(['assignment.campaign:id,title', 'listener:id,name'])
            ->whereNotNull('completed_at')
            ->orderByDesc('completed_at')
            ->limit(10)
            ->get();

        return view('admin.overview', compact('stats', 'recentSessions'));
    }

    public function campaigns(Request $request)
    {
        $campaigns = PromoCampaign::with(['artist:id,name', 'package'])
            ->withCount(['sessions as rewarded_sessions_count' => fn ($q) => $q->where('listen_sessions.status', ListenSession::STATUS_REWARDED)])
            ->orderByDesc('created_at')
            ->paginate(20);

        return view('admin.campaigns', compact('campaigns'));
    }

    public function createCampaign()
    {
        $artists = User::where('role', User::ROLE_ARTIST)->orderBy('name')->get(['id', 'name', 'email']);
        $packages = CampaignPackage::where('is_active', true)->get();

        return view('admin.campaign-create', compact('artists', 'packages'));
    }

    public function storeCampaign(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'artist_id' => 'required|exists:users,id',
            'title' => 'required|string|max:200',
            'track_url' => 'required|url|max:500',
            'platform' => 'required|in:youtube,spotify,audiomack,boomplay,apple_music,other',
            'genres' => 'nullable|array',
            'genres.*' => 'string|max:50',
            'package_id' => 'required|exists:campaign_packages,id',
            'starts_at' => 'nullable|date',
            'status' => 'required|in:active,draft',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $package = CampaignPackage::findOrFail($request->package_id);

        $campaign = PromoCampaign::create([
            'artist_id' => $request->artist_id,
            'package_id' => $package->id,
            'title' => $request->title,
            'track_url' => $request->track_url,
            'platform' => $request->platform,
            'genres' => $request->input('genres', []),
            'reward_per_review' => max(50, (int) round($package->price_ngn * 0.5 / $package->review_target)),
            'listen_target' => $package->listen_target,
            'review_target' => $package->review_target,
            'status' => $request->status,
            'amount_paid_ngn' => $package->price_ngn,
            'starts_at' => $request->input('starts_at'),
        ]);

        if ($campaign->isActive()) {
            $campaign->update(['funded_at' => now()]);
            \App\Jobs\DistributeCampaignJob::dispatch($campaign->id);
        }

        return redirect()->route('admin.campaigns')
            ->with('status', "Campaign '{$campaign->title}' created for {$campaign->artist?->name}.");
    }

    public function activateCampaign(Request $request, PromoCampaign $campaign)
    {
        $campaign->update([
            'status' => PromoCampaign::STATUS_ACTIVE,
            'funded_at' => $campaign->funded_at ?? now(),
            'starts_at' => $campaign->starts_at ?? now(),
        ]);

        \App\Jobs\DistributeCampaignJob::dispatch($campaign->id);

        return back()->with('status', "Campaign '{$campaign->title}' activated and distributed to eligible listeners.");
    }

    public function pauseCampaign(PromoCampaign $campaign)
    {
        $campaign->update(['status' => PromoCampaign::STATUS_PAUSED]);

        return back()->with('status', "Campaign '{$campaign->title}' paused.");
    }

    public function apiDocs()
    {
        return view('admin.api-docs');
    }

    public function listeners(Request $request)
    {
        $showDeleted = $request->query('status') === 'deleted';

        $query = User::query()->where('role', User::ROLE_LISTENER);

        if ($showDeleted) {
            $query->withTrashed()->whereNotNull('deleted_at');
        }

        $query
            ->with(['listenerProfile', 'userDevices'])
            ->withCount([
                'listenSessions as rewarded_sessions_count' => fn ($q) => $q->where('status', ListenSession::STATUS_REWARDED),
                'listenSessions as fraud_sessions_count' => fn ($q) => $q->where('status', ListenSession::STATUS_FRAUD),
                'listenSessions as open_sessions_count' => fn ($q) => $q->where('status', ListenSession::STATUS_OPEN),
                'payoutRequests as pending_payouts_count' => fn ($q) => $q->pending(),
            ])
            ->withSum(['walletTransactions as total_earned' => fn ($q) => $q->whereIn('type', [WalletTransaction::TYPE_REWARD, WalletTransaction::TYPE_BONUS, WalletTransaction::TYPE_RENTAL])->where('status', WalletTransaction::STATUS_CREDITED)], 'amount')
            ->withSum(['walletTransactions as total_paid_out' => fn ($q) => $q->where('type', WalletTransaction::TYPE_PAYOUT)->where('status', WalletTransaction::STATUS_CREDITED)], 'amount')
            ->withMax('listenSessions as last_listened_at', 'completed_at');

        // Search
        if ($q = trim((string) $request->query('q'))) {
            $query->where(function ($b) use ($q) {
                $b->where('name', 'like', "%{$q}%")
                    ->orWhere('email', 'like', "%{$q}%")
                    ->orWhere('phone', 'like', "%{$q}%");
            });
        }

        // Trust level filter
        if ($request->query('trust') !== null && $request->query('trust') !== '') {
            $query->whereHas('listenerProfile', fn ($b) => $b->where('trust_level', (int) $request->query('trust')));
        }

        // Account status filter
        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        // Sort
        $sort = $request->query('sort', 'recent');
        $sortMap = [
            'recent' => fn ($b) => $b->orderByDesc('created_at'),
            'earned' => fn ($b) => $b->orderByDesc('total_earned'),
            'listens' => fn ($b) => $b->orderByDesc('rewarded_sessions_count'),
            'fraud' => fn ($b) => $b->orderByDesc('fraud_sessions_count'),
        ];
        ($sortMap[$sort] ?? $sortMap['recent'])($query);

        $listeners = $query->paginate(15)->withQueryString();

        // ── Global metrics ─────────────────────────────────────────────────────
        $rewardCredits = fn ($q) => $q->whereIn('type', [WalletTransaction::TYPE_REWARD, WalletTransaction::TYPE_BONUS, WalletTransaction::TYPE_RENTAL])->where('status', WalletTransaction::STATUS_CREDITED);
        $payoutDebits = fn ($q) => $q->where('type', WalletTransaction::TYPE_PAYOUT)->where('status', WalletTransaction::STATUS_CREDITED);

        $listenerTransactions = fn ($q) => $q->whereHas('user', fn ($uq) => $uq->where('role', User::ROLE_LISTENER));

        $metrics = [
            'total' => User::where('role', User::ROLE_LISTENER)->count(),
            'verified' => User::where('role', User::ROLE_LISTENER)->whereNotNull('email_verified_at')->count(),
            'active_today' => ListenSession::whereDate('completed_at', today())->distinct('listener_id')->count('listener_id'),
            'active_week' => ListenSession::where('completed_at', '>=', now()->subDays(7))->distinct('listener_id')->count('listener_id'),
            'rewarded_sessions' => ListenSession::where('status', ListenSession::STATUS_REWARDED)->count(),
            'fraud_sessions' => ListenSession::where('status', ListenSession::STATUS_FRAUD)->count(),
            'pending_payouts' => PayoutRequest::pending()->whereHas('user', fn ($q) => $q->where('role', User::ROLE_LISTENER))->count(),
            'total_earned' => (int) $listenerTransactions(WalletTransaction::query())->whereIn('type', [WalletTransaction::TYPE_REWARD, WalletTransaction::TYPE_BONUS, WalletTransaction::TYPE_RENTAL])->where('status', WalletTransaction::STATUS_CREDITED)->sum('amount'),
            'total_paid_out' => (int) $listenerTransactions(WalletTransaction::query())->where('type', WalletTransaction::TYPE_PAYOUT)->where('status', WalletTransaction::STATUS_CREDITED)->sum('amount'),
            'avg_trust' => round((float) ListenerProfile::avg('trust_level'), 2),
            'avg_streak' => round((float) ListenerProfile::avg('streak'), 1),
            'fcm_devices' => UserDevice::whereNotNull('fcm_token')->where('last_seen_at', '>=', now()->subDay())->count(),
            'total_balance' => (int) $listenerTransactions(WalletTransaction::query())->whereIn('type', [WalletTransaction::TYPE_REWARD, WalletTransaction::TYPE_BONUS, WalletTransaction::TYPE_RENTAL])->where('status', WalletTransaction::STATUS_CREDITED)->sum('amount')
                - (int) $listenerTransactions(WalletTransaction::query())->where('type', WalletTransaction::TYPE_PAYOUT)->where('status', WalletTransaction::STATUS_CREDITED)->sum('amount'),
        ];

        // Trust-level distribution
        $trustDist = ListenerProfile::selectRaw('trust_level, count(*) as total, coalesce(sum(total_earned), 0) as earned, coalesce(avg(streak), 0) as avg_streak')
            ->groupBy('trust_level')
            ->orderBy('trust_level')
            ->get()
            ->keyBy('trust_level');

        // Top genres (from listener genre prefs)
        $genreCounts = [];
        ListenerProfile::whereNotNull('genre_prefs')->pluck('genre_prefs')->each(function ($prefs) use (&$genreCounts) {
            foreach ((array) $prefs as $genre) {
                $genreCounts[$genre] = ($genreCounts[$genre] ?? 0) + 1;
            }
        });
        arsort($genreCounts);
        $topGenres = array_slice($genreCounts, 0, 8, true);

        // Top earners
        $topEarners = User::where('role', User::ROLE_LISTENER)
            ->with(['listenerProfile'])
            ->withSum(['walletTransactions as earned' => fn ($q) => $q->whereIn('type', [WalletTransaction::TYPE_REWARD, WalletTransaction::TYPE_BONUS, WalletTransaction::TYPE_RENTAL])->where('status', WalletTransaction::STATUS_CREDITED)], 'amount')
            ->orderByDesc('earned')
            ->limit(5)
            ->get();

        return view('admin.listeners', compact('listeners', 'metrics', 'trustDist', 'topGenres', 'topEarners', 'sort'));
    }

    /**
     * Vendor fleet overview – every vendor with per-vendor earnings and
     * fraud visibility (mirrors the listeners admin page).
     */
    public function vendors(Request $request)
    {
        $query = User::query()->where('role', User::ROLE_VENDOR);

        if ($q = trim((string) $request->query('q'))) {
            $query->where(function ($b) use ($q) {
                $b->where('name', 'like', "%{$q}%")
                    ->orWhere('email', 'like', "%{$q}%")
                    ->orWhere('phone', 'like', "%{$q}%");
            });
        }

        $sort = $request->query('sort', 'recent');
        $sortMap = [
            'recent' => fn ($b) => $b->orderByDesc('created_at'),
            'earned' => fn ($b) => $b->orderByDesc('total_earned'),
            'streams' => fn ($b) => $b->orderByDesc('rewarded_streams_count'),
            'devices' => fn ($b) => $b->orderByDesc('devices_count'),
        ];
        ($sortMap[$sort] ?? $sortMap['recent'])($query);

        $vendors = $query
            ->withCount('devices')
            ->withCount(['streamPlays as rewarded_streams_count' => fn ($q) => $q->where('status', StreamPlay::STATUS_REWARDED)])
            ->withCount(['streamPlays as streams_today_count' => fn ($q) => $q->where('status', StreamPlay::STATUS_REWARDED)->whereDate('created_at', today())])
            ->withCount(['deviceSessions as fraud_sessions_count' => fn ($q) => $q->where('status', DeviceSession::STATUS_FRAUD)])
            ->withSum(['walletTransactions as total_earned' => fn ($q) => $q->where('type', WalletTransaction::TYPE_VENDOR)->where('status', WalletTransaction::STATUS_CREDITED)], 'amount')
            ->withMax('devices as last_seen_at', 'last_seen')
            ->paginate(15)
            ->withQueryString();

        $vendorCredits = fn ($q) => $q
            ->whereHas('user', fn ($uq) => $uq->where('role', User::ROLE_VENDOR))
            ->where('type', WalletTransaction::TYPE_VENDOR)
            ->where('status', WalletTransaction::STATUS_CREDITED);

        $metrics = [
            'total' => User::where('role', User::ROLE_VENDOR)->count(),
            'verified' => User::where('role', User::ROLE_VENDOR)->whereNotNull('email_verified_at')->count(),
            'devices' => Device::whereNotNull('user_id')->count(),
            'active_today' => StreamPlay::where('status', StreamPlay::STATUS_REWARDED)
                ->whereDate('created_at', today())
                ->whereHas('user', fn ($q) => $q->where('role', User::ROLE_VENDOR))
                ->distinct('user_id')->count('user_id'),
            'streams_today' => StreamPlay::where('status', StreamPlay::STATUS_REWARDED)
                ->whereDate('created_at', today())
                ->whereHas('user', fn ($q) => $q->where('role', User::ROLE_VENDOR))
                ->count(),
            'total_earned' => Money::number($vendorCredits(WalletTransaction::query())->sum('amount')),
            'fraud_sessions' => DeviceSession::where('status', DeviceSession::STATUS_FRAUD)->count(),
            'pending_payouts' => PayoutRequest::whereHas('user', fn ($q) => $q->where('role', User::ROLE_VENDOR))->pending()->count(),
        ];

        $topEarners = User::where('role', User::ROLE_VENDOR)
            ->withCount('devices')
            ->withSum(['walletTransactions as earned' => fn ($q) => $q->where('type', WalletTransaction::TYPE_VENDOR)->where('status', WalletTransaction::STATUS_CREDITED)], 'amount')
            ->orderByDesc('earned')
            ->limit(5)
            ->get();

        return view('admin.vendors', compact('vendors', 'metrics', 'topEarners', 'sort'));
    }

    /**
     * Per-vendor detail: devices, earnings record and flagged sessions.
     */
    public function vendorDetail(User $user)
    {
        abort_unless($user->role === User::ROLE_VENDOR, 404);

        $devices = $user->devices()->orderByDesc('last_seen')->get();

        $deviceTotals = $user->streamPlays()
            ->where('stream_plays.status', StreamPlay::STATUS_REWARDED)
            ->join('device_assignments', 'device_assignments.id', '=', 'stream_plays.device_assignment_id')
            ->selectRaw('device_assignments.device_id, count(*) as streams, sum(stream_plays.reward_amount) as earned')
            ->groupBy('device_assignments.device_id')
            ->pluck('streams', 'device_id');

        $deviceEarned = $user->streamPlays()
            ->where('stream_plays.status', StreamPlay::STATUS_REWARDED)
            ->join('device_assignments', 'device_assignments.id', '=', 'stream_plays.device_assignment_id')
            ->selectRaw('device_assignments.device_id, sum(stream_plays.reward_amount) as earned')
            ->groupBy('device_assignments.device_id')
            ->pluck('earned', 'device_id');

        $streams = $user->streamPlays()
            ->with(['assignment.device:id,name,device_id', 'track:id,title'])
            ->where('status', StreamPlay::STATUS_REWARDED)
            ->orderByDesc('created_at')
            ->limit(30)
            ->get();

        $sessions = $user->deviceSessions()
            ->with('device:id,name,device_id')
            ->orderByDesc('created_at')
            ->limit(30)
            ->get();

        $stats = [
            'total_streams' => $user->streamPlays()->where('status', StreamPlay::STATUS_REWARDED)->count(),
            'total_earned' => Money::number($user->walletTransactions()
                ->where('type', WalletTransaction::TYPE_VENDOR)
                ->where('status', WalletTransaction::STATUS_CREDITED)
                ->sum('amount')),
            'total_paid_out' => Money::number($user->walletTransactions()
                ->where('type', WalletTransaction::TYPE_PAYOUT)
                ->where('currency', Money::USD)
                ->where('status', WalletTransaction::STATUS_CREDITED)
                ->sum('amount')),
            'fraud_sessions' => (int) $user->deviceSessions()->where('status', DeviceSession::STATUS_FRAUD)->count(),
            'open_sessions' => (int) $user->deviceSessions()->where('status', DeviceSession::STATUS_OPEN)->count(),
            'balance' => Money::number(app(\App\Services\WalletService::class)->balance($user)),
        ];

        $transactions = $user->walletTransactions()->orderByDesc('created_at')->limit(20)->get();

        return view('admin.vendor-detail', compact('user', 'devices', 'deviceTotals', 'deviceEarned', 'streams', 'sessions', 'stats', 'transactions'));
    }

    public function listenerDetail(User $user)
    {
        abort_unless(in_array($user->role, [User::ROLE_LISTENER, User::ROLE_ARTIST]), 404);

        $profile = $user->listenerProfile;

        $rewardedQ = fn ($q) => $q->where('status', ListenSession::STATUS_REWARDED);
        $fraudQ = fn ($q) => $q->where('status', ListenSession::STATUS_FRAUD);
        $openQ = fn ($q) => $q->where('status', ListenSession::STATUS_OPEN);

        $stats = [
            'rewarded_sessions' => $user->listenSessions()->where('status', ListenSession::STATUS_REWARDED)->count(),
            'fraud_sessions' => $user->listenSessions()->where('status', ListenSession::STATUS_FRAUD)->count(),
            'open_sessions' => $user->listenSessions()->where('status', ListenSession::STATUS_OPEN)->count(),
            'total_sessions' => $user->listenSessions()->count(),
            'total_earned' => (int) $user->walletTransactions()
                ->whereIn('type', [WalletTransaction::TYPE_REWARD, WalletTransaction::TYPE_BONUS, WalletTransaction::TYPE_RENTAL])
                ->where('status', WalletTransaction::STATUS_CREDITED)
                ->sum('amount'),
            'total_paid_out' => (int) $user->walletTransactions()
                ->where('type', WalletTransaction::TYPE_PAYOUT)
                ->where('status', WalletTransaction::STATUS_CREDITED)
                ->sum('amount'),
            'balance' => (int) $user->walletTransactions()
                ->whereIn('type', [WalletTransaction::TYPE_REWARD, WalletTransaction::TYPE_BONUS, WalletTransaction::TYPE_RENTAL])
                ->where('status', WalletTransaction::STATUS_CREDITED)
                ->sum('amount')
                - (int) $user->walletTransactions()
                    ->where('type', WalletTransaction::TYPE_PAYOUT)
                    ->where('status', WalletTransaction::STATUS_CREDITED)
                    ->sum('amount'),
            'devices' => $user->userDevices()->count(),
            'last_listened_at' => $user->listenSessions()->max('completed_at'),
        ];

        $devices = $user->userDevices()->orderByDesc('last_seen_at')->get();

        $sessions = $user->listenSessions()
            ->with('assignment.campaign:id,title')
            ->orderByDesc('completed_at')
            ->limit(15)
            ->get();

        $transactions = $user->walletTransactions()
            ->orderByDesc('created_at')
            ->limit(15)
            ->get();

        $payouts = $user->payoutRequests()->orderByDesc('created_at')->get();

        $footprint = $user->listenSessions()
            ->selectRaw('ip_address, country_code, count(*) as sessions, min(started_at) as first_seen, max(started_at) as last_seen')
            ->whereNotNull('ip_address')
            ->groupBy('ip_address', 'country_code')
            ->orderByDesc('sessions')
            ->get();

        return view('admin.listener-detail', compact('user', 'profile', 'stats', 'devices', 'sessions', 'transactions', 'payouts', 'footprint'));
    }

    public function payouts(Request $request)
    {
        $payouts = PayoutRequest::with('user:id,name,email,phone,ip_address,status')
            ->orderByDesc('created_at')
            ->paginate(25);

        return view('admin.payouts', compact('payouts'));
    }

    public function markPayoutPaid(Request $request, PayoutRequest $payout)
    {
        $payout->update([
            'status' => PayoutRequest::STATUS_PAID,
            'paid_at' => now(),
        ]);

        return back()->with('status', 'Payout marked as paid.');
    }

    public function rejectPayout(Request $request, PayoutRequest $payout)
    {
        $validator = Validator::make($request->all(), [
            'note' => 'nullable|string|max:500',
        ]);

        $payout->update([
            'status' => PayoutRequest::STATUS_REJECTED,
            'note' => $request->input('note'),
        ]);

        (new \App\Services\WalletService())->credit(
            $payout->user,
            $payout->amount,
            \App\Models\WalletTransaction::TYPE_BONUS,
            ['payout_request_id' => $payout->id, 'note' => 'Payout rejected — refunded']
        );

        return back()->with('status', 'Payout rejected and funds returned to the wallet.');
    }

    public function appSettings()
    {
        $settings = AppSetting::orderBy('group')->orderBy('key')->get();
        $existing = $settings->pluck('value', 'key')->toArray();

        $fields = [
            ['listen_min_seconds', 'Min listen (seconds)', '30'],
            ['reward_per_review', 'Reward per listen (₦)', '100'],
            ['min_payout', 'Min payout (₦)', '1000'],
            ['payout_hold_hours', 'Payout hold (hours)', '72'],
            ['daily_review_limit', 'Max sessions / day', '50'],
            ['vendor_rate_per_stream', 'Vendor reward per stream ($)', '0.001'],
            ['vendor_min_payout', 'Vendor minimum payout ($)', '1.00'],
            ['usd_ngn_rate', 'USD → NGN rate (vendor payouts)', '1344.50'],
            ['vendor_min_seconds', 'Vendor min stream (seconds)', '30'],
            ['vendor_max_streams_per_device_per_day', 'Vendor max streams / device / day', '50'],
        ];

        return view('admin.app-settings', compact('settings', 'existing', 'fields'));
    }

    public function saveAppSettings(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'settings' => 'required|array',
            'settings.*.key' => 'required|string|max:100',
            'settings.*.value' => 'nullable',
            'settings.*.group' => 'nullable|string|max:50',
            'settings.*.description' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator);
        }

        foreach ($request->input('settings', []) as $row) {
            $raw = $row['value'] ?? null;
            $value = is_numeric($raw)
                ? (str_contains((string) $raw, '.') ? (float) $raw : (int) $raw)
                : $raw;

            AppSetting::updateOrCreate(
                ['key' => $row['key']],
                [
                    'value' => $value,
                    'group' => $row['group'] ?? 'general',
                    'description' => $row['description'] ?? null,
                ]
            );
        }

        return back()->with('status', 'App settings saved.');
    }

    public function sendPush(Request $request, FirebaseMessagingService $messaging)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:100',
            'message' => 'required|string|max:500',
            'audience' => 'required|in:all,listeners,artists',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator);
        }

        $role = $request->audience === 'all' ? null : ($request->audience === 'listeners' ? User::ROLE_LISTENER : User::ROLE_ARTIST);

        $query = UserDevice::whereNotNull('fcm_token')->where('last_seen_at', '>=', now()->subDays(30));
        if ($role) {
            $query->whereHas('user', fn ($q) => $q->where('role', $role));
        }

        $tokens = $query->pluck('fcm_token')->unique()->values()->all();
        $sent = 0;

        foreach (array_chunk($tokens, 500) as $chunk) {
            foreach ($chunk as $token) {
                if ($messaging->notifToToken($token, $request->title, $request->message)) {
                    $sent++;
                }
            }
        }

        return back()->with('status', "Push notification sent to {$sent} device(s).");
    }

    public function setTrustLevel(Request $request, User $user)
    {
        $validator = Validator::make($request->all(), [
            'trust_level' => 'required|integer|between:0,3',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator);
        }

        $user->listenerProfile()->updateOrCreate(
            ['user_id' => $user->id],
            ['trust_level' => $request->trust_level]
        );

        return back()->with('status', "Trust level updated for {$user->name}.");
    }
}