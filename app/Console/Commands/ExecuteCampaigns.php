<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\DeviceAssignment;
use App\Models\CampaignTrack;
use App\Models\DeviceLog;
use Illuminate\Support\Str;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Factory;

class ExecuteCampaigns extends Command
{
    protected $signature = 'campaigns:execute {--force : Skip duration checks and advance immediately}';
    protected $description = 'Advance campaign tracks on devices. Loops internally every 60s to work within a 5-min cron limit.';

    /**
     * How long (seconds) the worker stays alive per cron invocation.
     * Since we will move to a 1-minute cron, we can run once and exit.
     */
    private int $maxRuntime = 55;

    public function handle(): int
    {
        $force     = $this->option('force');
        $messaging = $this->getMessaging();

        if (!$messaging) {
            $this->error("CRITICAL: Firebase Messaging not initialized. Check credentials.");
            return 1;
        }

        Log::info("ExecuteCampaigns: Worker started.");
        $this->info("[" . now()->toDateTimeString() . "] Worker started.");

        // Fetch all active playing assignments
        $activeAssignments = DeviceAssignment::with(['device', 'campaign.tracks', 'campaignTrack'])
            ->whereNotNull('campaign_id')
            ->whereNotNull('campaign_track_id')
            ->where('status', 'playing')
            ->whereNotNull('started_at')
            ->get();

        $count = $activeAssignments->count();
        $countMsg = "Found {$count} active playing assignment(s).";
        $this->line("  " . $countMsg);
        Log::info("ExecuteCampaigns: " . $countMsg);

        $advanced = 0;
        foreach ($activeAssignments as $assignment) {
            if ($this->processSingleAssignment($assignment, $messaging, $force)) {
                $advanced++;
            }
        }

        if ($advanced > 0) {
            $this->info("  ✓ Advanced {$advanced} device(s).");
            Log::info("ExecuteCampaigns: Advanced {$advanced} device(s).");
        } else {
            $this->comment("  No tracks advanced.");
        }

        return 0;
    }

