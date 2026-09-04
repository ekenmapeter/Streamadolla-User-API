<?php

namespace Tests\Feature;

use App\Models\CampaignAssignment;
use App\Models\CampaignPackage;
use App\Models\ListenSession;
use App\Models\PromoCampaign;
use App\Models\User;
use App\Models\UserDevice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class ListenerActivityTest extends TestCase
{
    use RefreshDatabase;

    private function registeredListenerToken(): array
    {
        $this->seed(\Database\Seeders\AudioReachSeeder::class);

        $this->postJson('/api/v1/auth/register', [
            'name' => 'Chidi Listener',
            'email' => 'listener@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertCreated();

        $user = User::where('email', 'listener@example.com')->firstOrFail();
        $entry = Cache::get('email_verify:' . $user->email);

        $response = $this->postJson('/api/v1/auth/verify-email', [
            'email' => $user->email,
            'code' => $entry['code'],
        ])->assertOk();

        return [$user, $response->json('token')];
    }

    private function artistWithCampaign(): PromoCampaign
    {
        $package = CampaignPackage::create([
            'name' => 'Test Pack',
            'price_ngn' => 30000,
            'listen_target' => 100,
            'review_target' => 50,
            'margin_pct' => 35,
        ]);

        $artist = User::create([
            'name' => 'Artist One',
            'role' => User::ROLE_ARTIST,
            'email' => 'artist-one@example.com',
            'password' => bcrypt('password123'),
        ]);

        return PromoCampaign::create([
            'artist_id' => $artist->id,
            'package_id' => $package->id,
            'title' => 'Hot Single',
            'track_url' => 'https://music.example.com/hot-single.mp3',
            'platform' => 'spotify',
            'genres' => ['afrobeats'],
            'reward_per_review' => 150,
            'review_target' => 50,
            'listen_target' => 100,
            'status' => PromoCampaign::STATUS_ACTIVE,
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addDays(7),
        ]);
    }

    public function test_activities_returns_sessions_and_payouts(): void
    {
        [$user, $token] = $this->registeredListenerToken();

        $campaign = $this->artistWithCampaign();
        $assignment = CampaignAssignment::create([
            'campaign_id' => $campaign->id,
            'listener_id' => $user->id,
            'status' => CampaignAssignment::STATUS_REVIEWED,
            'reviewed_at' => now(),
        ]);

        ListenSession::create([
            'listener_id' => $user->id,
            'assignment_id' => $assignment->id,
            'min_duration_seconds' => 30,
            'elapsed_seconds' => 45,
            'checkpoints' => [],
            'foreground' => true,
            'status' => ListenSession::STATUS_REWARDED,
            'started_at' => now()->subMinutes(5),
            'completed_at' => now(),
        ]);

        $this->getJson('/api/v1/activities', ['Authorization' => 'Bearer ' . $token])
            ->assertOk()
            ->assertJsonPath('today_completed', 1)
            ->assertJsonCount(1, 'sessions')
            ->assertJsonPath('sessions.0.campaign_title', 'Hot Single')
            ->assertJsonPath('sessions.0.reward', 100);
    }

    public function test_heartbeat_updates_free_move_flag(): void
    {
        [$user, $token] = $this->registeredListenerToken();

        $device = UserDevice::create([
            'user_id' => $user->id,
            'fingerprint' => 'abc-123',
            'fcm_token' => 'fcm-test-token',
            'platform' => 'android',
        ]);

        $this->postJson('/api/v1/device/heartbeat', [
            'fingerprint' => 'abc-123',
            'free_move' => true,
        ], ['Authorization' => 'Bearer ' . $token])->assertOk();

        $this->assertTrue($device->fresh()->free_move);
    }

    public function test_settings_exposes_latest_apk(): void
    {
        [$user, $token] = $this->registeredListenerToken();

        $apkPath = public_path('download/test-update-9.9.9.apk');
        @mkdir(dirname($apkPath), 0777, true);
        file_put_contents($apkPath, 'fake-apk-bytes');
        touch($apkPath, now()->addHour()->timestamp);

        try {
            $response = $this->getJson('/api/v1/settings', [
                'Authorization' => 'Bearer ' . $token,
                'X-App-Version' => '1.0.0',
            ])->assertOk();

            $apk = $response->json('apk');
            $this->assertNotNull($apk);
            $this->assertSame('1.0.0', $apk['version']);
            $this->assertStringContainsString('test-update-9.9.9.apk', $apk['url']);
            $this->assertIsInt($apk['size']);
        } finally {
            @unlink($apkPath);
        }
    }

    public function test_app_latest_is_public_and_returns_apk(): void
    {
        $this->seed(\Database\Seeders\AudioReachSeeder::class);

        $apkPath = public_path('download/test-update-9.9.9.apk');
        @mkdir(dirname($apkPath), 0777, true);
        file_put_contents($apkPath, 'fake-apk-bytes');
        touch($apkPath, now()->addHour()->timestamp);

        try {
            $response = $this->getJson('/api/v1/app/latest', [
                'X-App-Version' => '1.0.0',
            ])->assertOk()
                ->assertJsonPath('app_version', '1.0.0')
                ->assertJsonPath('force_update', false);

            $apk = $response->json('apk');
            $this->assertNotNull($apk);
            $this->assertStringContainsString('test-update-9.9.9.apk', $apk['url']);
        } finally {
            @unlink($apkPath);
        }
    }
}