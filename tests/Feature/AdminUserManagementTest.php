<?php

namespace Tests\Feature;

use App\Models\CampaignAssignment;
use App\Models\CampaignPackage;
use App\Models\ListenSession;
use App\Models\ListenerProfile;
use App\Models\PromoCampaign;
use App\Models\User;
use App\Models\WalletTransaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminUserManagementTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create([
            'name' => 'Ops Admin',
            'email' => 'admin@audioreach.app',
            'role' => User::ROLE_ADMIN,
        ]);
    }

    public function test_admin_can_view_create_user_form(): void
    {
        $this->actingAs($this->admin())
            ->get(route('admin.users.create'))
            ->assertOk()
            ->assertSee('Create User');
    }

    public function test_admin_can_create_listener(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.users.store'), [
                'name' => 'New Listener',
                'email' => 'new-listener@example.com',
                'password' => 'password123',
                'phone' => '08012345678',
                'role' => 'listener',
                'status' => 'active',
                'genre_prefs' => 'afrobeats, highlife',
                'trust_level' => 2,
            ])
            ->assertRedirect();

        $user = User::where('email', 'new-listener@example.com')->firstOrFail();
        $this->assertSame(User::ROLE_LISTENER, $user->role);
        $this->assertSame('active', $user->status);
        $this->assertTrue(\Illuminate\Support\Facades\Hash::check('password123', $user->password));
        $this->assertDatabaseHas('listener_profiles', [
            'user_id' => $user->id,
            'trust_level' => 2,
        ]);
        $this->assertSame(['afrobeats', 'highlife'], $user->listenerProfile->genre_prefs);
    }

    public function test_admin_can_create_artist(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.users.store'), [
                'name' => 'New Artist',
                'email' => 'new-artist@example.com',
                'password' => 'password123',
                'role' => 'artist',
                'status' => 'active',
                'stage_name' => 'DJ Fresh',
            ])
            ->assertRedirect();

        $user = User::where('email', 'new-artist@example.com')->firstOrFail();
        $this->assertSame(User::ROLE_ARTIST, $user->role);
        $this->assertDatabaseHas('artist_profiles', [
            'user_id' => $user->id,
            'stage_name' => 'DJ Fresh',
        ]);
    }

    public function test_email_must_be_unique_on_create(): void
    {
        User::factory()->create(['email' => 'dupe@example.com', 'role' => User::ROLE_LISTENER]);

        $this->actingAs($this->admin())
            ->post(route('admin.users.store'), [
                'name' => 'Dupe',
                'email' => 'dupe@example.com',
                'password' => 'password123',
                'role' => 'listener',
                'status' => 'active',
            ])
            ->assertRedirect()
            ->assertSessionHasErrors('email');
    }

    public function test_admin_can_edit_user(): void
    {
        $listener = User::factory()->create(['role' => User::ROLE_LISTENER, 'status' => 'active']);
        ListenerProfile::create(['user_id' => $listener->id, 'genre_prefs' => [], 'trust_level' => 0]);

        $this->actingAs($this->admin())
            ->post(route('admin.users.update', $listener), [
                'name' => 'Renamed',
                'email' => 'renamed@example.com',
                'phone' => '0811111111',
                'password' => '',
                'role' => 'listener',
                'status' => 'banned',
                'genre_prefs' => 'gospel',
                'trust_level' => 3,
            ])
            ->assertRedirect();

        $listener->refresh();
        $this->assertSame('Renamed', $listener->name);
        $this->assertSame('renamed@example.com', $listener->email);
        $this->assertSame('banned', $listener->status);
        $this->assertSame(3, $listener->listenerProfile->trust_level);
        $this->assertSame(['gospel'], $listener->listenerProfile->genre_prefs);
    }

    public function test_admin_can_change_password(): void
    {
        $listener = User::factory()->create(['role' => User::ROLE_LISTENER]);

        $this->actingAs($this->admin())
            ->post(route('admin.users.update', $listener), [
                'name' => $listener->name,
                'email' => $listener->email,
                'password' => 'brandnewpass',
                'role' => 'listener',
                'status' => 'active',
            ])
            ->assertRedirect();

        $this->assertTrue(\Illuminate\Support\Facades\Hash::check('brandnewpass', $listener->fresh()->password));
    }

    public function test_admin_can_suspend_and_reactivate_user(): void
    {
        $admin = $this->admin();
        $listener = User::factory()->create(['role' => User::ROLE_LISTENER, 'status' => 'active']);

        $this->actingAs($admin)
            ->post(route('admin.users.suspend', $listener))
            ->assertRedirect();

        $this->assertSame('suspended', $listener->fresh()->status);

        $this->actingAs($admin)
            ->post(route('admin.users.activate', $listener))
            ->assertRedirect();

        $this->assertSame('active', $listener->fresh()->status);
    }

    public function test_admin_can_soft_delete_and_restore_user(): void
    {
        $admin = $this->admin();
        $listener = User::factory()->create(['role' => User::ROLE_LISTENER]);
        $artist = User::factory()->create(['role' => User::ROLE_ARTIST]);
        $package = CampaignPackage::create(['name' => 'Starter', 'price_ngn' => 30000, 'listen_target' => 100, 'review_target' => 50, 'margin_pct' => 35]);
        $campaign = PromoCampaign::create([
            'artist_id' => $artist->id,
            'package_id' => $package->id,
            'title' => 'Cascade Test',
            'track_url' => 'https://music.example.com/t.mp3',
            'platform' => 'spotify',
            'genres' => ['afrobeats'],
            'reward_per_review' => 100,
            'review_target' => 50,
            'listen_target' => 100,
            'status' => PromoCampaign::STATUS_ACTIVE,
        ]);
        $assignment = CampaignAssignment::create(['campaign_id' => $campaign->id, 'listener_id' => $listener->id, 'status' => CampaignAssignment::STATUS_ASSIGNED]);
        ListenSession::create([
            'listener_id' => $listener->id,
            'assignment_id' => $assignment->id,
            'min_duration_seconds' => 30,
            'elapsed_seconds' => 40,
            'checkpoints' => [],
            'foreground' => true,
            'status' => ListenSession::STATUS_REWARDED,
            'started_at' => now()->subHour(),
            'completed_at' => now(),
        ]);
        WalletTransaction::create([
            'user_id' => $listener->id,
            'reference' => WalletTransaction::nextReference(),
            'type' => WalletTransaction::TYPE_REWARD,
            'amount' => 100,
            'balance_before' => 0,
            'balance_after' => 100,
            'status' => WalletTransaction::STATUS_CREDITED,
            'meta' => [],
        ]);
        ListenerProfile::create(['user_id' => $listener->id, 'genre_prefs' => [], 'trust_level' => 0]);

        $this->actingAs($admin)
            ->post(route('admin.users.delete', $listener))
            ->assertRedirect(route('admin.listeners'));

        // Soft deleted: row exists with deleted_at, excluded from normal queries
        $this->assertSoftDeleted('users', ['id' => $listener->id]);
        $this->assertSame('deleted', User::withTrashed()->find($listener->id)->status);
        $this->assertNull(User::find($listener->id));

        // Related history is retained
        $this->assertDatabaseHas('wallet_transactions', ['user_id' => $listener->id]);
        $this->assertDatabaseHas('listen_sessions', ['listener_id' => $listener->id]);
        $this->assertDatabaseHas('listener_profiles', ['user_id' => $listener->id]);
        $this->assertDatabaseHas('campaign_assignments', ['listener_id' => $listener->id]);

        // Restore brings the account back to active
        $this->actingAs($admin)
            ->post(route('admin.users.restore', $listener))
            ->assertRedirect();

        $this->assertNotSoftDeleted('users', ['id' => $listener->id]);
        $this->assertSame('active', User::find($listener->id)->status);
        $this->assertDatabaseHas('wallet_transactions', ['user_id' => $listener->id]);
    }

    public function test_deleted_users_listed_under_deleted_filter(): void
    {
        $admin = $this->admin();
        $listener = User::factory()->create(['role' => User::ROLE_LISTENER]);
        ListenerProfile::create(['user_id' => $listener->id, 'genre_prefs' => [], 'trust_level' => 0]);

        $listener->status = 'deleted';
        $listener->save();
        $listener->delete();

        $this->actingAs($admin)
            ->get(route('admin.listeners', ['status' => 'deleted']))
            ->assertOk()
            ->assertSee($listener->name);

        $this->actingAs($admin)
            ->get(route('admin.listeners'))
            ->assertOk()
            ->assertDontSee($listener->name);
    }

    public function test_admin_cannot_delete_self(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin);

        $this->post(route('admin.users.delete', $admin))
            ->assertForbidden();

        $this->assertDatabaseHas('users', ['id' => $admin->id]);
    }

    public function test_suspended_listener_cannot_login(): void
    {
        User::factory()->create([
            'role' => User::ROLE_LISTENER,
            'status' => 'suspended',
            'email' => 'suspended@example.com',
            'password' => bcrypt('password123'),
            'email_verified_at' => now(),
        ]);

        $this->postJson('/api/v1/auth/login', [
            'email' => 'suspended@example.com',
            'password' => 'password123',
        ])->assertStatus(403)->assertJson(['success' => false]);
    }

    public function test_admin_cannot_suspend_admin_account(): void
    {
        $otherAdmin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $this->actingAs($this->admin())
            ->post(route('admin.users.suspend', $otherAdmin))
            ->assertForbidden();
    }

    public function test_detail_page_shows_profile_actions_and_footprint(): void
    {
        $listener = User::factory()->create(['role' => User::ROLE_LISTENER, 'status' => 'active']);
        ListenerProfile::create(['user_id' => $listener->id, 'genre_prefs' => ['afrobeats'], 'trust_level' => 1]);

        $artist = User::factory()->create(['role' => User::ROLE_ARTIST]);
        $package = CampaignPackage::create(['name' => 'Starter', 'price_ngn' => 30000, 'listen_target' => 100, 'review_target' => 50, 'margin_pct' => 35]);
        $campaign = PromoCampaign::create([
            'artist_id' => $artist->id,
            'package_id' => $package->id,
            'title' => 'Footprint Track',
            'track_url' => 'https://music.example.com/f.mp3',
            'platform' => 'spotify',
            'genres' => ['afrobeats'],
            'reward_per_review' => 100,
            'review_target' => 50,
            'listen_target' => 100,
            'status' => PromoCampaign::STATUS_ACTIVE,
        ]);
        $assignment = CampaignAssignment::create(['campaign_id' => $campaign->id, 'listener_id' => $listener->id, 'status' => CampaignAssignment::STATUS_ASSIGNED]);
        ListenSession::create([
            'listener_id' => $listener->id,
            'assignment_id' => $assignment->id,
            'country_code' => 'NG',
            'ip_address' => '197.210.64.1',
            'min_duration_seconds' => 30,
            'elapsed_seconds' => 40,
            'checkpoints' => [],
            'foreground' => true,
            'status' => ListenSession::STATUS_REWARDED,
            'started_at' => now()->subHour(),
            'completed_at' => now(),
        ]);

        $this->actingAs($this->admin())
            ->get(route('admin.listeners.detail', $listener))
            ->assertOk()
            ->assertSee('Edit')
            ->assertSee('Suspend')
            ->assertSee('Delete')
            ->assertSee('User Footprint')
            ->assertSee('197.210.64.1')
            ->assertSee('NG');
    }
}