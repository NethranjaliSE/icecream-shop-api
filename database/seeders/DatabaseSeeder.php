<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database. Every seeder is idempotent, so this
     * can be re-run safely with `php artisan db:seed`.
     */
    public function run(): void
    {
        $this->call([
            PassportClientSeeder::class,
            UserSeeder::class,
            CategorySeeder::class,
            ItemSeeder::class,
            CustomerSeeder::class,
        ]);
    }
}
