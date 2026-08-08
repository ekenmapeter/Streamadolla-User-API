<?php

namespace Database\Seeders;

use App\Models\AppSetting;
use App\Models\CampaignPackage;
use Illuminate\Database\Seeder;

class AudioReachSeeder extends Seeder
{
    public function run(): void
    {
        $packages = [
            ['name' => 'Starter', 'price_ngn' => 30000, 'listen_target' => 100, 'review_target' => 100, 'margin_pct' => 35],
            ['name' => 'Growth', 'price_ngn' => 120000, 'listen_target' => 500, 'review_target' => 500, 'margin_pct' => 38],
            ['name' => 'Pro', 'price_ngn' => 300000, 'listen_target' => 1500, 'review_target' => 1500, 'margin_pct' => 40],
            ['name' => 'Label / Custom', 'price_ngn' => 750000, 'listen_target' => 5000, 'review_target' => 5000, 'margin_pct' => 43],
        ];

        foreach ($packages as $package) {
            CampaignPackage::updateOrCreate(
                ['name' => $package['name']],
                $package
            );
        }

        $settings = [
            ['listen_min_seconds', 30, 'rewards', 'Minimum listen duration before a session can be completed'],
            ['reward_per_review', 150, 'rewards', 'Default reward paid per verified listen (override per-campaign)'],
            ['min_payout', 1000, 'rewards', 'Minimum wallet balance required to request a payout'],
            ['payout_hold_hours', 72, 'rewards', 'Holding period before payouts can be processed'],
            ['daily_review_limit', 50, 'limits', 'Max paid listens a listener can complete per day'],
            ['maintenance_mode', false, 'general', 'Disables listening when enabled'],
            ['app_version', '1.0.0', 'app', 'Current app release version'],
            ['min_app_version', '1.0.0', 'app', 'Minimum app version the server will accept'],
        ];

        foreach ($settings as [$key, $value, $group, $description]) {
            AppSetting::updateOrCreate(
                ['key' => $key],
                ['value' => $value, 'group' => $group, 'description' => $description]
            );
        }
    }
}