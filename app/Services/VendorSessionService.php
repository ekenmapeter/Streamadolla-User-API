<?php

namespace App\Services;

use App\Jobs\VendorRewardJob;
use App\Models\AppSetting;
use App\Models\Device;
use App\Models\DeviceSession;
use Illuminate\Support\Facades\Log;

class VendorSessionService
{
    /**
     * Finish one open vendor session and credit it if it passed the gates.
     * Called before a campaign assignment advances to the next track, so the
     * old track is paid even if the phone never sent /complete.
     */
    public function completeSession(DeviceSession $session, ?Device $device = null): bool
    {
        if ($session->status !== DeviceSession::STATUS_OPEN) {
            return false;
        }

        $device ??= $session->device;

        if (! $device || ! $session->started_at) {
            $session->update([
                'status' => DeviceSession::STATUS_ABANDONED,
                'completed_at' => now(),
            ]);

            return false;
        }

        $elapsed = max(0, now()->timestamp - $session->started_at->timestamp);

        if ($elapsed < $session->min_duration_seconds) {
            $session->update([
                'elapsed_seconds' => $elapsed,
                'status' => DeviceSession::STATUS_ABANDONED,
                'completed_at' => now(),
            ]);

            return false;
        }

        $fraud = app(FraudEngine::class);
        $maxPerDay = (int) (AppSetting::get('vendor_max_streams_per_device_per_day', 50) ?? 50);

        if ($fraud->hitDeviceDailyLimit($device->id, $maxPerDay)) {
            $session->update([
                'elapsed_seconds' => $elapsed,
                'status' => DeviceSession::STATUS_FRAUD,
                'completed_at' => now(),
            ]);

            return false;
        }

        $checkpoints = $session->checkpoints ?? [];
        $checkpoints[] = [
            'at' => now()->toIso8601String(),
            'elapsed' => $elapsed,
            'source' => 'server_track_advance',
        ];

        $session->update([
            'elapsed_seconds' => $elapsed,
            'checkpoints' => $checkpoints,
            'status' => DeviceSession::STATUS_REWARDED,
            'completed_at' => now(),
        ]);

        $fraud->incrementDeviceDailyCount($device->id);

        VendorRewardJob::dispatchSync($session->id);

        Log::info('Vendor session completed on track advance', [
            'device_session_id' => $session->id,
            'assignment_id' => $session->device_assignment_id,
            'device_id' => $device->id,
            'elapsed_seconds' => $elapsed,
        ]);

        return true;
    }
}
