<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\AppSetting;
use App\Models\DeviceAssignment;
use App\Models\DeviceSession;
use App\Models\User;
use App\Services\FraudEngine;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * Vendor device streaming sessions. Mirrors the listener ListenController
 * flow (start → checkpoint → complete) with the same anti-fraud gates, so a
 * device cannot earn unless it actually played the track long enough.
 */
class VendorSessionController extends Controller
{
    public function start(Request $request, DeviceAssignment $assignment)
    {
        $user = $request->user();
        $device = $assignment->device;

        if (! $device || ! $user->isVendor() || $device->user_id !== $user->id) {
            return response()->json(['success' => false, 'message' => 'Forbidden.'], 403);
        }

        if (! in_array($assignment->status, ['pending', 'playing', 'paused'])) {
            return response()->json([
                'success' => false,
                'message' => 'Assignment is not playable.',
            ], 422);
        }

        $existing = DeviceSession::where('device_assignment_id', $assignment->id)
            ->where('status', DeviceSession::STATUS_OPEN)
            ->first();

        if ($existing) {
            return response()->json([
                'success' => true,
                'session' => $this->sessionPayload($existing),
            ]);
        }

        // A fresh track was assigned – any previous streams on this phone
        // are abandoned (no double-earn across track transitions).
        DeviceSession::where('device_id', $device->id)
            ->where('status', DeviceSession::STATUS_OPEN)
            ->update(['status' => DeviceSession::STATUS_ABANDONED]);

        $minSeconds = (int) (AppSetting::get('vendor_min_seconds', 30) ?? 30);

        $session = DeviceSession::create([
            'vendor_id' => $user->id,
            'device_id' => $device->id,
            'device_assignment_id' => $assignment->id,
            'min_duration_seconds' => $minSeconds,
            'elapsed_seconds' => 0,
            'checkpoints' => [],
            'status' => DeviceSession::STATUS_OPEN,
            'started_at' => now(),
        ]);

        if (! $assignment->started_at) {
            $assignment->update(['status' => 'playing', 'started_at' => now()]);
        } else {
            $assignment->update(['status' => 'playing']);
        }

        $device->update(['status' => 'streaming', 'last_seen' => now()]);

        return response()->json([
            'success' => true,
            'session' => $this->sessionPayload($session),
        ], 201);
    }

    public function checkpoint(Request $request, DeviceAssignment $assignment)
    {
        $user = $request->user();
        $session = $this->openSessionFor($user, $assignment);

        if (! $session) {
            return response()->json(['success' => false, 'message' => 'Forbidden or session not open.'], 403);
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
        ];

        $session->update([
            'elapsed_seconds' => $elapsed,
            'checkpoints' => $checkpoints,
        ]);

        $session->device()->update(['last_seen' => now()]);

        return response()->json([
            'success' => true,
            'can_complete' => $session->hasMetDuration(),
            'requires_checkpoint' => $elapsed >= 30 && count($checkpoints) < 2,
            'session' => $this->sessionPayload($session),
        ]);
    }

    public function complete(Request $request, DeviceAssignment $assignment, FraudEngine $fraud)
    {
        $user = $request->user();
        $device = $assignment->device;
        $session = $this->openSessionFor($user, $assignment);

        if (! $session) {
            return response()->json(['success' => false, 'message' => 'Forbidden or session not open.'], 403);
        }

        if (! $session->hasMetDuration()) {
            return response()->json([
                'success' => false,
                'message' => 'Please finish streaming before submitting your completion.',
                'elapsed_seconds' => $session->elapsed_seconds,
                'min_duration_seconds' => $session->min_duration_seconds,
            ], 422);
        }

        // Stale guard: if a new track replaced this assignment after the
        // session started, ignore the completion (mirrors the rentals route).
        if ($assignment->started_at && $session->started_at
            && $assignment->started_at->gt($session->started_at->addSeconds(10))) {
            return response()->json([
                'success' => true,
                'message' => 'Ignored stale completion due to recent track transition.',
            ]);
        }

        $session->update([
            'status' => DeviceSession::STATUS_REWARDED,
            'completed_at' => now(),
        ]);

        // Completing a vendor stream is a reward milestone, not the end of a
        // campaign. Keep campaign assignments playing so the campaign worker
        // can advance to the next track; only non-campaign (track-farm)
        // assignments are marked completed here.
        $assignment->update([
            'status' => $assignment->campaign_id ? 'playing' : 'completed',
        ]);
        $device->update(['last_seen' => now()]);

        $sessionFlags = $fraud->deviceSessionRisk($session->fresh());

        $maxPerDay = (int) (AppSetting::get('vendor_max_streams_per_device_per_day', 50) ?? 50);
        $dailyLimitHit = $fraud->hitDeviceDailyLimit($session->device_id, $maxPerDay);

        if ($dailyLimitHit || ! empty($sessionFlags)) {
            $session->update(['status' => DeviceSession::STATUS_FRAUD]);

            return response()->json([
                'success' => false,
                'message' => 'Session flagged by our quality checks. No reward will be credited.',
                'flags' => $sessionFlags,
            ], 422);
        }

        $fraud->incrementDeviceDailyCount($session->device_id);

        \App\Jobs\VendorRewardJob::dispatch($session->id);

        return response()->json([
            'success' => true,
            'message' => 'Stream complete. Your reward will be credited shortly.',
            'session' => [
                'id' => $session->id,
                'status' => DeviceSession::STATUS_REWARDED,
            ],
        ], 201);
    }

    public function control(Request $request, DeviceAssignment $assignment)
    {
        $user = $request->user();
        $device = $assignment->device;

        if (! $device || ! $user->isVendor() || $device->user_id !== $user->id) {
            return response()->json(['success' => false, 'message' => 'Forbidden.'], 403);
        }

        $validator = Validator::make($request->all(), [
            'status' => 'required|in:paused,stopped,failed',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $status = $request->status;

        $assignment->update(['status' => $status]);

        $device->update([
            'status' => $status === 'paused' ? 'streaming' : 'online',
            'last_seen' => now(),
        ]);

        if (in_array($status, ['stopped', 'failed'])) {
            DeviceSession::where('device_assignment_id', $assignment->id)
                ->where('status', DeviceSession::STATUS_OPEN)
                ->update(['status' => DeviceSession::STATUS_ABANDONED]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Assignment status updated.',
        ]);
    }

    private function openSessionFor(User $user, DeviceAssignment $assignment): ?DeviceSession
    {
        $device = $assignment->device;

        if (! $device || ! $user->isVendor() || $device->user_id !== $user->id) {
            return null;
        }

        return DeviceSession::where('device_assignment_id', $assignment->id)
            ->where('status', DeviceSession::STATUS_OPEN)
            ->first();
    }

    private function sessionPayload(DeviceSession $session): array
    {
        return [
            'id' => $session->id,
            'status' => $session->status,
            'min_duration_seconds' => $session->min_duration_seconds,
            'elapsed_seconds' => $session->elapsed_seconds,
            'can_complete' => $session->hasMetDuration(),
            'started_at' => $session->started_at?->toIso8601String(),
        ];
    }
}