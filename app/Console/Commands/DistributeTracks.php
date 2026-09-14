<?php

namespace App\Console\Commands;

use App\Models\Device;
use App\Models\DeviceAssignment;
use App\Models\DeviceRental;
use App\Models\StreamPlay;
use App\Models\Track;
use App\Services\FirebaseMessagingService;
use App\Jobs\RentalRewardJob;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DistributeTracks extends Command
{
    protected $signature = 'tracks:distribute {--limit=50 : Max new assignments per run}';

    protected $description = 'Push live catalog tracks to rented devices and finalize completed plays.';

    public function handle(FirebaseMessagingService $messaging): int
    {
        $limit = (int) $this->option('limit');

        // ── 1. Finalize completed plays ──────────────────────────────────
        $finished = DeviceAssignment::with(['device', 'track'])
            ->whereNotNull('track_id')
            ->where('status', 'playing')
            ->whereNotNull('started_at')
            ->get();

        $finalized = 0;

        foreach ($finished as $assignment) {
            $duration = max(30, (int) ($assignment->track?->duration_seconds ?? 180));
            $elapsed = max(0, now()->timestamp - $assignment->started_at->timestamp);

            if ($elapsed < $duration + 2) {
                continue;
            }

            $assignment->update(['status' => 'completed']);

            if ($assignment->device?->fcm_token) {
                $messaging->sendToToken($assignment->device->fcm_token, [
                    'command' => 'stop',
                    'action' => 'stop',
                    'assignment_id' => (string) $assignment->id,
                ]);
            }

            if ($assignment->track_id && $assignment->device?->user_id) {
                RentalRewardJob::dispatch($assignment->id);
            }

            $finalized++;
        }

        if ($finalized > 0) {
            $this->info("  ✓ Finalized {$finalized} completed track play(s).");
            Log::info('tracks:distribute finalized', ['count' => $finalized]);
        }

        // ── 2. Assign next tracks to idle rented devices ────────────────
        $rentals = DeviceRental::active()
            ->with('device')
            ->whereHas('device', fn ($q) => $q->whereNotNull('fcm_token'))
            ->get()
            ->filter(fn (DeviceRental $rental) => $rental->device
                && ! $rental->device->assignments()
                    ->whereIn('status', ['pending', 'playing', 'paused'])
                    ->exists());

        if ($rentals->isEmpty()) {
            $this->info('No idle rented devices.');

            return 0;
        }

        $tracks = Track::live()
            ->whereColumn('streams_served', '<', 'stream_budget')
            ->orderBy('streams_served')
            ->orderBy('id')
            ->get();

        if ($tracks->isEmpty()) {
            $this->info('No live tracks with budget remaining.');

            return 0;
        }

        $assigned = 0;

        foreach ($rentals as $rental) {
            if ($assigned >= $limit || $tracks->isEmpty()) {
                break;
            }

            /** @var Device $device */
            $device = $rental->device;
            $track = $tracks->shift();

            $assignment = DeviceAssignment::create([
                'device_id' => $device->id,
                'track_id' => $track->id,
                'platform' => $track->platform,
                'media_url' => $track->media_url,
                'media_title' => $track->title,
                'status' => 'pending',
                'assigned_at' => now(),
            ]);

            $ok = $messaging->sendToToken($device->fcm_token, [
                'command' => $messaging->platformCommand($track->platform),
                'track_id' => $track->media_url,
                'youtube_url' => $track->media_url,
                'apple_music_url' => $track->media_url,
                'media_url' => $track->media_url,
                'duration_seconds' => (string) $track->duration_seconds,
                'title' => $track->title,
                'action' => 'play',
                'platform' => $track->platform,
                'assignment_id' => (string) $assignment->id,
            ]);

            if ($ok) {
                $assignment->update(['status' => 'playing', 'started_at' => now()]);
                $device->update(['status' => 'streaming', 'last_seen' => now()]);
                $assigned++;
                $this->line("  ✓ Assigned '{$track->title}' to rental #{$rental->id}");
            } else {
                $assignment->update(['status' => 'failed']);
            }
        }

        $this->info("[{$this->getName()}] Assigned {$assigned} track(s) to rented devices.");
        Log::info('tracks:distribute assigned', ['count' => $assigned]);

        return 0;
    }
}