<?php

namespace Tests\Feature;

use App\Models\AppSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class ArtistPortalTest extends TestCase
{
    use RefreshDatabase;

    private function verificationCode(string $email): string
    {
        $entry = Cache::get('email_verify:' . $email);

        $this->assertNotNull($entry, 'Verification code was not issued.');

        return $entry['code'];
    }

    public function test_landing_page_renders(): void
    {
        $this->get('/')->assertOk()->assertSee('Streamadollar');
    }

    public function test_artist_signup_creates_user_and_profile(): void
    {
        $this->seed(\Database\Seeders\AudioReachSeeder::class);

        $this->post('/artists/signup', [
            'name' => 'Tunde Bello',
            'stage_name' => 'TB Swag',
            'email' => 'TB@Example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertRedirect(route('artist.verify', ['email' => 'tb@example.com']));

        $user = User::where('email', 'tb@example.com')->first();

        $this->assertNotNull($user);
        $this->assertSame(User::ROLE_ARTIST, $user->role);
        $this->assertFalse((bool) $user->email_verified_at);
        $this->assertDatabaseHas('artist_profiles', ['user_id' => $user->id, 'stage_name' => 'TB Swag']);
        $this->assertNotNull(Cache::get('email_verify:tb@example.com'), 'Verification code should be issued on signup.');
    }

    public function test_artist_verify_with_code_logs_in_and_redirects(): void
    {
        $this->seed(\Database\Seeders\AudioReachSeeder::class);

        $this->post('/artists/signup', [
            'name' => 'Tunde Bello',
            'stage_name' => 'DJ Swag',
            'email' => 'tb@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $code = $this->verificationCode('tb@example.com');

        $this->post('/artists/verify', [
            'email' => 'tb@example.com',
            'code' => $code,
        ])->assertRedirect(route('artist.dashboard'));

        $user = User::where('email', 'tb@example.com')->first();

        $this->assertTrue((bool) $user->email_verified_at);
        $this->assertAuthenticatedAs($user);
        $this->assertNull(Cache::get('email_verify:tb@example.com'), 'Code should be consumed after successful verify.');
    }

    public function test_artist_verify_rejects_wrong_code(): void
    {
        $this->seed(\Database\Seeders\AudioReachSeeder::class);

        $this->post('/artists/signup', [
            'name' => 'Tunde Bello',
            'stage_name' => 'DJ Swag',
            'email' => 'tb@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $this->post('/artists/verify', [
            'email' => 'tb@example.com',
            'code' => '000000',
        ])->assertSessionHasErrors('code');

        $this->assertGuest();
    }

    public function test_login_redirects_unverified_artist_to_verify_page(): void
    {
        $user = User::factory()->create([
            'email' => 'artist@example.com',
            'password' => bcrypt('password123'),
            'role' => User::ROLE_ARTIST,
            'email_verified_at' => null,
        ]);

        $this->post('/login', [
            'email' => 'artist@example.com',
            'password' => 'password123',
        ])->assertRedirect(route('artist.verify', ['email' => 'artist@example.com']));
    }

    public function test_login_allows_verified_artist_into_dashboard(): void
    {
        $user = User::factory()->create([
            'email' => 'verified@example.com',
            'password' => bcrypt('password123'),
            'role' => User::ROLE_ARTIST,
        ]);

        $this->post('/login', [
            'email' => 'verified@example.com',
            'password' => 'password123',
        ])->assertRedirect(route('artist.dashboard'));

        $this->get(route('artist.dashboard'))->assertOk()->assertSee('Dashboard');
    }

    public function test_listener_cannot_access_artist_portal(): void
    {
        $listener = User::factory()->create([
            'email' => 'listener@example.com',
            'role' => User::ROLE_LISTENER,
        ]);

        $this->actingAs($listener)
            ->get(route('artist.dashboard'))
            ->assertForbidden();
    }

    public function test_guest_is_redirected_to_login_for_artist_routes(): void
    {
        $this->get(route('artist.dashboard'))->assertRedirect(route('login'));
    }

    public function test_logout_ends_session(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_ARTIST]);

        $this->actingAs($user)
            ->post(route('logout'))
            ->assertRedirect(route('login'));

        $this->assertGuest();
    }

    public function test_campaign_min_payout_setting_seeded(): void
    {
        $this->seed(\Database\Seeders\AudioReachSeeder::class);

        $this->assertSame(1000, (int) AppSetting::get('min_payout'));
    }
}