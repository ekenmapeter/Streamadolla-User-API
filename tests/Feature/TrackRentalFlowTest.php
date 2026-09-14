<?php

namespace Tests\Feature;

use App\Models\CampaignPackage;
use App\Models\Device;
use App\Models\DeviceAssignment;
use App\Models\DeviceRental;
use App\Models\StreamPlay;
use App\Models\Track;
use App\Models\User;
use App\Models\UserDevice;
use App\Models\WalletTransaction;
use App\Services\FirebaseMessagingService;
use App\Services\WalletService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TrackRentalFlowTest extends TestCase
{
    use RefreshDatabase;

    private function seedBase(): void
    {
        $this->seed(\Database\Seeders\AudioReachSeeder::class);
    }

    private array $tokens = [];

    private function artistUser(): User
    {
        return User::firstOrCreate(
            ['email' => 'artist@example.com'],
            ['name' => 'Artist One', 'role' => User::ROLE_ARTIST, 'password' => bcrypt('password123')]
        );
    }

    private function adminUser(): User
    {
        return User::factory()->create([
            'name' => 'Admin',
            'email' => 'admin@example.com',
            'role' => User::ROLE_ADMIN,
        ]);
    }

    private function registerListener(string $email = 'listener@example.com'): User
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

    private function listenerToken(User $user): string
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

    private function package(): CampaignPackage
    {
        return CampaignPackage::create([
            'name' => 'Test Pack',
            'price_ngn' => 30000,
            'listen_target' => 100,
            'review_target' => 50,
            'margin_pct' => 35,
        ]);
    }

    private function makeTrack(array $overrides = []): Track
    {
        $package = $this->package();

        return Track::create(array_merge([
            'artist_id' => $this->artistUser()->id,
            'package_id' => $package->id,
            'title' => 'Hot Single',
            'artist_name' => 'Artist One',
            'platform' => 'youtube',
            'media_url' => 'https://www.youtube.com/watch?v=abc123',
            'genre' => 'afrobeats',
            'duration_seconds' => 30,
            'status' => Track::STATUS_LIVE,
            'payment_reference' => 'TR-TEST-' . strtoupper(uniqid()),
            'amount_paid_ngn' => $package->price_ngn,
            'stream_budget' => 5,
            'approved_at' => now(),
        ], $overrides));
    }

    private function registerUserDevice(User $user, string $fingerprint = 'fp-123', ?string $token = 'fcm-token-123'): UserDevice
    {
        return UserDevice::create([
            'user_id' => $user->id,
            'fingerprint' => $fingerprint,
            'fcm_token' => $token,
            'platform' => 'android',
            'app_version' => '1.0.0',
            'free_move' => false,
            'last_seen_at' => now(),
        ]);
    }

    private function mockFcm(): void
    {
        $this->mock(FirebaseMessagingService::class, function ($mock) {
            $mock->shouldReceive('sendToToken')->andReturn(true);
            $mock->shouldReceive('platformCommand')->andReturn('play_youtube');
        });
    }

    // ── Artist submission + payment + admin approval ───────────────────

    public function test_artist_submits_track_and_gets_paystack_checkout(): void
    {
        $this->seedBase();
        $artist = $this->artistUser();
        $package = $this->package();

        config(['services.paystack.secret_key' => 'sk_test_123']);

        Http::fake([
            'api.paystack.co/transaction/initialize' => Http::response([
                'status' => true,
                'data' => [
                    'authorization_url' => 'https://checkout.paystack.com/abc',
                    'reference' => 'TR-ABC123',
                ],
            ], 200),
        ]);

        $this->actingAs($artist)
            ->post(route('artist.track.store'), [
                'title' => 'Midnight Vibe',
                'media_url' => 'https://www.youtube.com/watch?v=xyz789',
                'platform' => 'youtube',
                'genre' => 'Afrobeats',
                'duration_seconds' => 180,
                'package_id' => $package->id,
            ])
            ->assertRedirect('https://checkout.paystack.com/abc');

        $track = Track::where('title', 'Midnight Vibe')->first();

        $this->assertNotNull($track);
        $this->assertSame(Track::STATUS_PENDING_PAYMENT, $track->status);
        $this->assertSame('TR-ABC123', $track->payment_reference);
        $this->assertSame($package->listen_target, $track->stream_budget);
        $this->assertSame($package->price_ngn, $track->amount_paid_ngn);
    }

    public function test_paystack_webhook_marks_track_pending_approval(): void
    {
        $this->seedBase();
        $track = $this->makeTrack(['status' => Track::STATUS_PENDING_PAYMENT, 'payment_reference' => 'TR-PAY1']);

        $response = $this->postJson('/api/v1/webhooks/paystack', [
            'event' => 'charge.success',
            'data' => [
                'reference' => 'TR-PAY1',
                'amount' => $track->amount_paid_ngn,
                'status' => 'success',
                'metadata' => ['kind' => 'track_funding', 'track_id' => $track->id],
            ],
        ])->assertOk();

        $this->assertSame(Track::STATUS_PENDING_APPROVAL, $track->fresh()->status);
    }

    public function test_admin_approves_track_and_it_goes_live(): void
    {
        $this->seedBase();
        $admin = $this->adminUser();
        $track = $this->makeTrack(['status' => Track::STATUS_PENDING_APPROVAL]);

        $this->actingAs($admin)
            ->post(route('admin.tracks.approve', $track))
            ->assertSessionHas('status');

        $track->refresh();

        $this->assertSame(Track::STATUS_LIVE, $track->status);
        $this->assertNotNull($track->approved_at);
    }

    public function test_track_list_endpoint_returns_live_tracks(): void
    {
        $this->seedBase();
        $this->makeTrack();
        $this->makeTrack(['title' => 'Hidden', 'status' => Track::STATUS_PENDING_APPROVAL]);

        $user = $this->registerListener();
        $token = $this->listenerToken($user);

        $this->getJson('/api/v1/tracks', ['Authorization' => 'Bearer ' . $token])
            ->assertOk()
            ->assertJsonCount(1, 'tracks')
            ->assertJsonPath('tracks.0.title', 'Hot Single');
    }

    public function test_admin_track_list_page_renders_pending_tracks(): void
    {
        $this->seedBase();
        $admin = $this->adminUser();
        $this->makeTrack(['title' => 'Awaiting Song', 'status' => Track::STATUS_PENDING_APPROVAL]);

        $this->actingAs($admin)
            ->get(route('admin.tracks'))
            ->assertOk()
            ->assertSee('Awaiting Song');
    }

    public function test_artist_track_pages_render(): void
    {
        $this->seedBase();
        $artist = $this->artistUser();
        $this->package();
        $this->makeTrack(['title' => 'My Submitted Song']);

        $this->actingAs($artist)
            ->get(route('artist.tracks'))
            ->assertOk()
            ->assertSee('My Submitted Song');

        $this->actingAs($artist)
            ->get(route('artist.track.create'))
            ->assertOk()
            ->assertSee('Submit a Track');
    }

    // ── Rental lifecycle ────────────────────────────────────────────────

    public function test_rental_start_requires_registered_fcm_device(): void
    {
        $this->seedBase();
        $user = $this->registerListener();
        $token = $this->listenerToken($user);

        $this->postJson('/api/v1/rentals/start', [], ['Authorization' => 'Bearer ' . $token])
            ->assertStatus(422);
    }

    public function test_rental_start_creates_fleet_device_and_contract(): void
    {
        $this->seedBase();
        $user = $this->registerListener();
        $token = $this->listenerToken($user);
        $this->registerUserDevice($user);

        $this->postJson('/api/v1/rentals/start', [], ['Authorization' => 'Bearer ' . $token])
            ->assertCreated()
            ->assertJsonPath('rental.status', 'active');

        $rental = DeviceRental::where('user_id', $user->id)->first();

        $this->assertNotNull($rental);
        $this->assertSame(DeviceRental::STATUS_ACTIVE, $rental->status);
        $this->assertSame(5, (int) $rental->rate_per_stream);

        $device = Device::find($rental->device_id);

        $this->assertNotNull($device);
        $this->assertSame($user->id, $device->user_id);
        $this->assertSame('user-fp-123', $device->device_id);
        $this->assertSame('online', $device->status);
    }

    public function test_rental_pause_and_stop_terminates_contract(): void
    {
        $this->seedBase();
        $user = $this->registerListener();
        $token = $this->listenerToken($user);
        $this->registerUserDevice($user);

        $this->postJson('/api/v1/rentals/start', [], ['Authorization' => 'Bearer ' . $token])->assertCreated();

        $this->postJson('/api/v1/rentals/pause', [], ['Authorization' => 'Bearer ' . $token])
            ->assertOk()
            ->assertJsonPath('rental.status', 'paused');

        $this->postJson('/api/v1/rentals/stop', [], ['Authorization' => 'Bearer ' . $token])
            ->assertOk()
            ->assertJsonPath('rental.status', 'terminated');

        $device = Device::where('user_id', $user->id)->first();

        $this->assertSame('offline', $device->status);
    }

    public function test_rentals_expire_when_device_goes_stale(): void
    {
        $this->seedBase();
        $user = $this->registerListener();
        $this->registerUserDevice($user);

        $this->postJson('/api/v1/rentals/start', [], ['Authorization' => 'Bearer ' . $this->listenerToken($user)])->assertCreated();

        Device::where('user_id', $user->id)->update(['last_seen' => now()->subMinutes(20)]);

        $this->artisan('rentals:expire')->assertSuccessful();

        $rental = DeviceRental::where('user_id', $user->id)->first();

        $this->assertSame(DeviceRental::STATUS_TERMINATED, $rental->status);
        $this->assertNotNull($rental->terminated_at);
    }

    // ── Distribution + per-stream payment ──────────────────────────────

    public function test_tracks_distribute_plays_and_rewards_per_stream(): void
    {
        $this->seedBase();
        $this->mockFcm();

        $user = $this->registerListener();
        $this->registerUserDevice($user);
        $this->postJson('/api/v1/rentals/start', [], ['Authorization' => 'Bearer ' . $this->listenerToken($user)])->assertCreated();

        $track = $this->makeTrack(['duration_seconds' => 30]);

        $this->artisan('tracks:distribute')->assertSuccessful();

        $assignment = DeviceAssignment::where('track_id', $track->id)->first();

        $this->assertNotNull($assignment);
        $this->assertSame('playing', $assignment->status);

        // Simulate the track finishing
        $assignment->update(['started_at' => now()->subSeconds(45)]);

        $this->artisan('tracks:distribute')->assertSuccessful();

        $this->assertSame('completed', $assignment->fresh()->status);

        $play = StreamPlay::where('device_assignment_id', $assignment->id)->first();

        $this->assertNotNull($play);
        $this->assertSame(StreamPlay::STATUS_REWARDED, $play->status);
        $this->assertSame(5, (int) $play->reward_amount);

        $this->assertSame(1, (int) $track->fresh()->streams_served);

        $rental = DeviceRental::where('user_id', $user->id)->first();

        $this->assertSame(1, (int) $rental->total_streams);
        $this->assertSame(5, (int) $rental->total_earned);

        $this->assertSame(5, (new WalletService())->balance($user->fresh()));

        $this->assertDatabaseHas('wallet_transactions', [
            'user_id' => $user->id,
            'type' => WalletTransaction::TYPE_RENTAL,
            'amount' => 5,
            'status' => WalletTransaction::STATUS_CREDITED,
        ]);
    }

    public function test_completed_stream_is_not_paid_twice(): void
    {
        $this->seedBase();
        $this->mockFcm();

        $user = $this->registerListener();
        $this->registerUserDevice($user);
        $this->postJson('/api/v1/rentals/start', [], ['Authorization' => 'Bearer ' . $this->listenerToken($user)])->assertCreated();

        $track = $this->makeTrack(['duration_seconds' => 30]);

        $this->artisan('tracks:distribute');
        $assignment = DeviceAssignment::where('track_id', $track->id)->first();
        $assignment->update(['started_at' => now()->subSeconds(45)]);

        // Finalize twice — the second pass must not double-credit
        $this->artisan('tracks:distribute');
        $this->artisan('tracks:distribute');

        $this->assertSame(1, StreamPlay::where('device_assignment_id', $assignment->id)->count());
        $this->assertSame(1, (int) $track->fresh()->streams_served);
        $this->assertSame(5, (new WalletService())->balance($user->fresh()));
    }

    public function test_track_is_exhausted_when_budget_is_met(): void
    {
        $this->seedBase();
        $this->mockFcm();

        $user = $this->registerListener();
        $this->registerUserDevice($user);
        $this->postJson('/api/v1/rentals/start', [], ['Authorization' => 'Bearer ' . $this->listenerToken($user)])->assertCreated();

        $track = $this->makeTrack(['duration_seconds' => 30, 'stream_budget' => 1]);

        $this->artisan('tracks:distribute');
        $assignment = DeviceAssignment::where('track_id', $track->id)->first();
        $assignment->update(['started_at' => now()->subSeconds(45)]);
        $this->artisan('tracks:distribute');

        $this->assertSame(Track::STATUS_EXHAUSTED, $track->fresh()->status);

        // No new assignment should be created for an exhausted track
        $this->artisan('tracks:distribute');

        $this->assertSame(1, DeviceAssignment::where('track_id', $track->id)->count());
    }

    // ── Assignment status mirror ────────────────────────────────────────

    public function test_rental_assignment_status_rejects_other_users(): void
    {
        $this->seedBase();
        $this->mockFcm();

        // Build the rental + assignment via models so the intruder's request
        // is the first authenticated request of the test process.
        $owner = User::factory()->create(['name' => 'Owner', 'email' => 'owner@example.com', 'role' => User::ROLE_LISTENER]);
        $fleetDevice = Device::create([
            'user_id' => $owner->id,
            'name' => 'Owner phone',
            'device_id' => 'user-fp-owner',
            'fcm_token' => 'fcm-token-owner',
            'status' => 'online',
            'last_seen' => now(),
        ]);
        DeviceRental::create([
            'user_id' => $owner->id,
            'device_id' => $fleetDevice->id,
            'status' => DeviceRental::STATUS_ACTIVE,
            'rate_per_stream' => 5,
            'started_at' => now(),
        ]);

        $track = $this->makeTrack(['duration_seconds' => 30]);
        $assignment = DeviceAssignment::create([
            'device_id' => $fleetDevice->id,
            'track_id' => $track->id,
            'platform' => $track->platform,
            'media_url' => $track->media_url,
            'media_title' => $track->title,
            'status' => 'playing',
            'assigned_at' => now(),
            'started_at' => now(),
        ]);

        $intruder = $this->registerListener('intruder@example.com');
        $token = $this->listenerToken($intruder);

        $this->postJson(
            "/api/v1/rentals/assignments/{$assignment->id}/status",
            ['status' => 'playing'],
            ['Authorization' => 'Bearer ' . $token]
        )->assertForbidden();
    }

    public function test_rental_assignment_status_completes_and_rewards(): void
    {
        $this->seedBase();
        $this->mockFcm();

        $user = $this->registerListener();
        $this->registerUserDevice($user);
        $this->postJson('/api/v1/rentals/start', [], ['Authorization' => 'Bearer ' . $this->listenerToken($user)])->assertCreated();

        $track = $this->makeTrack(['duration_seconds' => 30]);
        $this->artisan('tracks:distribute');

        $assignment = DeviceAssignment::where('track_id', $track->id)->firstOrFail();
        $assignment->update(['started_at' => now()->subSeconds(45)]);

        $this->postJson(
            "/api/v1/rentals/assignments/{$assignment->id}/status",
            ['status' => 'completed'],
            ['Authorization' => 'Bearer ' . $this->listenerToken($user)]
        )->assertOk();

        $this->assertSame(StreamPlay::STATUS_REWARDED, StreamPlay::where('device_assignment_id', $assignment->id)->value('status'));
        $this->assertSame(5, (new WalletService())->balance($user->fresh()));
    }
}