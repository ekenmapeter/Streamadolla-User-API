<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Jobs\RewardSessionJob;
use App\Models\AppSetting;
use App\Models\CampaignAssignment;
use App\Models\ListenSession;
use App\Models\PromoCampaign;
use App\Models\User;
use App\Services\FraudEngine;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ListenController extends Controller
{
    public function start(Request $request, PromoCampaign $campaign)
    {
        /** @var User $user */
        $user = $request->user();

        if (! $campaign->isActive()) {
            return response()->json([
                'success' => false,
                'message' => 'This campaign is not currently active.',
            ], 422);
        }

        if ($campaign->ends_at && $campaign->ends_at->isPast()) {
            return response()->json([
                'success' => false,
                'message' => 'This campaign has ended.',
            ], 422);
        }

        $assignment = CampaignAssignment::where('campaign_id', $campaign->id)
            ->where('listener_id', $user->id)
            ->first();

        if (! $assignment) {
            return response()->json([
                'success' => false,
                'message' => 'This track has not been assigned to you yet.',
            ], 422);
        }

        if ($assignment->status === CampaignAssignment::STATUS_REVIEWED) {
            return response()->json([
                'success' => false,
                'message' => 'You have already completed this track.',
            ], 422);
        }

        $existingOpen = ListenSession::where('assignment_id', $assignment->id)
            ->where('status', ListenSession::STATUS_OPEN)
            ->first();

        if ($existingOpen) {
            return response()->json([
                'success' => true,
                'session' => $this->sessionPayload($existingOpen, $campaign),
            ]);
        }

        $minSeconds = (int) (AppSetting::get('listen_min_seconds', 30) ?? 30);

        $session = ListenSession::create([
            'listener_id' => $user->id,
            'assignment_id' => $assignment->id,
            'min_duration_seconds' => $minSeconds,
            'elapsed_seconds' => 0,
            'checkpoints' => [],
            'foreground' => true,
            'status' => ListenSession::STATUS_OPEN,
            'started_at' => now(),
        ]);

        $assignment->update(['status' => CampaignAssignment::STATUS_LISTENING]);

        return response()->json([
            'success' => true,
            'session' => $this->sessionPayload($session, $campaign),
        ], 201);
    }

    public function checkpoint(Request $request, ListenSession $session)
    {
        $user = $request->user();

        if ($session->listener_id !== $user->id) {
            return response()->json(['success' => false, 'message' => 'Forbidden.'], 403);
        }

        if (! $session->isOpen()) {
            return response()->json(['success' => false, 'message' => 'Session is not open.'], 422);
        }

        $validator = Validator::make($request->all(), [
            'elapsed_seconds' => 'required|integer|min:1|max:86400',
            'foreground' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $elapsed = (int) $request->input('elapsed_seconds', 0);
        $checkpoints = $session->checkpoints ?? [];
        $checkpoints[] = [
            'at' => now()->toIso8601String(),
            'elapsed' => $elapsed,
            'foreground' => $request->boolean('foreground', true),
        ];

        $session->update([
            'elapsed_seconds' => $elapsed,
            'checkpoints' => $checkpoints,
            'foreground' => $request->boolean('foreground', true),
        ]);

        return response()->json([
            'success' => true,
            'can_complete' => $session->hasMetDuration(),
            'requires_checkpoint' => $elapsed >= 30 && count($checkpoints) < 2,
            'session' => $this->sessionPayload($session, $session->assignment?->campaign),
        ]);
    }

    public function complete(Request $request, ListenSession $session, FraudEngine $fraud)
    {
        $user = $request->user();

        if ($session->listener_id !== $user->id) {
            return response()->json(['success' => false, 'message' => 'Forbidden.'], 403);
        }

        if (! $session->isOpen()) {
            return response()->json([
                'success' => false,
                'message' => 'This session has already been completed or closed.',
            ], 422);
        }

        if (! $session->hasMetDuration()) {
            return response()->json([
                'success' => false,
                'message' => 'Please finish listening before submitting your completion.',
                'elapsed_seconds' => $session->elapsed_seconds,
                'min_duration_seconds' => $session->min_duration_seconds,
            ], 422);
        }

        $campaign = $session->assignment->campaign;

        $session->update([
            'status' => ListenSession::STATUS_REWARDED,
            'completed_at' => now(),
        ]);

        $sessionFlags = $fraud->listenSessionRisk($session->fresh());

        $session->assignment->update(['status' => CampaignAssignment::STATUS_REVIEWED, 'reviewed_at' => now()]);

        $dailyLimitHit = $fraud->hitDailyLimit($user, 50);

        if ($dailyLimitHit || ! empty($sessionFlags)) {
            $session->update(['status' => ListenSession::STATUS_FRAUD]);

            return response()->json([
                'success' => false,
                'message' => 'Session flagged by our quality checks. No reward will be credited.',
            ], 422);
        }

        $fraud->incrementDailyCount($user);

        RewardSessionJob::dispatch($session->id);

        return response()->json([
            'success' => true,
            'message' => 'Session complete. Your reward will be credited shortly.',
            'session' => [
                'id' => $session->id,
                'status' => ListenSession::STATUS_REWARDED,
                'reward' => (int) $campaign->reward_per_review,
            ],
        ], 201);
    }

    private function sessionPayload(ListenSession $session, ?PromoCampaign $campaign): array
    {
        return [
            'id' => $session->id,
            'session_token' => $session->session_token,
            'status' => $session->status,
            'min_duration_seconds' => $session->min_duration_seconds,
            'elapsed_seconds' => $session->elapsed_seconds,
            'can_complete' => $session->hasMetDuration(),
            'campaign' => $campaign ? [
                'id' => $campaign->id,
                'title' => $campaign->title,
                'track_url' => $campaign->track_url,
                'platform' => $campaign->platform,
                'artist' => $campaign->artist?->name,
                'reward_per_review' => (int) $campaign->reward_per_review,
            ] : null,
        ];
    }
}