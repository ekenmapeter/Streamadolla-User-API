<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FleetApiTest extends TestCase
{
    use RefreshDatabase;

    private const VALID_KEY = 'fleet-test-key-123';

    protected function setUp(): void
    {
        parent::setUp();

        config(['app.api_key' => self::VALID_KEY]);
    }

    public function test_api_requires_key(): void
    {
        $this->seed(\Database\Seeders\AudioReachSeeder::class);

        $this->getJson('/api/campaigns')->assertStatus(401);
        $this->getJson('/api/devices')->assertStatus(401);
    }

    public function test_api_rejects_wrong_key(): void
    {
        $this->seed(\Database\Seeders\AudioReachSeeder::class);

        $this->getJson('/api/campaigns', ['X-API-Key' => 'wrong-key'])->assertStatus(401);
    }

    public function test_api_accepts_valid_key(): void
    {
        $this->seed(\Database\Seeders\AudioReachSeeder::class);

        $this->getJson('/api/devices', ['X-API-Key' => self::VALID_KEY])
            ->assertOk()
            ->assertJson(['success' => true]);
    }

    public function test_authenticated_route_can_create_campaign_via_fleet_api(): void
    {
        $this->seed(\Database\Seeders\AudioReachSeeder::class);

        $this->postJson('/api/campaigns', [
            'name' => 'Fleet Campaign',
            'platform' => 'spotify',
            'tracks' => [
                ['media_url' => 'https://example.com/track.mp3', 'media_title' => 'Track One', 'duration_seconds' => 180],
            ],
        ], ['X-API-Key' => self::VALID_KEY])
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('campaigns', ['name' => 'Fleet Campaign']);
    }

    public function test_guest_cannot_access_campaign_via_fleet_api(): void
    {
        $this->seed(\Database\Seeders\AudioReachSeeder::class);

        $this->postJson('/api/campaigns', [
            'name' => 'No Auth Campaign',
            'platform' => 'spotify',
            'tracks' => [['media_url' => 'https://example.com/track.mp3']],
        ])->assertStatus(401);

        $this->assertDatabaseMissing('campaigns', ['name' => 'No Auth Campaign']);
    }
}