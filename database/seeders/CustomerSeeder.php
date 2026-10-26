<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Database\Seeder;

class CustomerSeeder extends Seeder
{
    private const CUSTOMERS = [
        ['name' => 'Amara Silva', 'email' => 'amara.silva@example.com', 'phone' => '+94 71 234 5678', 'address' => '12 Beach Road, Negombo', 'loyalty_points' => 120],
        ['name' => 'Kevin Fernando', 'email' => 'kevin.fernando@example.com', 'phone' => '+94 77 345 6789', 'address' => '45 Lewis Place, Negombo', 'loyalty_points' => 45],
        ['name' => 'Nimali Perera', 'email' => 'nimali.perera@example.com', 'phone' => '+94 76 456 7890', 'address' => '8 Main Street, Colombo 03', 'loyalty_points' => 300],
        ['name' => 'John Smith', 'email' => 'john.smith@example.com', 'phone' => '+94 70 567 8901', 'address' => null, 'loyalty_points' => 0],
        ['name' => 'Sara Jayasinghe', 'email' => null, 'phone' => '+94 75 678 9012', 'address' => '21 Temple Road, Kandy', 'loyalty_points' => 80],
    ];

    public function run(): void
    {
        $cashier = User::where('role', Role::Cashier)->value('id');

        foreach (self::CUSTOMERS as $data) {
            $customer = Customer::firstOrNew(['phone' => $data['phone']]);
            $customer->fill($data);
            $customer->created_by ??= $cashier;
            $customer->updated_by = $cashier;
            $customer->save();
        }

        // A few extra random customers so list/pagination endpoints have data.
        if (Customer::count() < 20) {
            Customer::factory()
                ->count(20 - Customer::count())
                ->create(['created_by' => $cashier, 'updated_by' => $cashier]);
        }
    }
}
