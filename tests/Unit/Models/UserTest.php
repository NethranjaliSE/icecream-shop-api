<?php

namespace Tests\Unit\Models;

use App\Enums\Permission;
use App\Enums\Role;
use App\Models\User;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class UserTest extends TestCase
{
    #[Test]
    public function role_is_cast_to_the_enum(): void
    {
        $user = new User(['role' => 'manager']);

        $this->assertSame(Role::Manager, $user->role);
        $this->assertTrue($user->hasRole(Role::Manager));
        $this->assertFalse($user->hasRole(Role::Owner));
    }

    #[Test]
    public function permissions_come_from_the_role(): void
    {
        $cashier = new User(['role' => Role::Cashier]);

        $this->assertTrue($cashier->hasPermission(Permission::UpdateItems));
        $this->assertFalse($cashier->hasPermission(Permission::CreateItems));
        $this->assertSame(
            ['items.view', 'items.update', 'customers.view', 'customers.create', 'customers.update'],
            $cashier->permissionNames(),
        );
    }

    #[Test]
    public function a_user_without_a_role_has_no_permissions(): void
    {
        $user = new User;

        $this->assertFalse($user->hasPermission(Permission::ViewItems));
        $this->assertSame([], $user->permissionNames());
    }
}
