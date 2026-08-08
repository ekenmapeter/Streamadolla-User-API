<?php

namespace App\Console\Commands;

use App\Models\ListenSession;
use App\Models\PromoCampaign;
use Illuminate\Console\Command;

class FinalizeCampaigns extends Command
{
    protected $signature = 'campaigns:finalize';

    protected $description = 'Mark campaigns as completed once listen targets are hit or end dates pass';

    public function handle(): int
    {
        $completed = 0;

        $campaigns = PromoCampaign::where('status', PromoCampaign::STATUS_ACTIVE)->get();

        foreach ($campaigns as $campaign) {
            $paidListens = ListenSession::where('campaign_id', $campaign->id)
                ->where('status', ListenSession::STATUS_REWARDED)
                ->count();

            $ended = $campaign->ends_at && $campaign->ends_at->isPast();

            if ($paidListens >= ($campaign->listen_target ?? $campaign->review_target) || $ended) {
                $campaign->update([
                    'status' => PromoCampaign::STATUS_COMPLETED,
                    'completed_at' => now(),
                ]);
                $completed++;
            }
        }

        $this->info("Campaign finalizer: {$completed} campaign(s) completed.");

        return self::SUCCESS;
    }
}