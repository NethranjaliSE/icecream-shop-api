<?php

namespace Tests\Feature\Auth;

use App\Enums\Role;
use App\Models\User;
use Database\Seeders\PassportClientSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Laravel\Passport\Passport;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Runs the real Passport flow: tokens are issued and validated, not faked.
 */
class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        if (! file_exists(Passport::keyPath('oauth-private.key'))) {
            Artisan::call('passport:keys', ['--length' => 2048]);
        }

        $this->seed([PassportClientSeeder::class, UserSeeder::class]);
    }

    /**
     * @return array<string, array{string, Role, int}>
     */
    public static function seededAccounts(): array
    {
        return [
            'owner' => ['owner@icecream.test', Role::Owner, 8],
            'manager' => ['manager@icecream.test', Role::Manager, 6],
            'cashier' => ['cashier@icecream.test', Role::Cashier, 5],
        ];
    }

    #[Test]
    #[DataProvider('seededAccounts')]
    public function each_seeded_role_can_log_in(string $email, Role $role, int $permissionCount): void
    {
        $response = $this->postJson('/api/auth/login', [
            'email' => $email,
            'password' => UserSeeder::PASSWORD,
        ]);

        $response->assertOk()
            ->assertJsonStructure(['token_type', 'access_token', 'expires_at', 'user' => ['id', 'name', 'email', 'role', 'permissions']])
            ->assertJsonPath('token_type', 'Bearer')
            ->assertJsonPath('user.email', $email)
            ->assertJsonPath('user.role', $role->value)
            ->assertJsonCount($permissionCount, 'user.permissions');

        $this->assertNotEmpty($response->json('access_token'));
    }

    #[Test]
    public function the_issued_token_authenticates_api_requests(): void
    {
        $token = $this->login('manager@icecream.test');

        $this->withToken($token)
            ->getJson('/api/auth/me')
            ->assertOk()
            ->assertJsonPath('data.email', 'manager@icecream.test')
            ->assertJsonPath('data.role', 'manager');
    }

    #[Test]
    public function email_is_case_insensitive(): void
    {
        $this->postJson('/api/auth/login', ['email' => ' Owner@IceCream.TEST ', 'password' => 'password'])
            ->assertOk();
    }

    #[Test]
    public function token_expires_after_the_configured_lifetime(): void
    {
        $this->freezeSecond();

        $response = $this->postJson('/api/auth/login', ['email' => 'owner@icecream.test', 'password' => 'password']);

        $this->assertSame(
            now()->addHours(config('auth.token_ttl_hours'))->toIso8601String(),
            $response->json('expires_at'),
        );
    }

    #[Test]
    public function wrong_password_is_rejected(): void
    {
        $this->postJson('/api/auth/login', ['email' => 'owner@icecream.test', 'password' => 'not-the-password'])
            ->assertUnauthorized()
            ->assertJsonPath('message', __('auth.failed'))
            ->assertJsonMissingPath('access_token');
    }

    #[Test]
    public function unknown_email_is_rejected_with_the_same_message(): void
    {
        $this->postJson('/api/auth/login', ['email' => 'nobody@icecream.test', 'password' => 'password'])
            ->assertUnauthorized()
            ->assertJsonPath('message', __('auth.failed'));
    }

    #[Test]
    public function login_validates_its_input(): void
    {
        $this->postJson('/api/auth/login', ['email' => 'not-an-email'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['email', 'password']);
    }

    #[Test]
    public function login_is_rate_limited(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/auth/login', ['email' => 'owner@icecream.test', 'password' => 'wrong'])
                ->assertUnauthorized();
        }

        $this->postJson('/api/auth/login', ['email' => 'owner@icecream.test', 'password' => 'password'])
            ->assertTooManyRequests();
    }

    #[Test]
    public function logout_revokes_the_token(): void
    {
        $token = $this->login('cashier@icecream.test');

        $this->withToken($token)->postJson('/api/auth/logout')
            ->assertOk()
            ->assertJsonPath('message', 'Logged out successfully.');

        $this->forgetAuthentication();

        $this->withToken($token)->getJson('/api/auth/me')->assertUnauthorized();
        $this->withToken($token)->getJson('/api/items')->assertUnauthorized();
    }

    #[Test]
    public function logging_out_one_device_keeps_other_tokens_valid(): void
    {
        $phone = $this->login('owner@icecream.test');
        $till = $this->login('owner@icecream.test');

        $this->withToken($phone)->postJson('/api/auth/logout')->assertOk();
        $this->forgetAuthentication();

        $this->withToken($till)->getJson('/api/auth/me')->assertOk();
    }

    #[Test]
    public function protected_endpoints_require_a_token(): void
    {
        $this->getJson('/api/auth/me')->assertUnauthorized()->assertJsonPath('message', 'Unauthenticated.');
        $this->postJson('/api/auth/logout')->assertUnauthorized();
    }

    #[Test]
    public function an_invalid_token_is_rejected(): void
    {
        $this->withToken('not-a-real-token')->getJson('/api/auth/me')->assertUnauthorized();
    }

    #[Test]
    public function api_errors_are_json_even_without_an_accept_header(): void
    {
        $this->get('/api/items')
            ->assertUnauthorized()
            ->assertHeader('Content-Type', 'application/json')
            ->assertJsonPath('message', 'Unauthenticated.');
    }

    #[Test]
    public function a_deleted_users_token_stops_working(): void
    {
        $token = $this->login('manager@icecream.test');

        User::where('email', 'manager@icecream.test')->delete();
        $this->forgetAuthentication();

        $this->withToken($token)->getJson('/api/auth/me')->assertUnauthorized();
    }

    private function login(string $email): string
    {
        return $this->postJson('/api/auth/login', ['email' => $email, 'password' => UserSeeder::PASSWORD])
            ->assertOk()
            ->json('access_token');
    }
}
