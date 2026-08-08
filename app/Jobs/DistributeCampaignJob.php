<?php

namespace App\Jobs;

use App\Models\CampaignAssignment;
use App\Models\ListenerProfile;
use App\Models\PromoCampaign;
use App\Models\User;
use App\Services\FirebaseMessagingService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DistributeCampaignJob implements ShouldQueue
{
    use Queueable;

    public int $timeout = 300;

    public function __construct(public int $campaignId)
    {
    }

    public function handle(): void
    {
        $campaign = PromoCampaign::with('artist')->find($this->campaignId);

        if (! $campaign || ! $campaign->isActive()) {
            return;
        }

        $genres = $campaign->genres ?? [];
        $target = $campaign->listen_target > 0 ? $campaign->listen_target : 100;
        $tokens = [];

        $eligibleQuery = User::where('role', User::ROLE_LISTENER)
            ->where('status', 'active')
            ->whereNotNull('email_verified_at')
            ->whereDoesntHave('campaignAssignments', fn ($q) => $q->where('campaign_id', $campaign->id));

        $listeners = $eligibleQuery
            ->whereHas('listenerProfile', fn ($q) => $q->where('trust_level', '>=', 0))
            ->limit($target)
            ->get();

        $count = 0;

        DB::transaction(function () use ($listeners, $campaign, &$count) {
            foreach ($listeners as $listener) {
                $prefs = $listener->listenerProfile?->genre_prefs ?? [];

                if (! empty($genres) && ! empty($prefs) && ! array_intersect($genres, $prefs)) {
                    continue;
                }

                CampaignAssignment::firstOrCreate(
                    ['campaign_id' => $campaign->id, 'listener_id' => $listener->id],
                    ['status' => CampaignAssignment::STATUS_ASSIGNED]
                );

                $count++;
                $fcm = $listener->userDevices()->whereNotNull('fcm_token')->first();
                if ($fcm?->fcm_token) {
                    $tokens[] = $fcm->fcm_token;
                }
            }
        });

        Log::info('Campaign distributed', [
            'campaign_id' => $campaign->id,
            'assignments_created' => $count,
        ]);

        if (! empty($tokens)) {
            try {
                $messaging = app(FirebaseMessagingService::class);
                $messaging->sendMulticast($tokens, [
                    'command' => 'campaign_assigned',
                    'campaign_id' => (string) $campaign->id,
                    'title' => $campaign->title,
                ]);
            } catch (\Throwable $e) {
                Log::warning('Campaign distribution FCM push failed', ['error' => $e->getMessage()]);
            }
        }
    }
}