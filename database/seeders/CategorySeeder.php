<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public const FLAVOURS = [
        'Chocolate' => 'Rich Belgian cocoa ice cream.',
        'Vanilla' => 'Classic Madagascar vanilla bean.',
        'Strawberry' => 'Made with real strawberry pieces.',
        'Mint Chocolate Chip' => 'Cool peppermint with dark chocolate chips.',
        'Cookies & Cream' => 'Vanilla base loaded with chocolate cookie crumbs.',
        'Mango' => 'Tropical Alphonso mango sorbet-style ice cream.',
    ];

    public function run(): void
    {
        foreach (self::FLAVOURS as $name => $description) {
            Category::updateOrCreate(['name' => $name], ['description' => $description]);
        }
    }
}
