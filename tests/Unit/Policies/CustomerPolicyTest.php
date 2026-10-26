<?php

namespace Tests\Unit\Policies;

use App\Enums\Role;
use App\Models\Customer;
use App\Models\User;
use App\Policies\CustomerPolicy;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class CustomerPolicyTest extends TestCase
{
    /**
     * @return iterable<string, array{Role, string, bool}>
     */
    public static function abilities(): iterable
    {
        $expected = [
            'owner' => ['viewAny' => true, 'view' => true, 'create' => true, 'update' => true, 'delete' => true],
            'manager' => ['viewAny' => true, 'view' => true, 'create' => true, 'update' => true, 'delete' => false],
            'cashier' => ['viewAny' => true, 'view' => true, 'create' => true, 'update' => true, 'delete' => false],
        ];

        foreach ($expected as $role => $abilities) {
            foreach ($abilities as $ability => $allowed) {
                yield "{$role} ".($allowed ? 'can' : 'cannot')." {$ability}" => [Role::from($role), $ability, $allowed];
            }
        }
    }

    #[Test]
    #[DataProvider('abilities')]
    public function it_follows_the_role_matrix(Role $role, string $ability, bool $allowed): void
    {
        $response = $this->decide(new User(['role' => $role]), $ability);

        $this->assertSame($allowed, $response->allowed());
    }

    #[Test]
    public function denial_message_names_the_role_and_permission(): void
    {
        $response = (new CustomerPolicy)->delete(new User(['role' => Role::Manager]), new Customer);

        $this->assertSame('Your role (manager) is not allowed to perform this action [customers.delete].', $response->message());
    }

    private function decide(User $user, string $ability)
    {
        $policy = new CustomerPolicy;

        return in_array($ability, ['viewAny', 'create'], true)
            ? $policy->{$ability}($user)
            : $policy->{$ability}($user, new Customer);
    }
}
