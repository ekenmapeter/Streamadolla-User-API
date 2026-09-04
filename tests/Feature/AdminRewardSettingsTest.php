<?php

namespace Tests\Feature;

use App\Models\AppSetting;
use App\Models\CountryReward;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminRewardSettingsTest extends TestCase
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

    public function test_reward_settings_page_renders(): void
    {
        $this->actingAs($this->admin())
            ->get(route('admin.rewards'))
            ->assertOk()
            ->assertSee('Reward Settings');
    }

    public function test_artist_cannot_access_reward_settings(): void
    {
        $artist = User::factory()->create(['role' => User::ROLE_ARTIST]);

        $this->actingAs($artist)
            ->get(route('admin.rewards'))
            ->assertForbidden();
    }

    public function test_admin_can_add_country_rate(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.rewards.store'), [
                'country_code' => 'gh',
                'country_name' => 'Ghana',
                'amount_per_listen' => 80,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('country_rewards', [
            'country_code' => 'GH',
            'country_name' => 'Ghana',
            'amount_per_listen' => 80,
            'is_active' => true,
        ]);
    }

    public function test_country_code_must_be_unique(): void
    {
        CountryReward::create([
            'country_code' => 'GH',
            'country_name' => 'Ghana',
            'amount_per_listen' => 80,
        ]);

        $this->actingAs($this->admin())
            ->post(route('admin.rewards.store'), [
                'country_code' => 'GH',
                'country_name' => 'Duplicate',
                'amount_per_listen' => 50,
            ])
            ->assertRedirect()
            ->assertSessionHasErrors('country_code');

        $this->assertDatabaseCount('country_rewards', 1);
    }

    public function test_admin_can_update_country_rate_and_toggle(): void
    {
        $country = CountryReward::create([
            'country_code' => 'GH',
            'country_name' => 'Ghana',
            'amount_per_listen' => 80,
        ]);

        $this->actingAs($this->admin())
            ->post(route('admin.rewards.update', $country), [
                'country_name' => 'Ghana (Updated)',
                'amount_per_listen' => 90,
                'is_active' => 1,
            ])
            ->assertRedirect();

        $country->refresh();
        $this->assertSame('Ghana (Updated)', $country->country_name);
        $this->assertSame(90, $country->amount_per_listen);
        $this->assertTrue($country->is_active);
    }

    public function test_admin_can_disable_country_rate(): void
    {
        $country = CountryReward::create([
            'country_code' => 'GH',
            'country_name' => 'Ghana',
            'amount_per_listen' => 80,
        ]);

        $this->actingAs($this->admin())
            ->post(route('admin.rewards.update', $country), [
                'country_name' => 'Ghana',
                'amount_per_listen' => 80,
            ])
            ->assertRedirect();

        $this->assertFalse($country->fresh()->is_active);
    }

    public function test_admin_can_delete_country_rate(): void
    {
        $country = CountryReward::create([
            'country_code' => 'GH',
            'country_name' => 'Ghana',
            'amount_per_listen' => 80,
        ]);

        $this->actingAs($this->admin())
            ->post(route('admin.rewards.destroy', $country))
            ->assertRedirect();

        $this->assertDatabaseMissing('country_rewards', ['id' => $country->id]);
    }

    public function test_admin_can_save_default_rate(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.rewards.default'), [
                'reward_per_listen_default' => 250,
            ])
            ->assertRedirect();

        $this->assertSame(250, (int) AppSetting::get('reward_per_listen_default'));
    }

    public function test_country_amount_for_falls_back_to_default(): void
    {
        AppSetting::updateOrCreate(
            ['key' => 'reward_per_listen_default'],
            ['value' => 175]
        );

        CountryReward::create([
            'country_code' => 'GH',
            'country_name' => 'Ghana',
            'amount_per_listen' => 80,
        ]);

        $this->assertSame(80, CountryReward::amountFor('gh'));
        $this->assertSame(175, CountryReward::amountFor('XX'));
        $this->assertSame(175, CountryReward::amountFor(null));
    }

    public function test_ip_lookup_tester_handles_private_ip(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.rewards.lookup'), ['ip' => '127.0.0.1'])
            ->assertRedirect()
            ->assertSessionHas('lookup_result');
    }
}