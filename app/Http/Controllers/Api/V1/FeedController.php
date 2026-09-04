<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\CampaignAssignment;
use App\Models\CountryReward;
use App\Models\PromoCampaign;
use App\Models\User;
use App\Services\GeoIpService;
use Illuminate\Http\Request;

class FeedController extends Controller
{
    public function index(Request $request, GeoIpService $geo)
    {
        /** @var User $user */
        $user = $request->user();
        $limit = min((int) $request->input('limit', 20), 50);

        $countryCode = $geo->countryCode($request->ip());
        $reward = CountryReward::amountFor($countryCode);

        $assignedIds = CampaignAssignment::where('listener_id', $user->id)
            ->whereIn('status', [CampaignAssignment::STATUS_ASSIGNED, CampaignAssignment::STATUS_LISTENING])
            ->pluck('campaign_id');

        $campaigns = PromoCampaign::active()
            ->with('artist:id,name')
            ->whereIn('id', $assignedIds)
            ->orderBy('starts_at', 'desc')
            ->limit($limit)
            ->get()
            ->map(fn (PromoCampaign $c) => [
                'id' => $c->id,
                'title' => $c->title,
                'track_url' => $c->track_url,
                'platform' => $c->platform,
                'genres' => $c->genres ?? [],
                'artist' => $c->artist?->name,
                'reward_per_review' => (int) $c->reward_per_review,
                'reward' => $reward,
                'country_code' => $countryCode,
                'starts_at' => $c->starts_at?->toIso8601String(),
            ]);

        return response()->json([
            'success' => true,
            'campaigns' => $campaigns,
        ]);
    }
}