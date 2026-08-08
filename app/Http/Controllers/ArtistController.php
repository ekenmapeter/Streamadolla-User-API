<?php

namespace App\Http\Controllers;

use App\Models\CampaignPackage;
use App\Models\ListenSession;
use App\Models\PromoCampaign;
use App\Services\PaystackService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ArtistController extends Controller
{
    public function dashboard(Request $request)
    {
        $artist = $request->user();
        $campaigns = PromoCampaign::ownedBy($artist->id)
            ->withCount(['sessions as rewarded_sessions_count' => fn ($q) => $q->where('listen_sessions.status', ListenSession::STATUS_REWARDED)])
            ->orderByDesc('created_at')
            ->get();

        $stats = [
            'total_campaigns' => $campaigns->count(),
            'active_campaigns' => $campaigns->where('status', PromoCampaign::STATUS_ACTIVE)->count(),
            'total_listens' => $campaigns->sum('rewarded_sessions_count'),
            'total_spent' => $campaigns->sum('amount_paid_ngn'),
        ];

        return view('artist.dashboard', compact('campaigns', 'stats'));
    }

    public function createCampaign()
    {
        $packages = CampaignPackage::where('is_active', true)->get();

        return view('artist.campaign-create', compact('packages'));
    }

    public function storeCampaign(Request $request, PaystackService $paystack)
    {
        $artist = $request->user();

        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:200',
            'track_url' => 'required|url|max:500',
            'platform' => 'required|in:youtube,spotify,audiomack,boomplay,apple_music,other',
            'genres' => 'nullable|array',
            'genres.*' => 'string|max:50',
            'package_id' => 'required|exists:campaign_packages,id',
            'starts_at' => 'nullable|date|after_or_equal:today',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $package = CampaignPackage::findOrFail($request->package_id);

        $campaign = PromoCampaign::create([
            'artist_id' => $artist->id,
            'package_id' => $package->id,
            'title' => $request->title,
            'track_url' => $request->track_url,
            'platform' => $request->platform,
            'genres' => $request->input('genres', []),
            'reward_per_review' => max(50, (int) round($package->price_ngn * 0.5 / $package->review_target)),
            'listen_target' => $package->listen_target,
            'review_target' => $package->review_target,
            'status' => PromoCampaign::STATUS_PENDING_PAYMENT,
            'amount_paid_ngn' => $package->price_ngn,
            'starts_at' => $request->input('starts_at'),
        ]);

        if (! config('services.paystack.secret_key')) {
            return redirect()->route('artist.campaign.show', $campaign)
                ->with('status', 'Campaign created. Payment gateway not configured — activate manually from the command center.');
        }

        try {
            $checkout = $paystack->initialize([
                'amount' => $package->price_ngn * 100,
                'email' => $artist->email,
                'reference' => 'AR-' . strtoupper(uniqid()),
                'metadata' => [
                    'campaign_id' => $campaign->id,
                    'kind' => 'campaign_funding',
                ],
            ]);

            $campaign->update(['payment_reference' => $checkout['reference'] ?? null]);

            return redirect()->away($checkout['authorization_url'] ?? route('artist.campaign.show', $campaign));
        } catch (\Throwable $e) {
            report($e);

            return redirect()->route('artist.campaign.show', $campaign)
                ->with('status', 'Campaign created but payment could not be started. Please try funding later.');
        }
    }

    public function showCampaign(Request $request, PromoCampaign $campaign)
    {
        abort_if($campaign->artist_id !== $request->user()->id, 403);

        $sessions = $campaign->sessions()
            ->with('listener:id,name')
            ->orderByDesc('completed_at')
            ->paginate(20);

        $stats = [
            'total_listens' => $campaign->rewardedSessionCount(),
            'target_listens' => $campaign->listen_target ?? $campaign->review_target,
            'active_listeners' => $campaign->sessions()->where('status', ListenSession::STATUS_REWARDED)->distinct('listener_id')->count('listener_id'),
        ];

        return view('artist.campaign-show', compact('campaign', 'sessions', 'stats'));
    }
}