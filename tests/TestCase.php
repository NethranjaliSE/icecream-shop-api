<?php

namespace Tests;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Laravel\Passport\Passport;

abstract class TestCase extends BaseTestCase
{
    /**
     * Authenticate the next requests as a fresh user with the given role.
     */
    protected function actingAsRole(Role $role): User
    {
        $user = User::factory()->role($role)->create();

        Passport::actingAs($user);

        return $user;
    }

    /**
     * The test client reuses one application per test, so the auth guard
     * remembers the last user. Forget it to make the next request
     * authenticate from its own Authorization header.
     */
    protected function forgetAuthentication(): void
    {
        $this->app['auth']->forgetGuards();
    }
}
