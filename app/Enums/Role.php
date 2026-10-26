<?php

namespace App\Enums;

enum Role: string
{
    case Owner = 'owner';
    case Manager = 'manager';
    case Cashier = 'cashier';

    /**
     * The permission matrix from the business requirements.
     *
     * | Role    | Items                        | Customers                    |
     * |---------|------------------------------|------------------------------|
     * | Owner   | view, create, update, delete | view, create, update, delete |
     * | Manager | view, create, update         | view, create, update         |
     * | Cashier | view, update                 | view, create, update         |
     *
     * @return list<Permission>
     */
    public function permissions(): array
    {
        return match ($this) {
            self::Owner => Permission::cases(),

            self::Manager => [
                Permission::ViewItems,
                Permission::CreateItems,
                Permission::UpdateItems,
                Permission::ViewCustomers,
                Permission::CreateCustomers,
                Permission::UpdateCustomers,
            ],

            self::Cashier => [
                Permission::ViewItems,
                Permission::UpdateItems,
                Permission::ViewCustomers,
                Permission::CreateCustomers,
                Permission::UpdateCustomers,
            ],
        };
    }

    public function grants(Permission $permission): bool
    {
        return in_array($permission, $this->permissions(), true);
    }

    public function label(): string
    {
        return ucfirst($this->value);
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
