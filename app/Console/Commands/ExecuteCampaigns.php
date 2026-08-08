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

            $tracks = $campaign->tracks()->orderBy('position_order')->get();
            if ($tracks->isEmpty()) {
                $msg = "Asgn #{$assignment->id}: Campaign has no tracks.";
                $this->warn("  " . $msg);
                Log::info($msg);
                DB::rollBack();
                return false;
            }

            // ── Track Selection ──────────────────────────────────────────
            $assignedAt = $assignment->assigned_at ?? $assignment->created_at ?? now();
            $seed = "{$assignment->device_id}_{$assignment->campaign_id}_" . (int)$assignedAt->timestamp;
            $shuffledTracks = $tracks->sortBy(fn($t) => md5($seed . $t->id))->values();

            $trackCount = $shuffledTracks->count();
            $currentIndex = (int)($assignment->shuffled_index ?? 0);
            $isInterstitial = (bool)$assignment->is_interstitial;
            $cycleCount = (int)($assignment->cycle_track_count ?? 0);

            if ($currentIndex >= $trackCount) {
                $currentIndex = 0;
            }

            // ── Timing check ────────────────────────────────────────────
            $startedAtTs   = ($assignment->started_at ?? $assignment->created_at ?? now())->timestamp;
            $nowTs         = time();
            $playedSeconds = max(0, $nowTs - $startedAtTs);

            if ($isInterstitial) {
                $duration  = (int)($campaign->interstitial_duration_seconds ?? 120);
            } else {
                $track     = $shuffledTracks->get($currentIndex);
                $duration  = $track ? (int)($track->duration_seconds ?? 180) : 180;
            }
            $threshold = $duration + 2;
            $remaining = max(0, $threshold - $playedSeconds);
            $startedAtStr = date('Y-m-d H:i:s', $startedAtTs);

            $statusMsg = "Asgn #{$assignment->id}: Played {$playedSeconds}s / {$duration}s"
                . " | Remaining: {$remaining}s"
                . " | Start(UTC): {$startedAtStr}"
                . " | Index: {$currentIndex}"
                . ($isInterstitial ? " [INTERSTITIAL]" : "")
                . " | Cycle: {$cycleCount}"
                . " | Track: " . basename($assignment->media_url ?? 'unknown');
            $this->line("  " . $statusMsg);
            Log::info($statusMsg);

            if (!$force && $playedSeconds < $threshold) {
                DB::rollBack();
                return false;
            }

            // ── Determine next action ───────────────────────────────────
            $campaignMediaUrl = $campaign->interstitial_media_url;
            $interstitialInterval = $campaign->interstitial_every;

            if ($isInterstitial) {
                // Interstitial finished → resume playlist at next track
                $nextIndex = ($currentIndex < $trackCount - 1) ? $currentIndex + 1 : 0;
                $nextTrack = $shuffledTracks[$nextIndex];

                $advanceMsg = "Asgn #{$assignment->id}: >>> INTERSTITIAL DONE, resuming Track #" . ($nextIndex + 1) . ": {$nextTrack->media_url}";
                $this->info("  " . $advanceMsg);
                Log::info($advanceMsg);

                $command = \App\Http\Controllers\Api\AssignmentController::platformCommand($campaign->platform);

                $message = CloudMessage::withTarget('token', $device->fcm_token)
                    ->withData([
                        'command'          => $command,
                        'track_id'         => (string)$nextTrack->media_url,
                        'youtube_url'      => (string)$nextTrack->media_url,
                        'apple_music_url'  => (string)$nextTrack->media_url,
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

                $assignment->update([
                    'campaign_track_id' => (int)$nextTrack->id,
                    'shuffled_index'    => $nextIndex,
                    'cycle_track_count' => 0,
                    'is_interstitial'   => false,
                    'media_url'         => (string)$nextTrack->media_url,
                    'media_title'       => (string)($nextTrack->media_title ?? $campaign->name . ' - Track ' . ($nextIndex + 1)),
                    'started_at'        => now(),
                ]);

                $device->update(['last_seen' => now()]);

                DB::commit();
                Log::info("Asgn #{$assignment->id}: DB updated — resumed from interstitial to track #" . ($nextIndex + 1) . ".");
                return true;

            } elseif ($interstitialInterval && $campaignMediaUrl && $cycleCount >= $interstitialInterval) {
                // Play interstitial instead of next playlist track
                $advanceMsg = "Asgn #{$assignment->id}: >>> INTERSTITIAL (after {$cycleCount} tracks): {$campaignMediaUrl}";
                $this->info("  " . $advanceMsg);
                Log::info($advanceMsg);

                $command = \App\Http\Controllers\Api\AssignmentController::platformCommand($campaign->platform);

                $interstitialDuration = (int)($campaign->interstitial_duration_seconds ?? 120);

                $message = CloudMessage::withTarget('token', $device->fcm_token)
                    ->withData([
                        'command'          => $command,
                        'track_id'         => (string)$campaignMediaUrl,
                        'youtube_url'      => (string)$campaignMediaUrl,
                        'apple_music_url'  => (string)$campaignMediaUrl,
                        'media_url'        => (string)$campaignMediaUrl,
                        'duration_seconds' => (string)$interstitialDuration,
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

                try {
                    $messaging->send($message);
                    Log::info("Asgn #{$assignment->id}: FCM interstitial sent successfully.");
                } catch (\Kreait\Firebase\Exception\MessagingException $e) {
                    $errMsg = "Asgn #{$assignment->id}: Firebase send FAILED — " . $e->getMessage();
                    Log::error($errMsg);
                    $this->error("  " . $errMsg);
                    DB::rollBack();
                    return false;
                }

                $assignment->update([
                    'is_interstitial'   => true,
                    'media_url'         => (string)$campaignMediaUrl,
                    'media_title'       => 'Interstitial - ' . basename($campaignMediaUrl),
                    'started_at'        => now(),
                ]);

                $device->update(['last_seen' => now()]);

                DB::commit();
                Log::info("Asgn #{$assignment->id}: DB updated — now playing interstitial.");
                return true;

            } else {
                // Normal track advance
                $nextIndex = ($currentIndex < $trackCount - 1) ? $currentIndex + 1 : 0;
                $nextTrack = $shuffledTracks[$nextIndex];

                $advanceMsg = "Asgn #{$assignment->id}: >>> ADVANCING to Track #" . ($nextIndex + 1) . ": {$nextTrack->media_url}";
                $this->info("  " . $advanceMsg);
                Log::info($advanceMsg);

                $command = \App\Http\Controllers\Api\AssignmentController::platformCommand($campaign->platform);

                $message = CloudMessage::withTarget('token', $device->fcm_token)
                    ->withData([
                        'command'          => $command,
                        'track_id'         => (string)$nextTrack->media_url,
                        'youtube_url'      => (string)$nextTrack->media_url,
                        'apple_music_url'  => (string)$nextTrack->media_url,
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

                $assignment->update([
                    'campaign_track_id' => (int)$nextTrack->id,
                    'shuffled_index'    => $nextIndex,
                    'cycle_track_count' => $cycleCount + 1,
                    'is_interstitial'   => false,
                    'media_url'         => (string)$nextTrack->media_url,
                    'media_title'       => (string)($nextTrack->media_title ?? $campaign->name . ' - Track ' . ($nextIndex + 1)),
                    'started_at'        => now(),
                ]);

                $device->update(['last_seen' => now()]);

                DB::commit();
                $nextTrackNum = $nextIndex + 1;
                Log::info("Asgn #{$assignment->id}: DB updated — now on track #{$nextTrackNum}.");
                return true;
            }
        } catch (\Exception $e) {
            DB::rollBack();
            $errMsg = "Asgn #{$assignment->id} EXCEPTION: " . $e->getMessage();
            $this->error("  " . $errMsg);
            Log::error($errMsg);
            return false;
        }
    }

    private function getMessaging(): mixed
    {
        try {
            return app(\App\Services\FirebaseMessagingService::class)->messaging();
        } catch (\Exception $e) {
            Log::error("ExecuteCampaigns: getMessaging() failed — " . $e->getMessage());
            return null;
        }
    }
}
