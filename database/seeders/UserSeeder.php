<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * One account per role. All of them use the password "password".
 */
class UserSeeder extends Seeder
{
    public const PASSWORD = 'password';

    public const ACCOUNTS = [
        ['name' => 'Shop Owner', 'email' => 'owner@icecream.test', 'role' => Role::Owner],
        ['name' => 'Shop Manager', 'email' => 'manager@icecream.test', 'role' => Role::Manager],
        ['name' => 'Shop Cashier', 'email' => 'cashier@icecream.test', 'role' => Role::Cashier],
    ];

    public function run(): void
    {
        foreach (self::ACCOUNTS as $account) {
            User::updateOrCreate(
                ['email' => $account['email']],
                [
                    'name' => $account['name'],
                    'role' => $account['role'],
                    'password' => self::PASSWORD,
                    'email_verified_at' => now(),
                ],
            );
        }
    }
}
