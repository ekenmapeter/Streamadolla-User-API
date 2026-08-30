<?php

namespace App\Console\Commands;

use App\Models\CampaignAssignment;
use App\Models\UserDevice;
use App\Services\FirebaseMessagingService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class PushAutoplayTracks extends Command
{
    protected $signature = 'autoplay:push {--limit=200 : Max push commands per run}';

    protected $description = 'Push play commands to listeners in Free Move mode with pending assignments.';

    public function handle(FirebaseMessagingService $messaging): int
    {
        $limit = (int) $this->option('limit');
        $sent = 0;

        $devices = UserDevice::where('free_move', true)
            ->whereNotNull('fcm_token')
            ->whereHas('user', fn ($q) => $q->where('status', 'active'))
            ->with('user')
            ->limit($limit)
            ->get();

        foreach ($devices as $device) {
            $assignment = CampaignAssignment::with('campaign')
                ->where('listener_id', $device->user_id)
                ->where('status', CampaignAssignment::STATUS_ASSIGNED)
                ->whereHas('campaign', fn ($q) => $q->active())
                ->orderBy('assigned_at')
                ->first();

            if (! $assignment || ! $assignment->campaign) {
                continue;
            }

            $campaign = $assignment->campaign;
            $command = $messaging->platformCommand($campaign->platform);

            $ok = $messaging->sendToToken($device->fcm_token, [
                'command' => $command,
                'platform' => (string) $campaign->platform,
                'action' => 'play',
                'media_url' => (string) $campaign->track_url,
                'campaign_id' => (string) $campaign->id,
                'assignment_id' => (string) $assignment->id,
                'title' => (string) $campaign->title,
            ]);

            if ($ok) {
                $assignment->update(['status' => CampaignAssignment::STATUS_LISTENING]);
                $sent++;
                $this->line("  ✓ Pushed campaign #{$campaign->id} to listener #{$device->user_id}");
            }
        }

        $this->info("[{$this->getName()}] Sent {$sent} autoplay command(s).");
        Log::info('Autoplay push complete', ['sent' => $sent]);

        return 0;
    }
}