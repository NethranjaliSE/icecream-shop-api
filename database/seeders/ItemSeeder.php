<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\Category;
use App\Models\Item;
use App\Models\User;
use Illuminate\Database\Seeder;

class ItemSeeder extends Seeder
{
    /**
     * [flavour, name, sku, price, quantity, reorder level]
     */
    private const ITEMS = [
        ['Chocolate', 'Chocolate Scoop', 'CHO-SCP', 2.50, 120, 20],
        ['Chocolate', 'Chocolate Waffle Cone', 'CHO-CON', 3.75, 60, 15],
        ['Chocolate', 'Chocolate Fudge Tub (1L)', 'CHO-TUB-1L', 9.99, 18, 10],
        ['Vanilla', 'Vanilla Scoop', 'VAN-SCP', 2.25, 150, 20],
        ['Vanilla', 'Vanilla Bean Tub (500ml)', 'VAN-TUB-500', 5.49, 35, 10],
        ['Vanilla', 'Vanilla Sundae Cup', 'VAN-SUN', 4.25, 6, 10],
        ['Strawberry', 'Strawberry Scoop', 'STR-SCP', 2.50, 90, 20],
        ['Strawberry', 'Strawberry Swirl Tub (1L)', 'STR-TUB-1L', 9.49, 12, 10],
        ['Mint Chocolate Chip', 'Mint Chocolate Chip Scoop', 'MCC-SCP', 2.75, 70, 20],
        ['Mint Chocolate Chip', 'Mint Chocolate Chip Tub (500ml)', 'MCC-TUB-500', 5.99, 4, 8],
        ['Cookies & Cream', 'Cookies & Cream Scoop', 'CNC-SCP', 2.75, 80, 20],
        ['Cookies & Cream', 'Cookies & Cream Milkshake', 'CNC-SHK', 4.95, 40, 10],
        ['Mango', 'Mango Scoop', 'MAN-SCP', 2.50, 65, 20],
        ['Mango', 'Mango Popsicle', 'MAN-POP', 1.95, 9, 15],
    ];

    public function run(): void
    {
        $categories = Category::pluck('id', 'name');
        $owner = User::where('role', Role::Owner)->value('id');

        foreach (self::ITEMS as [$flavour, $name, $sku, $price, $quantity, $reorderLevel]) {
            $item = Item::firstOrNew(['sku' => $sku]);

            $item->fill([
                'category_id' => $categories[$flavour],
                'name' => $name,
                'description' => "{$name} made in-house.",
                'price' => $price,
                'quantity' => $quantity,
                'reorder_level' => $reorderLevel,
            ]);

            $item->created_by ??= $owner;
            $item->updated_by = $owner;
            $item->save();
        }
    }
}
