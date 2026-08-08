<?php

namespace Tests\Feature;

use App\Jobs\DistributeCampaignJob;
use App\Models\AppSetting;
use App\Models\CampaignAssignment;
use App\Models\CampaignPackage;
use App\Models\ListenSession;
use App\Models\PayoutRequest;
use App\Models\PromoCampaign;
use App\Models\User;
use App\Models\WalletTransaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class AdminCommandCenterTest extends TestCase
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

    private function listener(string $email = 'bravo@example.com'): User
    {
        return User::factory()->create([
            'name' => 'Bravo',
            'email' => $email,
            'role' => User::ROLE_LISTENER,
        ]);
    }

    private function campaign(User $artist, string $status = PromoCampaign::STATUS_DRAFT): PromoCampaign
    {
        $package = CampaignPackage::create([
            'name' => 'Starter',
            'price_ngn' => 30000,
            'listen_target' => 100,
            'review_target' => 50,
            'margin_pct' => 35,
        ]);

        return PromoCampaign::create([
            'artist_id' => $artist->id,
            'package_id' => $package->id,
            'title' => 'Summer Jam',
            'track_url' => 'https://music.example.com/summer.mp3',
            'platform' => 'spotify',
            'genres' => ['afrobeats'],
            'reward_per_review' => 150,
            'review_target' => 50,
            'listen_target' => 100,
            'status' => $status,
        ]);
    }

    private function sessionFor(User $listener, PromoCampaign $campaign, string $status = ListenSession::STATUS_REWARDED): ListenSession
    {
        $assignment = CampaignAssignment::create([
            'campaign_id' => $campaign->id,
            'listener_id' => $listener->id,
            'status' => CampaignAssignment::STATUS_REVIEWED,
        ]);

        return ListenSession::create([
            'listener_id' => $listener->id,
            'assignment_id' => $assignment->id,
            'min_duration_seconds' => 30,
            'elapsed_seconds' => 45,
            'checkpoints' => [],
            'foreground' => true,
            'status' => $status,
            'started_at' => now()->subHour(),
            'completed_at' => $status === ListenSession::STATUS_OPEN ? null : now(),
        ]);
    }

    public function test_overview_renders_only_for_admin(): void
    {
        $artist = User::factory()->create(['role' => User::ROLE_ARTIST]);
        $this->campaign($artist);

        $this->actingAs($this->admin())
            ->get(route('admin.center'))
            ->assertOk()
            ->assertSee('Command Center');
    }

    public function test_artist_cannot_access_admin_area(): void
    {
        $artist = User::factory()->create(['role' => User::ROLE_ARTIST]);

        $this->actingAs($artist)
            ->get(route('admin.center'))
            ->assertForbidden();
    }

    public function test_admin_can_activate_campaign_and_job_dispatched(): void
    {
        Queue::fake();

        $artist = User::factory()->create(['role' => User::ROLE_ARTIST]);
        $campaign = $this->campaign($artist, PromoCampaign::STATUS_PENDING_PAYMENT);

        $this->actingAs($this->admin())
            ->post(route('admin.campaigns.activate', $campaign))
            ->assertRedirect();

        $this->assertSame(PromoCampaign::STATUS_ACTIVE, $campaign->fresh()->status);
        Queue::assertPushed(DistributeCampaignJob::class);
    }

    public function test_admin_can_pause_campaign(): void
    {
        $artist = User::factory()->create(['role' => User::ROLE_ARTIST]);
        $campaign = $this->campaign($artist, PromoCampaign::STATUS_ACTIVE);

        $this->actingAs($this->admin())
            ->post(route('admin.campaigns.pause', $campaign))
            ->assertRedirect();

        $this->assertSame(PromoCampaign::STATUS_PAUSED, $campaign->fresh()->status);
    }

    public function test_overview_shows_session_stats(): void
    {
        $listener = $this->listener();
        $artist = User::factory()->create(['role' => User::ROLE_ARTIST]);
        $campaign = $this->campaign($artist);
        $this->sessionFor($listener, $campaign, ListenSession::STATUS_REWARDED);
        $this->sessionFor($this->listener('charlie@example.com'), $campaign, ListenSession::STATUS_FRAUD);

        $this->actingAs($this->admin())
            ->get(route('admin.center'))
            ->assertOk()
            ->assertSee('Command Center');
    }

    public function test_admin_can_mark_payout_paid(): void
    {
        $listener = $this->listener();
        $payout = PayoutRequest::create([
            'user_id' => $listener->id,
            'amount' => 1500,
            'method' => 'bank',
            'destination' => ['bank_code' => '044', 'account_number' => '0123456789'],
            'status' => PayoutRequest::STATUS_REQUESTED,
            'hold_until_at' => now()->addHours(72),
        ]);

        $this->actingAs($this->admin())
            ->post(route('admin.payouts.mark-paid', $payout))
            ->assertRedirect();

        $this->assertSame(PayoutRequest::STATUS_PAID, $payout->fresh()->status);
        $this->assertNotNull($payout->fresh()->paid_at);
    }

    public function test_admin_can_reject_payout_and_funds_returned(): void
    {
        $listener = $this->listener();
        $payout = PayoutRequest::create([
            'user_id' => $listener->id,
            'amount' => 2000,
            'method' => 'airtime',
            'destination' => ['phone' => '08012345678'],
            'status' => PayoutRequest::STATUS_REQUESTED,
            'hold_until_at' => now()->addDay(),
        ]);

        $this->actingAs($this->admin())
            ->post(route('admin.payouts.reject', $payout), ['note' => 'Invalid account'])
            ->assertRedirect();

        $payout->refresh();
        $this->assertSame(PayoutRequest::STATUS_REJECTED, $payout->status);
        $this->assertSame('Invalid account', $payout->note);
        $this->assertDatabaseHas('wallet_transactions', [
            'user_id' => $listener->id,
            'type' => WalletTransaction::TYPE_BONUS,
            'amount' => 2000,
        ]);
    }

    public function test_admin_can_save_app_settings(): void
    {
        $this->seed(\Database\Seeders\AudioReachSeeder::class);

        $this->actingAs($this->admin())
            ->post(route('admin.settings.save'), [
                'settings' => [
                    ['key' => 'min_payout', 'value' => '2500', 'group' => 'rewards'],
                    ['key' => 'daily_review_limit', 'value' => '10', 'group' => 'limits'],
                ],
            ])
            ->assertRedirect();

        $this->assertSame(2500, (int) AppSetting::get('min_payout'));
        $this->assertSame(10, (int) AppSetting::get('daily_review_limit'));
    }

    public function test_admin_can_set_listener_trust_level(): void
    {
        $listener = $this->listener();

        $this->actingAs($this->admin())
            ->post(route('admin.listeners.trust', $listener), ['trust_level' => 2])
            ->assertRedirect();

        $this->assertDatabaseHas('listener_profiles', [
            'user_id' => $listener->id,
            'trust_level' => 2,
        ]);
    }

    public function test_overview_lists_recent_sessions(): void
    {
        $listener = $this->listener();
        $artist = User::factory()->create(['role' => User::ROLE_ARTIST]);
        $campaign = $this->campaign($artist);
        $this->sessionFor($listener, $campaign, ListenSession::STATUS_REWARDED);

        $this->actingAs($this->admin())
            ->get(route('admin.center'))
            ->assertOk()
            ->assertSee('Recent Listens');
    }

    public function test_listeners_page_renders_with_metrics_and_table(): void
    {
        $listener = $this->listener();
        $artist = User::factory()->create(['role' => User::ROLE_ARTIST]);
        $campaign = $this->campaign($artist);
        $this->sessionFor($listener, $campaign, ListenSession::STATUS_REWARDED);

        $this->actingAs($this->admin())
            ->get(route('admin.listeners'))
            ->assertOk()
            ->assertSee('Total Listeners')
            ->assertSee('All Listeners')
            ->assertSee($listener->name);
    }

    public function test_listeners_page_filters_and_sorts(): void
    {
        $this->listener('alpha@example.com');
        $this->listener('bravo@example.com');

        $admin = $this->admin();

        $this->actingAs($admin)
            ->get(route('admin.listeners', ['q' => 'alpha']))
            ->assertOk()
            ->assertSee('alpha@example.com')
            ->assertDontSee('bravo@example.com');

        $this->actingAs($admin)
            ->get(route('admin.listeners', ['trust' => 0]))
            ->assertOk()
            ->assertSee('All Listeners');

        $this->actingAs($admin)
            ->get(route('admin.listeners', ['sort' => 'earned']))
            ->assertOk();
    }

    public function test_api_docs_page_renders_both_zones(): void
    {
        $this->actingAs($this->admin())
            ->get(route('admin.api-docs'))
            ->assertOk()
            ->assertSee('Fleet Zone')
            ->assertSee('AudioReach v1')
            ->assertSee('/api/v1/auth/register')
            ->assertSee('X-API-Key');
    }
}