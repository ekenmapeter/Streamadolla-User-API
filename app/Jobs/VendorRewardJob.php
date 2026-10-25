<?php

namespace App\Jobs;

use App\Models\AppSetting;
use App\Models\DeviceSession;
use App\Models\StreamPlay;
use App\Models\Track;
use App\Models\WalletTransaction;
use App\Services\WalletService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Mirrors RentalRewardJob for vendor-owned devices. Pays the vendor's
 * wallet once per completed, gate-passing vendor session. Campaign
 * assignments span many tracks, so each session is paid separately while
 * duplicate job runs for the same session are ignored.
 */
class VendorRewardJob implements ShouldQueue
{
    use Queueable;

    public int $timeout = 120;

    public function __construct(public int $deviceSessionId)
    {
    }

    public function handle(WalletService $wallet): void
    {
        $session = DeviceSession::with(['device.user', 'assignment.track'])
            ->find($this->deviceSessionId);

        if (! $session) {
            return;
        }

        if ($session->status !== DeviceSession::STATUS_REWARDED || ! $session->device?->user_id) {
            return;
        }

        if (StreamPlay::where('device_session_id', $session->id)->exists()) {
            return;
        }

        // Legacy non-campaign (track-farm) assignments represent one track,
        // so keep the one-reward-per-assignment guard for those.
        if (! $session->assignment?->campaign_id && StreamPlay::existsForAssignment($session->device_assignment_id)) {
            return;
        }

        $user = $session->device->user;

        if (! $user->isVendor()) {
            return;
        }

        $reward = (int) (AppSetting::get('vendor_rate_per_stream', 5) ?? 5);

        if ($reward <= 0) {
            return;
        }

        $assignment = $session->assignment;

        $play = DB::transaction(function () use ($session, $assignment, $user, $reward, $wallet) {
            $play = StreamPlay::create([
                'user_id' => $user->id,
                'rental_id' => null,
                'device_assignment_id' => $session->device_assignment_id,
                'device_session_id' => $session->id,
                'track_id' => $assignment?->track_id,
                'platform' => $assignment?->platform,
                'media_url' => $assignment?->media_url,
                'media_title' => $assignment?->media_title,
                'reward_amount' => $reward,
                'status' => StreamPlay::STATUS_REWARDED,
            ]);

            $wallet->credit($user, $reward, WalletTransaction::TYPE_VENDOR, [
                'device_session_id' => $session->id,
                'assignment_id' => $session->device_assignment_id,
                'stream_play_id' => $play->id,
                'track_id' => $assignment?->track_id,
                'device_id' => $session->device_id,
            ]);

            $session->device()->increment('total_streams');
            $session->device()->increment('total_earned', $reward);

            if ($assignment?->track_id) {
                Track::where('id', $assignment->track_id)->increment('streams_served');
            }

            return $play;
        });

        if ($assignment?->track_id) {
            Track::where('id', $assignment->track_id)
                ->where('status', Track::STATUS_LIVE)
                ->whereColumn('streams_served', '>=', 'stream_budget')
                ->update(['status' => Track::STATUS_EXHAUSTED]);
        }

        Log::info('Vendor stream rewarded', [
            'device_session_id' => $session->id,
            'stream_play_id' => $play->id,
            'vendor_id' => $user->id,
            'device_id' => $session->device_id,
            'track_id' => $assignment?->track_id,
            'reward' => $reward,
        ]);
    }
}