    /**
     * Process a single assignment: check timing and advance the track if due.
     * Returns true if the track was advanced, false otherwise.
     */
    private function processSingleAssignment(
        DeviceAssignment $assignment,
        mixed $messaging,
        bool $force
    ): bool {
        DB::beginTransaction();
        try {
            $assignment->refresh();
            $campaign = $assignment->campaign;
            $device   = $assignment->device;

            if (!$campaign || !$device || !$device->fcm_token) {
                $msg = "Asgn #{$assignment->id}: Skipping — missing device, FCM token, or campaign.";
                $this->warn("  " . $msg);
                Log::info($msg);
                DB::rollBack();
                return false;
            }

            // Load tracks ordered by position
            $tracks = $campaign->tracks()->orderBy('position_order')->get();
            if ($tracks->isEmpty()) {
                $msg = "Asgn #{$assignment->id}: Campaign has no tracks.";
                $this->warn("  " . $msg);
                Log::info($msg);
                DB::rollBack();
                return false;
            }

            // ── Track Selection ──────────────────────────────────────────
            // Play all tracks on each phone randomly (deterministic shuffle based on assignment)
            $assignedAt = $assignment->assigned_at ?? $assignment->created_at ?? now();
            $seed = "{$assignment->device_id}_{$assignment->campaign_id}_" . (int)$assignedAt->timestamp;
            $shuffledTracks = $tracks->sortBy(fn($t) => md5($seed . $t->id))->values();
            
            $trackCount = $shuffledTracks->count();

            // Find the current track's position in the active list
            $currentIndex = $shuffledTracks->search(function ($t) use ($assignment) {
                return ($assignment->campaign_track_id && (int)$t->id === (int)$assignment->campaign_track_id)
                    || $t->media_url === $assignment->media_url;
            });

            if ($currentIndex === false) {
                $msg = "Asgn #{$assignment->id}: Track position lost — resetting to first track.";
                $this->warn("  " . $msg);
                Log::info($msg);
                $currentIndex = 0;
            }

            // ── Timing check ────────────────────────────────────────────
            $startedAtTs   = ($assignment->started_at ?? $assignment->created_at ?? now())->timestamp;
            $nowTs         = time();
            $playedSeconds = max(0, $nowTs - $startedAtTs);

            $track     = $shuffledTracks->get($currentIndex);
            $duration  = $track ? (int)($track->duration_seconds ?? 180) : 180;
            $threshold = $duration + 2; // +2s grace for scheduling jitter
            $remaining = max(0, $threshold - $playedSeconds);
            $startedAtStr = date('Y-m-d H:i:s', $startedAtTs);

            $statusMsg = "Asgn #{$assignment->id}: Played {$playedSeconds}s / {$duration}s"
                . " | Remaining: {$remaining}s"
                . " | Start(UTC): {$startedAtStr}"
                . " | Track: " . basename($assignment->media_url ?? 'unknown');
            $this->line("  " . $statusMsg);
            Log::info($statusMsg);

            if (!$force && $playedSeconds < $threshold) {
                DB::rollBack();
                return false;
            }
            // ────────────────────────────────────────────────────────────

            // Determine next track (wraps back to 0 at the end)
            $nextIndex = ($currentIndex < $trackCount - 1) ? $currentIndex + 1 : 0;
            $nextTrack = $shuffledTracks[$nextIndex];

            $advanceMsg = "Asgn #{$assignment->id}: >>> ADVANCING to Track #" . ($nextIndex + 1) . ": {$nextTrack->media_url}";
            $this->info("  " . $advanceMsg);
            Log::info($advanceMsg);

            // Build FCM command
            $command = $campaign->platform === 'spotify' ? 'play_spotify' : 'play_youtube';

            $message = CloudMessage::withTarget('token', $device->fcm_token)
                ->withData([
                    'command'          => $command,
                    'track_id'         => (string)$nextTrack->media_url,
                    'youtube_url'      => (string)$nextTrack->media_url,
                    'media_url'        => (string)$nextTrack->media_url,
                    'duration_seconds' => (string)($nextTrack->duration_seconds ?? 180),
                    'action'           => 'play',
                    'platform'         => (string)$campaign->platform,
                    'assignment_id'    => (string)$assignment->id,
                    'timestamp'        => (string)time(),
                    'command_id'       => Str::uuid()->toString(),
                ])
                ->withAndroidConfig(['priority' => 'high', 'ttl' => '3600s'])
                ->withApnsConfig([
                    'headers' => ['apns-priority' => '10', 'apns-push-type' => 'background'],
                    'payload' => ['aps' => ['content-available' => 1]],
                ]);

            // Send via Firebase — isolated catch so a send failure only
            // rolls back this assignment, not the whole batch.
            try {
                $messaging->send($message);
                Log::info("Asgn #{$assignment->id}: FCM command sent successfully.");
            } catch (\Kreait\Firebase\Exception\MessagingException $e) {
                $errMsg = "Asgn #{$assignment->id}: Firebase send FAILED — " . $e->getMessage();
                Log::error($errMsg);
                $this->error("  " . $errMsg);
                DB::rollBack();
                return false;
            }

            // Update the assignment to record the new current track
            $assignment->update([
                'campaign_track_id' => (int)$nextTrack->id,
                'media_url'         => (string)$nextTrack->media_url,
                'media_title'       => (string)($nextTrack->media_title ?? $campaign->name . ' - Track ' . ($nextIndex + 1)),
                'started_at'        => now(),
            ]);

            $device->update(['last_seen' => now()]);

            DB::commit();
            $nextTrackNum = $nextIndex + 1;
            Log::info("Asgn #{$assignment->id}: DB updated — now on track #{$nextTrackNum}.");
            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            $errMsg = "Asgn #{$assignment->id} EXCEPTION: " . $e->getMessage();
            $this->error("  " . $errMsg);
            Log::error($errMsg);
            return false;
        }
    }

    /**
     * Resolve a Firebase Messaging instance from the service-account JSON.
     */
    private function getMessaging(): mixed
    {
        $credentialsPath = base_path(config('firebase.credentials'));

        if (!file_exists($credentialsPath)) {
            $storagePath = storage_path('app');
            foreach (glob($storagePath . '/*.json') as $file) {
                if (str_contains($file, 'firebase-adminsdk')) {
                    $credentialsPath = $file;
                    break;
                }
            }
        }

        if (!file_exists($credentialsPath)) {
            return null;
        }

        try {
            return (new Factory)->withServiceAccount($credentialsPath)->createMessaging();
        } catch (\Exception $e) {
            Log::error("ExecuteCampaigns: getMessaging() failed — " . $e->getMessage());
            return null;
        }
    }
}
