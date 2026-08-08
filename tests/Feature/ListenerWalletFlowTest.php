<?php

namespace Tests\Feature;

use App\Models\AppSetting;
use App\Models\CampaignAssignment;
use App\Models\CampaignPackage;
use App\Models\PromoCampaign;
use App\Models\User;
use App\Services\WalletService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class ListenerWalletFlowTest extends TestCase
{
    use RefreshDatabase;

    private function registerUser(string $email = 'listener@example.com'): User
    {
        $this->postJson('/api/v1/auth/register', [
            'name' => 'Chidi Listener',
            'email' => $email,
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'genre_prefs' => ['afrobeats'],
        ])->assertCreated();

        return User::where('email', $email)->firstOrFail();
    }

    private function tokenFor(User $user): string
    {
        $entry = Cache::get('email_verify:' . $user->email);

        $response = $this->postJson('/api/v1/auth/verify-email', [
            'email' => $user->email,
            'code' => $entry['code'],
        ])->assertOk();

        return $response->json('token');
    }

    private function headersFor(string $token): array
    {
        return ['Authorization' => 'Bearer ' . $token];
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

        $artist = User::create(['name' => 'Artist One', 'role' => User::ROLE_ARTIST, 'email' => 'artist-one@example.com', 'password' => bcrypt('password123')]);

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

    public function test_registration_issues_verification_code(): void
    {
        $this->seed(\Database\Seeders\AudioReachSeeder::class);

        $user = $this->registerUser();

        $this->assertSame(User::ROLE_LISTENER, $user->role);
        $this->assertNotNull(Cache::get('email_verify:' . $user->email));
    }

    public function test_login_requires_verified_email(): void
    {
        $this->seed(\Database\Seeders\AudioReachSeeder::class);

        $this->registerUser();

        $this->postJson('/api/v1/auth/login', [
            'email' => 'listener@example.com',
            'password' => 'password123',
        ])->assertStatus(403)->assertJson(['requires_verification' => true]);
    }

    public function test_wallet_returns_balance_and_min_payout(): void
    {
        $this->seed(\Database\Seeders\AudioReachSeeder::class);

        $user = $this->registerUser();
        $token = $this->tokenFor($user);

        $this->getJson('/api/v1/wallet', $this->headersFor($token))
            ->assertOk()
            ->assertJson([
                'success' => true,
                'balance' => 0,
                'min_payout' => 1000,
            ]);
    }

    public function test_artist_cannot_access_listener_api(): void
    {
        $this->seed(\Database\Seeders\AudioReachSeeder::class);

        $artist = User::create(['name' => 'Artist One', 'role' => User::ROLE_ARTIST, 'email' => 'artist@example.com', 'password' => bcrypt('password123')]);
        $token = $artist->createToken('test')->plainTextToken;

        $this->getJson('/api/v1/wallet', $this->headersFor($token))->assertForbidden();
    }

    public function test_payout_below_minimum_is_rejected(): void
    {
        $this->seed(\Database\Seeders\AudioReachSeeder::class);

        $user = $this->registerUser();
        $token = $this->tokenFor($user);
        $wallet = app(WalletService::class);
        $wallet->credit($user, 500, 'reward', ['review_id' => 1]);

        $this->postJson('/api/v1/wallet/payout-request', [
            'amount' => 500,
            'method' => 'bank',
            'account' => ['bank_code' => '044', 'account_number' => '0123456789', 'account_name' => 'Chidi'],
        ], $this->headersFor($token))
            ->assertStatus(422)
            ->assertJson(['message' => 'Minimum payout is 1000.']);

        $this->assertDatabaseCount('payout_requests', 0);
    }

    public function test_full_listen_reward_and_payout_flow(): void
    {
        $this->seed(\Database\Seeders\AudioReachSeeder::class);
        AppSetting::where('key', 'min_payout')->update(['value' => 100]);

        $listener = $this->registerUser();
        $token = $this->tokenFor($listener);
        $headers = $this->headersFor($token);

        $campaign = $this->artistWithCampaign();

        $assignment = CampaignAssignment::create([
            'campaign_id' => $campaign->id,
            'listener_id' => $listener->id,
            'status' => CampaignAssignment::STATUS_ASSIGNED,
        ]);

        // 1. Start a listen session
        $start = $this->postJson("/api/v1/listen/{$campaign->id}/start", [], $headers)
            ->assertCreated()
            ->assertJson(['success' => true]);

        $sessionId = $start->json('session.id');

        // Simulate real listening time so the fraud engine's time checks pass
        \App\Models\ListenSession::where('id', $sessionId)->update(['started_at' => now()->subMinutes(3)]);

        // 2. Checkpoint after the minimum duration
        $this->postJson("/api/v1/listen/{$sessionId}/checkpoint", [
            'elapsed_seconds' => 31,
            'foreground' => true,
        ], $headers)->assertOk()->assertJson(['can_complete' => true]);

        // 3. Complete the session
        $complete = $this->postJson("/api/v1/listen/{$sessionId}/complete", [], $headers)
            ->assertStatus(201)
            ->assertJson(['success' => true, 'session' => ['status' => 'rewarded']]);

        // Reward job ran synchronously
        $this->assertDatabaseHas('wallet_transactions', [
            'user_id' => $listener->id,
            'type' => 'reward',
            'amount' => 150,
        ]);

        // 4. Wallet reflects the reward
        $this->getJson('/api/v1/wallet', $headers)
            ->assertOk()
            ->assertJson(['balance' => 150]);

        // 5. Payout request (now above the lowered minimum)
        $this->postJson('/api/v1/wallet/payout-request', [
            'amount' => 100,
            'method' => 'bank',
            'account' => ['bank_code' => '044', 'account_number' => '0123456789', 'account_name' => 'Chidi'],
        ], $headers)
            ->assertStatus(201)
            ->assertJson(['success' => true]);

        // 6. Second request while one is pending is rejected
        $this->postJson('/api/v1/wallet/payout-request', [
            'amount' => 100,
            'method' => 'bank',
            'account' => ['bank_code' => '044', 'account_number' => '0123456789'],
        ], $headers)
            ->assertStatus(422);

        $this->assertDatabaseCount('payout_requests', 1);
    }

    public function test_reward_is_not_double_credited(): void
    {
        $this->seed(\Database\Seeders\AudioReachSeeder::class);

        $listener = $this->registerUser();
        $campaign = $this->artistWithCampaign();
        $assignment = CampaignAssignment::create([
            'campaign_id' => $campaign->id,
            'listener_id' => $listener->id,
            'status' => CampaignAssignment::STATUS_ASSIGNED,
        ]);
        $reward = $campaign->reward_per_review;

        $session = \App\Models\ListenSession::create([
            'listener_id' => $listener->id,
            'assignment_id' => $assignment->id,
            'min_duration_seconds' => 30,
            'elapsed_seconds' => 40,
            'checkpoints' => [],
            'foreground' => true,
            'status' => 'rewarded',
            'started_at' => now()->subMinutes(2),
            'completed_at' => now(),
        ]);

        \App\Jobs\RewardSessionJob::dispatchSync($session->id);
        \App\Jobs\RewardSessionJob::dispatchSync($session->id);

        $wallet = app(WalletService::class);

        $this->assertDatabaseCount('wallet_transactions', 1);
        $this->assertSame((int) $reward, (int) $wallet->balance($listener));
    }
}