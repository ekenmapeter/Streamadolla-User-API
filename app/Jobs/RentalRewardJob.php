<?php

namespace App\Jobs;

use App\Models\DeviceAssignment;
use App\Models\DeviceRental;
use App\Models\StreamPlay;
use App\Models\Track;
use App\Models\WalletTransaction;
use App\Services\WalletService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RentalRewardJob implements ShouldQueue
{
    use Queueable;

    public int $timeout = 120;

    public function __construct(public int $assignmentId)
    {
    }

    public function handle(WalletService $wallet): void
    {
        $assignment = DeviceAssignment::with(['device.user', 'track'])
            ->find($this->assignmentId);

        if (! $assignment) {
            return;
        }

        if ($assignment->status !== 'completed' || ! $assignment->track_id || ! $assignment->device?->user_id) {
            return;
        }

        if (StreamPlay::existsForAssignment($assignment->id)) {
            return;
        }

        $rental = DeviceRental::active()
            ->where('user_id', $assignment->device->user_id)
            ->where('device_id', $assignment->device_id)
            ->first();

        if (! $rental) {
            return;
        }

        $user = $assignment->device->user;
        $reward = (int) $rental->rate_per_stream;

        if ($reward <= 0) {
            return;
        }

        $play = DB::transaction(function () use ($assignment, $rental, $user, $reward, $wallet) {
            $play = StreamPlay::create([
                'user_id' => $user->id,
                'rental_id' => $rental->id,
                'device_assignment_id' => $assignment->id,
                'track_id' => $assignment->track_id,
                'platform' => $assignment->platform,
                'media_url' => $assignment->media_url,
                'reward_amount' => $reward,
                'status' => StreamPlay::STATUS_REWARDED,
            ]);

            $transaction = $wallet->credit($user, $reward, WalletTransaction::TYPE_RENTAL, [
                'assignment_id' => $assignment->id,
                'stream_play_id' => $play->id,
                'track_id' => $assignment->track_id,
                'rental_id' => $rental->id,
            ]);

            $rental->increment('total_streams');
            $rental->increment('total_earned', $reward);

            Track::where('id', $assignment->track_id)->increment('streams_served');

            $user->listenerProfile?->addEarnings($reward);

            return $play;
        });

        Track::where('id', $assignment->track_id)
            ->where('status', Track::STATUS_LIVE)
            ->whereColumn('streams_served', '>=', 'stream_budget')
            ->update(['status' => Track::STATUS_EXHAUSTED]);

        Log::info('Rental stream rewarded', [
            'assignment_id' => $assignment->id,
            'stream_play_id' => $play->id,
            'user_id' => $user->id,
            'track_id' => $assignment->track_id,
            'reward' => $reward,
        ]);
    }
}