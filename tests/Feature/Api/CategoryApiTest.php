<?php

namespace Tests\Feature\Api;

use App\Enums\Role;
use App\Models\Category;
use App\Models\Item;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CategoryApiTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, array{Role}>
     */
    public static function roles(): array
    {
        return array_combine(Role::values(), array_map(fn (Role $r) => [$r], Role::cases()));
    }

    #[Test]
    #[DataProvider('roles')]
    public function every_role_can_list_flavours_with_item_counts(Role $role): void
    {
        $chocolate = Category::factory()->create(['name' => 'Chocolate']);
        Category::factory()->create(['name' => 'Vanilla']);
        Item::factory()->count(2)->for($chocolate)->create();
        $this->actingAsRole($role);

        $this->getJson('/api/categories')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.name', 'Chocolate')
            ->assertJsonPath('data.0.items_count', 2)
            ->assertJsonPath('data.1.items_count', 0);
    }

    #[Test]
    public function listing_flavours_requires_authentication(): void
    {
        $this->getJson('/api/categories')->assertUnauthorized();
    }

    #[Test]
    public function a_flavour_that_still_has_items_cannot_be_removed(): void
    {
        $chocolate = Category::factory()->create();
        Item::factory()->for($chocolate)->create();

        $this->expectException(QueryException::class);

        $chocolate->delete();
    }
}
