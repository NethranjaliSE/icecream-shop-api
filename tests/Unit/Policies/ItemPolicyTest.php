<?php

namespace Tests\Unit\Policies;

use App\Enums\Role;
use App\Models\Item;
use App\Models\User;
use App\Policies\ItemPolicy;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class ItemPolicyTest extends TestCase
{
    /**
     * @return iterable<string, array{Role, string, bool}>
     */
    public static function abilities(): iterable
    {
        $expected = [
            'owner' => ['viewAny' => true, 'view' => true, 'create' => true, 'update' => true, 'delete' => true],
            'manager' => ['viewAny' => true, 'view' => true, 'create' => true, 'update' => true, 'delete' => false],
            'cashier' => ['viewAny' => true, 'view' => true, 'create' => false, 'update' => true, 'delete' => false],
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
        $response = (new ItemPolicy)->create(new User(['role' => Role::Cashier]));

        $this->assertTrue($response->denied());
        $this->assertSame('Your role (cashier) is not allowed to perform this action [items.create].', $response->message());
    }

    #[Test]
    public function a_user_without_a_role_is_denied_everything(): void
    {
        foreach (['viewAny', 'view', 'create', 'update', 'delete'] as $ability) {
            $this->assertTrue($this->decide(new User, $ability)->denied(), $ability);
        }
    }

    private function decide(User $user, string $ability)
    {
        $policy = new ItemPolicy;

        return in_array($ability, ['viewAny', 'create'], true)
            ? $policy->{$ability}($user)
            : $policy->{$ability}($user, new Item);
    }
}
