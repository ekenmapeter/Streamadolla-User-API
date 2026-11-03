<?php

namespace Tests\Feature;

use App\Console\Commands\ExecuteCampaigns;
use App\Jobs\VendorRewardJob;
use App\Models\Campaign;
use App\Models\Device;
use App\Models\DeviceAssignment;
use App\Models\DeviceSession;
use App\Models\StreamPlay;
use App\Models\Track;
use App\Models\User;
use App\Models\UserDevice;
use App\Models\WalletTransaction;
use App\Services\WalletService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class VendorFlowTest extends TestCase
{
    use RefreshDatabase;

    private string $vendorToken = '';
    private array $tokens = [];

    private function seedBase(): void
    {
        $this->seed(\Database\Seeders\AudioReachSeeder::class);
    }

    private function registerVendor(string $email = 'vendor@example.com'): User
    {
        $this->postJson('/api/v1/auth/register', [
            'name' => 'Grace Vendor',
            'email' => $email,
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'vendor',
        ])->assertCreated();

        return User::where('email', $email)->firstOrFail();
    }

    private function vendorToken(User $user): string
    {
        if (isset($this->tokens[$user->email])) {
            return $this->tokens[$user->email];
        }

        $entry = Cache::get('email_verify:' . $user->email);
        $this->assertNotNull($entry, 'Verification code missing for ' . $user->email);

        $token = $this->postJson('/api/v1/auth/verify-email', [
            'email' => $user->email,
            'code' => $entry['code'],
        ])->json('token');

        $this->tokens[$user->email] = $token;

        return $token;
    }

    private function registerVendorDevice(User $user, string $fingerprint = 'fp-vendor-1', string $token = 'fcm-vendor-1'): Device
    {
        $this->postJson('/api/v1/device/register', [
            'fingerprint' => $fingerprint,
            'fcm_token' => $token,
            'platform' => 'android',
            'app_version' => '1.0.0',
        ], ['Authorization' => 'Bearer ' . $this->vendorToken($user)])->assertOk();

        return Device::where('device_id', 'user-' . $fingerprint)->firstOrFail();
    }

    private function makeTrack(): Track
    {
        $artist = User::firstOrCreate(
            ['email' => 'artist@example.com'],
            ['name' => 'Artist', 'role' => User::ROLE_ARTIST, 'password' => bcrypt('password123')]
        );

        $package = \App\Models\CampaignPackage::create([
            'name' => 'Pack',
            'price_ngn' => 30000,
            'listen_target' => 100,
            'review_target' => 50,
            'margin_pct' => 35,
        ]);

        return Track::create([
            'artist_id' => $artist->id,
            'package_id' => $package->id,
            'title' => 'Vendor Single',
            'artist_name' => 'Artist',
            'platform' => 'youtube',
            'media_url' => 'https://www.youtube.com/watch?v=v123',
            'genre' => 'afrobeats',
            'duration_seconds' => 30,
            'status' => Track::STATUS_LIVE,
            'payment_reference' => 'TR-V-' . strtoupper(uniqid()),
            'amount_paid_ngn' => 30000,
            'stream_budget' => 5,
            'approved_at' => now(),
        ]);
    }

    private function makeAssignment(Device $device, Track $track): DeviceAssignment
    {
        return DeviceAssignment::create([
            'device_id' => $device->id,
            'track_id' => $track->id,
            'platform' => $track->platform,
            'media_url' => $track->media_url,
            'media_title' => $track->title,
            'status' => 'playing',
            'assigned_at' => now(),
            'started_at' => now(),
        ]);
    }

    private function playOutSession(Device $device, DeviceAssignment $assignment, int $elapsed = 45): int
    {
        $this->postJson("/api/v1/vendor/sessions/{$assignment->id}/start", [], [
            'Authorization' => 'Bearer ' . $this->vendorToken,
        ])->assertCreated();

        // Simulate real wall-clock time having passed so the too-fast gate
        // does not trip, and record the required two checkpoints. The track
        // was assigned before the session started, so keep that ordering.
        DeviceSession::where('device_assignment_id', $assignment->id)->update([
            'started_at' => now()->subSeconds($elapsed + 5),
        ]);
        $assignment->update(['started_at' => now()->subSeconds($elapsed + 15)]);

        $this->postJson("/api/v1/vendor/sessions/{$assignment->id}/checkpoint", [
            'elapsed_seconds' => 15,
        ], ['Authorization' => 'Bearer ' . $this->vendorToken])->assertOk();

        $this->postJson("/api/v1/vendor/sessions/{$assignment->id}/checkpoint", [
            'elapsed_seconds' => $elapsed,
        ], ['Authorization' => 'Bearer ' . $this->vendorToken])->assertOk();

        $this->postJson("/api/v1/vendor/sessions/{$assignment->id}/complete", [], [
            'Authorization' => 'Bearer ' . $this->vendorToken,
        ])->assertCreated();

        return DeviceSession::where('device_assignment_id', $assignment->id)->value('id');
    }

    // ── Account & device linking ────────────────────────────────────────

    public function test_vendor_can_register_verify_and_login(): void
    {
        $this->seedBase();
        $vendor = $this->registerVendor();

        $this->assertTrue($vendor->isVendor());

        $this->vendorToken($vendor);

        $payload = $this->postJson('/api/v1/auth/login', [
            'email' => $vendor->email,
            'password' => 'password123',
        ])->assertOk()->json();

        $this->assertSame('vendor', $payload['user']['role']);
        $this->assertTrue($payload['user']['vendor']);

        $me = $this->getJson('/api/v1/me', [
            'Authorization' => 'Bearer ' . $payload['token'],
        ])->assertOk()->json();

        $this->assertSame('vendor', $me['user']['role']);
    }

    public function test_listener_can_still_login(): void
    {
        $this->seedBase();
        $listener = User::create([
            'name' => 'Listener',
            'email' => 'my-listener@example.com',
            'password' => bcrypt('password123'),
            'role' => User::ROLE_LISTENER,
            'status' => 'active',
        ]);
        $listener->forceFill(['email_verified_at' => now()])->save();

        $resp = $this->postJson('/api/v1/auth/login', [
            'email' => $listener->email,
            'password' => 'password123',
        ])->assertOk();
    }

    public function test_device_register_creates_fleet_device_under_vendor(): void
    {
        $this->seedBase();
        $vendor = $this->registerVendor();
        $this->vendorToken = $this->vendorToken($vendor);

        $this->postJson('/api/v1/device/register', [
            'fingerprint' => 'fp-vendor-1',
            'fcm_token' => 'fcm-vendor-1',
            'platform' => 'android',
        ], ['Authorization' => 'Bearer ' . $this->vendorToken])->assertOk();

        $this->assertDatabaseHas('user_devices', ['fingerprint' => 'fp-vendor-1', 'user_id' => $vendor->id]);

        $fleet = Device::where('device_id', 'user-fp-vendor-1')->first();
        $this->assertNotNull($fleet);
        $this->assertSame($vendor->id, $fleet->user_id);
        $this->assertSame('fcm-vendor-1', $fleet->fcm_token);
        $this->assertSame('online', $fleet->status);
    }

    public function test_vendor_devices_list_and_rename(): void
    {
        $this->seedBase();
        $vendor = $this->registerVendor();
        $this->vendorToken = $this->vendorToken($vendor);
        $device = $this->registerVendorDevice($vendor, 'fp-vendor-2', 'fcm-vendor-2');

        $this->postJson('/api/v1/device/heartbeat', [
            'fingerprint' => 'fp-vendor-2',
        ], ['Authorization' => 'Bearer ' . $this->vendorToken])->assertOk();

        $list = $this->getJson('/api/v1/vendor/devices', [
            'Authorization' => 'Bearer ' . $this->vendorToken,
        ])->assertOk()->json();

        $this->assertSame(1, $list['summary']['total_devices']);
        $this->assertSame(1, $list['summary']['online_devices']);
        $this->assertSame($device->id, $list['devices'][0]['id']);

        $this->putJson("/api/v1/vendor/devices/{$device->id}", [
            'name' => 'Shop phone',
        ], ['Authorization' => 'Bearer ' . $this->vendorToken])->assertOk();

        $this->assertSame('Shop phone', $device->fresh()->name);
    }

    public function test_vendor_cannot_rename_foreign_device(): void
    {
        $this->seedBase();
        $vendorA = $this->registerVendor();
        $vendorB = $this->registerVendor('other@example.com');
        $this->vendorToken = $this->vendorToken($vendorA);

        $device = $this->registerVendorDevice($vendorB, 'fp-other', 'fcm-other');

        // The guard caches the resolved user between requests inside one test,
        // so forget it before switching the acting vendor.
        $this->app['auth']->forgetGuards();

        $this->putJson("/api/v1/vendor/devices/{$device->id}", [
            'name' => 'Hijacked',
        ], ['Authorization' => 'Bearer ' . $this->vendorToken])->assertForbidden();

        $this->assertNotSame('Hijacked', $device->fresh()->name);
    }

    // ── Session flow ────────────────────────────────────────────────────

    public function test_session_happy_path_rewards_vendor_wallet(): void
    {
        $this->seedBase();
        $vendor = $this->registerVendor();
        $this->vendorToken = $this->vendorToken($vendor);
        $device = $this->registerVendorDevice($vendor);
        $track = $this->makeTrack();
        $assignment = $this->makeAssignment($device, $track);

        $sessionId = $this->playOutSession($device, $assignment);

        $this->assertSame('rewarded', DeviceSession::find($sessionId)->status);
        $this->assertSame(1, Device::find($device->id)->total_streams);
        $this->assertEqualsWithDelta(0.001, Device::find($device->id)->total_earned, 0.0000001);
        $this->assertSame(1, $track->fresh()->streams_served);

        $play = StreamPlay::where('device_assignment_id', $assignment->id)->first();
        $this->assertNotNull($play);
        $this->assertEqualsWithDelta(0.001, $play->reward_amount, 0.0000001);

        $this->assertEqualsWithDelta(0.001, (new WalletService())->balance($vendor->fresh()), 0.0000001);

        // Earnings record shows the rewarded stream.
        $earnings = $this->getJson('/api/v1/vendor/earnings', [
            'Authorization' => 'Bearer ' . $this->vendorToken,
        ])->assertOk()->json();

        $this->assertSame(1, $earnings['summary']['total_streams']);
        $this->assertEqualsWithDelta(0.001, $earnings['summary']['total_earned'], 0.0000001);
        $this->assertSame('Vendor Single', $earnings['streams'][0]['media_title']);
    }

    public function test_complete_before_min_duration_is_rejected(): void
    {
        $this->seedBase();
        $vendor = $this->registerVendor();
        $this->vendorToken = $this->vendorToken($vendor);
        $device = $this->registerVendorDevice($vendor);
        $assignment = $this->makeAssignment($device, $this->makeTrack());

        $this->postJson("/api/v1/vendor/sessions/{$assignment->id}/start", [], [
            'Authorization' => 'Bearer ' . $this->vendorToken,
        ])->assertCreated();

        $this->postJson("/api/v1/vendor/sessions/{$assignment->id}/complete", [], [
            'Authorization' => 'Bearer ' . $this->vendorToken,
        ])->assertStatus(422);

        $this->assertDatabaseMissing('stream_plays', ['device_assignment_id' => $assignment->id]);
    }

    public function test_campaign_assignment_stays_playing_after_vendor_session_completes(): void
    {
        $this->seedBase();
        $vendor = $this->registerVendor('campaign-vendor@example.com');
        $this->vendorToken = $this->vendorToken($vendor);
        $device = $this->registerVendorDevice($vendor, 'fp-campaign-vendor', 'fcm-campaign-vendor');

        $campaign = Campaign::create([
            'name' => 'Vendor Campaign',
            'platform' => 'spotify',
            'is_active' => true,
        ]);

        $assignment = DeviceAssignment::create([
            'device_id' => $device->id,
            'campaign_id' => $campaign->id,
            'platform' => 'spotify',
            'media_url' => 'https://open.spotify.com/track/example',
            'status' => 'playing',
            'assigned_at' => now(),
            'started_at' => now(),
        ]);

        $this->playOutSession($device, $assignment);

        // The vendor session is rewarded, but the campaign assignment must
        // remain active so the worker can advance to the next track.
        $this->assertSame('playing', $assignment->fresh()->status);
        $this->assertDatabaseHas('stream_plays', ['device_assignment_id' => $assignment->id]);
    }

    public function test_each_campaign_track_session_earns_a_separate_reward(): void
    {
        $this->seedBase();
        $vendor = $this->registerVendor('multi-track-vendor@example.com');
        $this->vendorToken = $this->vendorToken($vendor);
        $device = $this->registerVendorDevice($vendor, 'fp-multi-track', 'fcm-multi-track');

        $campaign = Campaign::create([
            'name' => 'Looping Campaign',
            'platform' => 'spotify',
            'is_active' => true,
        ]);

        $assignment = DeviceAssignment::create([
            'device_id' => $device->id,
            'campaign_id' => $campaign->id,
            'platform' => 'spotify',
            'media_url' => 'https://open.spotify.com/track/first',
            'media_title' => 'First Track',
            'status' => 'playing',
            'assigned_at' => now(),
            'started_at' => now(),
        ]);

        $this->playOutSession($device, $assignment);

        // Simulate the campaign worker advancing to the next track.
        $assignment->update([
            'media_url' => 'https://open.spotify.com/track/second',
            'media_title' => 'Second Track',
            'started_at' => now(),
        ]);

        $this->playOutSession($device, $assignment);

        $this->assertSame(2, StreamPlay::where('device_assignment_id', $assignment->id)->count());
        $this->assertEqualsWithDelta(0.002, (new WalletService())->balance($vendor->fresh()), 0.0000001);
    }

    public function test_track_advance_rewards_an_open_vendor_session_without_checkpoints(): void
    {
        $this->seedBase();
        $vendor = $this->registerVendor('fallback-vendor@example.com');
        $this->vendorToken = $this->vendorToken($vendor);
        $device = $this->registerVendorDevice($vendor, 'fp-fallback', 'fcm-fallback');

        $campaign = Campaign::create([
            'name' => 'Fallback Campaign',
            'platform' => 'spotify',
            'is_active' => true,
        ]);

        $assignment = DeviceAssignment::create([
            'device_id' => $device->id,
            'campaign_id' => $campaign->id,
            'platform' => 'spotify',
            'media_url' => 'https://open.spotify.com/track/fallback',
            'media_title' => 'Fallback Track',
            'status' => 'playing',
            'assigned_at' => now(),
            'started_at' => now()->subSeconds(70),
        ]);

        $session = DeviceSession::create([
            'vendor_id' => $vendor->id,
            'device_id' => $device->id,
            'device_assignment_id' => $assignment->id,
            'min_duration_seconds' => 30,
            'elapsed_seconds' => 0,
            'checkpoints' => [],
            'status' => DeviceSession::STATUS_OPEN,
            'started_at' => now()->subSeconds(60),
        ]);

        $command = app(ExecuteCampaigns::class);
        $method = new \ReflectionMethod($command, 'completeOpenVendorSessions');
        $method->setAccessible(true);
        $method->invoke($command, $assignment, $device);

        $this->assertSame('rewarded', $session->fresh()->status);
        $this->assertDatabaseHas('stream_plays', ['device_session_id' => $session->id]);
        $this->assertEqualsWithDelta(0.001, (new WalletService())->balance($vendor->fresh()), 0.0000001);
    }

    public function test_low_checkpoints_and_too_fast_completion_flagged_fraud(): void
    {
        $this->seedBase();
        $vendor = $this->registerVendor();
        $this->vendorToken = $this->vendorToken($vendor);
        $device = $this->registerVendorDevice($vendor);
        $assignment = $this->makeAssignment($device, $this->makeTrack());

        $this->postJson("/api/v1/vendor/sessions/{$assignment->id}/start", [], [
            'Authorization' => 'Bearer ' . $this->vendorToken,
        ])->assertCreated();

        // Only ONE checkpoint, elapsed >= min, but wall-clock still near-zero.
        $this->postJson("/api/v1/vendor/sessions/{$assignment->id}/checkpoint", [
            'elapsed_seconds' => 30,
        ], ['Authorization' => 'Bearer ' . $this->vendorToken])->assertOk();

        $response = $this->postJson("/api/v1/vendor/sessions/{$assignment->id}/complete", [], [
            'Authorization' => 'Bearer ' . $this->vendorToken,
        ])->assertStatus(422)->json();

        $this->assertArrayHasKey('flags', $response);
        $this->assertContains('missing_checkpoints', $response['flags']);
        $this->assertContains('too_fast', $response['flags']);

        $session = DeviceSession::where('device_assignment_id', $assignment->id)->firstOrFail();
        $this->assertSame('fraud', $session->status);
        $this->assertDatabaseMissing('stream_plays', ['device_assignment_id' => $assignment->id]);
        $this->assertSame(0, (new WalletService())->balance($vendor->fresh()));
    }

    public function test_daily_per_device_cap_blocks_reward(): void
    {
        $this->seedBase();
        $vendor = $this->registerVendor();
        $this->vendorToken = $this->vendorToken($vendor);
        $device = $this->registerVendorDevice($vendor);
        $assignment = $this->makeAssignment($device, $this->makeTrack());

        Cache::put(
            'vendor_stream_count:' . $device->id . ':' . now()->toDateString(),
            50
        );

        $this->postJson("/api/v1/vendor/sessions/{$assignment->id}/start", [], [
            'Authorization' => 'Bearer ' . $this->vendorToken,
        ])->assertCreated();

        // Long-enough, well-checkpointed session that only trips the cap.
        DeviceSession::where('device_assignment_id', $assignment->id)->update([
            'started_at' => now()->subSeconds(50),
        ]);
        $assignment->update(['started_at' => now()->subSeconds(60)]);

        $this->postJson("/api/v1/vendor/sessions/{$assignment->id}/checkpoint", [
            'elapsed_seconds' => 15,
        ], ['Authorization' => 'Bearer ' . $this->vendorToken])->assertOk();
        $this->postJson("/api/v1/vendor/sessions/{$assignment->id}/checkpoint", [
            'elapsed_seconds' => 45,
        ], ['Authorization' => 'Bearer ' . $this->vendorToken])->assertOk();

        $this->postJson("/api/v1/vendor/sessions/{$assignment->id}/complete", [], [
            'Authorization' => 'Bearer ' . $this->vendorToken,
        ])->assertStatus(422);

        $session = DeviceSession::where('device_assignment_id', $assignment->id)->firstOrFail();
        $this->assertSame('fraud', $session->status);
        $this->assertDatabaseMissing('stream_plays', ['device_assignment_id' => $assignment->id]);
    }

    public function test_double_completion_and_job_rerun_are_idempotent(): void
    {
        $this->seedBase();
        $vendor = $this->registerVendor();
        $this->vendorToken = $this->vendorToken($vendor);
        $device = $this->registerVendorDevice($vendor);
        $assignment = $this->makeAssignment($device, $this->makeTrack());

        $sessionId = $this->playOutSession($device, $assignment);

        // A second completion attempt finds the session closed.
        $this->postJson("/api/v1/vendor/sessions/{$assignment->id}/complete", [], [
            'Authorization' => 'Bearer ' . $this->vendorToken,
        ])->assertForbidden();

        // Re-running the job must not double-pay.
        (new VendorRewardJob($sessionId))->handle(app(WalletService::class));

        $this->assertSame(1, StreamPlay::where('device_assignment_id', $assignment->id)->count());
        $this->assertEqualsWithDelta(0.001, (new WalletService())->balance($vendor->fresh()), 0.0000001);
    }

    public function test_stop_control_abandons_session_without_reward(): void
    {
        $this->seedBase();
        $vendor = $this->registerVendor();
        $this->vendorToken = $this->vendorToken($vendor);
        $device = $this->registerVendorDevice($vendor);
        $assignment = $this->makeAssignment($device, $this->makeTrack());

        $this->postJson("/api/v1/vendor/sessions/{$assignment->id}/start", [], [
            'Authorization' => 'Bearer ' . $this->vendorToken,
        ])->assertCreated();

        $this->postJson("/api/v1/vendor/sessions/{$assignment->id}/control", [
            'status' => 'stopped',
        ], ['Authorization' => 'Bearer ' . $this->vendorToken])->assertOk();

        $session = DeviceSession::where('device_assignment_id', $assignment->id)->firstOrFail();
        $this->assertSame('abandoned', $session->status);
        $this->assertSame('stopped', $assignment->fresh()->status);
        $this->assertDatabaseMissing('stream_plays', ['device_assignment_id' => $assignment->id]);
    }

    public function test_foreign_vendor_cannot_session_a_device(): void
    {
        $this->seedBase();
        $owner = $this->registerVendor();
        $intruder = $this->registerVendor('intruder@example.com');

        $this->vendorToken = $this->vendorToken($intruder);

        $device = Device::create([
            'user_id' => $owner->id,
            'name' => 'Owner phone',
            'device_id' => 'user-fp-owner',
            'fcm_token' => 'fcm-owner',
            'status' => 'online',
            'last_seen' => now(),
        ]);
        $assignment = $this->makeAssignment($device, $this->makeTrack());

        $this->postJson("/api/v1/vendor/sessions/{$assignment->id}/start", [], [
            'Authorization' => 'Bearer ' . $this->vendorToken,
        ])->assertForbidden();
    }

    // ── Admin monitoring ────────────────────────────────────────────────

    public function test_vendor_can_use_shared_me_and_wallet_routes(): void
    {
        $this->seedBase();
        $vendor = $this->registerVendor();
        $this->vendorToken = $this->vendorToken($vendor);

        $this->getJson('/api/v1/wallet', [
            'Authorization' => 'Bearer ' . $this->vendorToken,
        ])->assertOk()->assertJson(['success' => true, 'balance' => 0]);

        $this->putJson('/api/v1/me', ['name' => 'Renamed Vendor'], [
            'Authorization' => 'Bearer ' . $this->vendorToken,
        ])->assertOk();

        $this->assertSame('Renamed Vendor', $vendor->fresh()->name);
    }

    public function test_vendor_wallet_and_payout_are_denominated_in_usd(): void
    {
        $this->seedBase();
        $vendor = $this->registerVendor();
        $this->vendorToken = $this->vendorToken($vendor);

        app(WalletService::class)->credit($vendor, 2.00, WalletTransaction::TYPE_VENDOR);

        $this->getJson('/api/v1/wallet', [
            'Authorization' => 'Bearer ' . $this->vendorToken,
        ])->assertOk()
            ->assertJsonPath('currency', 'USD')
            ->assertJsonPath('balance', 2)
            ->assertJsonPath('min_payout', 1);

        $response = $this->postJson('/api/v1/wallet/payout-request', [
            'amount' => 1.25,
            'method' => 'bank',
            'account' => [
                'bank_code' => '044',
                'account_number' => '0123456789',
                'account_name' => 'Grace Vendor',
            ],
        ], ['Authorization' => 'Bearer ' . $this->vendorToken])
            ->assertCreated()
            ->assertJsonPath('payout.currency', 'USD')
            ->assertJsonPath('payout.amount', 1.25);

        $this->assertDatabaseHas('payout_requests', [
            'id' => $response->json('payout.id'),
            'user_id' => $vendor->id,
            'currency' => 'USD',
            'amount' => 1.25,
        ]);

        $this->assertEqualsWithDelta(0.75, app(WalletService::class)->balance($vendor->fresh()), 0.0000001);
    }

    public function test_admin_vendor_pages_render(): void
    {
        $this->seedBase();
        $admin = User::create([
            'name' => 'Admin',
            'email' => 'admin@example.com',
            'password' => bcrypt('password123'),
            'role' => User::ROLE_ADMIN,
            'status' => 'active',
        ]);
        $admin->forceFill(['email_verified_at' => now()])->save();

        $vendor = $this->registerVendor();
        $this->vendorToken = $this->vendorToken($vendor);
        $device = $this->registerVendorDevice($vendor, 'fp-admin-1', 'fcm-admin-1');
        $assignment = $this->makeAssignment($device, $this->makeTrack());
        $this->playOutSession($device, $assignment);

        $this->actingAs($admin)
            ->get(route('admin.vendors'))
            ->assertOk()
            ->assertSee('vendor@example.com');

        $this->actingAs($admin)
            ->get(route('admin.vendors.detail', $vendor))
            ->assertOk()
            ->assertSee('Vendor Single')
            ->assertSee('Fleet Devices');

        $this->assertSame(1, WalletTransaction::where('user_id', $vendor->id)
            ->where('type', WalletTransaction::TYPE_VENDOR)
            ->where('status', WalletTransaction::STATUS_CREDITED)
            ->count());
        $this->assertSame(1, UserDevice::where('user_id', $vendor->id)->count());
    }
}