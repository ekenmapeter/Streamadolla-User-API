<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\CountryReward;
use App\Models\ListenSession;
use App\Models\PayoutRequest;
use App\Models\User;
use Illuminate\Http\Request;

class ActivityController extends Controller
{
    public function index(Request $request)
    {
        /** @var User $user */
        $user = $request->user();

        $sessions = ListenSession::with('assignment.campaign.artist')
            ->where('listener_id', $user->id)
            ->orderByDesc('created_at')
            ->limit(30)
            ->get()
            ->map(fn (ListenSession $s) => [
                'id' => $s->id,
                'campaign_id' => $s->assignment?->campaign_id,
                'campaign_title' => $s->assignment?->campaign?->title,
                'artist' => $s->assignment?->campaign?->artist?->name,
                'reward' => CountryReward::amountFor($s->country_code),
                'country_code' => $s->country_code,
                'status' => $s->status,
                'elapsed_seconds' => (int) $s->elapsed_seconds,
                'completed_at' => $s->completed_at?->toIso8601String(),
                'created_at' => $s->created_at?->toIso8601String(),
            ]);

        $payouts = PayoutRequest::where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->limit(20)
            ->get()
            ->map(fn (PayoutRequest $p) => [
                'id' => $p->id,
                'amount' => (int) $p->amount,
                'method' => $p->method,
                'status' => $p->status,
                'hold_until_at' => $p->hold_until_at?->toIso8601String(),
                'created_at' => $p->created_at?->toIso8601String(),
            ]);

        $todayCompleted = ListenSession::where('listener_id', $user->id)
            ->where('status', ListenSession::STATUS_REWARDED)
            ->whereDate('completed_at', today())
            ->count();

        return response()->json([
            'success' => true,
            'today_completed' => $todayCompleted,
            'sessions' => $sessions,
            'payouts' => $payouts,
        ]);
    }
}