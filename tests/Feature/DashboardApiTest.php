<?php

namespace Tests\Feature;

use App\Models\Campaign;
use App\Models\Device;
use App\Models\DeviceAssignment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardApiTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => User::ROLE_ADMIN]);
    }

    public function test_guest_cannot_access_dashboard_api(): void
    {
        $this->getJson('/dashboard-api/stats')->assertStatus(401);
    }

    public function test_non_admin_cannot_access_dashboard_api(): void
    {
        $listener = User::factory()->create(['role' => User::ROLE_LISTENER]);

        $this->actingAs($listener)->getJson('/dashboard-api/stats')->assertForbidden();
    }

    public function test_admin_can_fetch_dashboard_stats_without_api_key(): void
    {
        $this->actingAs($this->admin())
            ->getJson('/dashboard-api/stats')
            ->assertOk()
            ->assertJson(['success' => true])
            ->assertJsonStructure([
                'counts' => ['online', 'streaming', 'offline', 'total', 'activeTasks'],
            ]);
    }

    public function test_admin_can_create_campaign_without_api_key(): void
    {
        $this->actingAs($this->admin())
            ->postJson('/dashboard-api/campaigns', [
                'name' => 'Dashboard Campaign',
                'platform' => 'spotify',
                'tracks' => [
                    ['media_url' => 'https://example.com/track.mp3', 'media_title' => 'Track One', 'duration_seconds' => 180],
                ],
            ])
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('campaigns', ['name' => 'Dashboard Campaign']);
    }

    public function test_stale_device_is_marked_offline_without_stopping_its_campaign(): void
    {
        $device = Device::create([
            'device_id' => 'stale-device-1',
            'name' => 'Stale Phone',
            'fcm_token' => 'fcm-stale-1',
            'status' => 'streaming',
            'last_seen' => now()->subHour(),
        ]);

        $campaign = Campaign::create([
            'name' => 'Stale Campaign',
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

        $this->actingAs($this->admin())
            ->getJson('/dashboard-api/stats')
            ->assertOk();

        $this->assertSame('offline', $device->fresh()->status);
        $this->assertSame('playing', $assignment->fresh()->status);
    }
}
