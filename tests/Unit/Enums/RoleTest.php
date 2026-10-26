<?php

namespace Tests\Unit\Enums;

use App\Enums\Permission;
use App\Enums\Role;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * The permission matrix from section 4 of the requirements, cell by cell.
 */
class RoleTest extends TestCase
{
    /**
     * @return iterable<string, array{Role, Permission, bool}>
     */
    public static function permissionMatrix(): iterable
    {
        $matrix = [
            //                     items: view  create update delete | customers: view create update delete
            Role::Owner->value => [true, true, true, true, true, true, true, true],
            Role::Manager->value => [true, true, true, false, true, true, true, false],
            Role::Cashier->value => [true, false, true, false, true, true, true, false],
        ];

        $permissions = [
            Permission::ViewItems, Permission::CreateItems, Permission::UpdateItems, Permission::DeleteItems,
            Permission::ViewCustomers, Permission::CreateCustomers, Permission::UpdateCustomers, Permission::DeleteCustomers,
        ];

        foreach ($matrix as $role => $expectations) {
            foreach ($permissions as $i => $permission) {
                $verb = $expectations[$i] ? 'can' : 'cannot';

                yield "{$role} {$verb} {$permission->value}" => [Role::from($role), $permission, $expectations[$i]];
            }
        }
    }

    #[Test]
    #[DataProvider('permissionMatrix')]
    public function role_grants_exactly_the_required_permissions(Role $role, Permission $permission, bool $expected): void
    {
        $this->assertSame($expected, $role->grants($permission));
    }

    #[Test]
    public function the_matrix_covers_every_permission(): void
    {
        $this->assertCount(8, Permission::cases(), 'Add the new permission to permissionMatrix().');
        $this->assertCount(3 * 8, iterator_to_array(self::permissionMatrix()));
    }

    #[Test]
    public function owner_has_every_permission(): void
    {
        $this->assertSame(Permission::cases(), Role::Owner->permissions());
    }

    #[Test]
    public function no_role_except_owner_can_delete(): void
    {
        foreach ([Role::Manager, Role::Cashier] as $role) {
            $this->assertFalse($role->grants(Permission::DeleteItems), "{$role->value} deletes items");
            $this->assertFalse($role->grants(Permission::DeleteCustomers), "{$role->value} deletes customers");
        }
    }

    #[Test]
    public function values_and_labels(): void
    {
        $this->assertSame(['owner', 'manager', 'cashier'], Role::values());
        $this->assertSame('Manager', Role::Manager->label());
    }
}
