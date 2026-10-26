<?php

namespace Tests\Feature\Api;

use App\Enums\Role;
use App\Models\Category;
use App\Models\Item;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ItemApiTest extends TestCase
{
    use RefreshDatabase;

    private Category $chocolate;

    private Category $vanilla;

    protected function setUp(): void
    {
        parent::setUp();

        $this->chocolate = Category::factory()->create(['name' => 'Chocolate']);
        $this->vanilla = Category::factory()->create(['name' => 'Vanilla']);
    }

    // ---------------------------------------------------------------------
    // Role-based access control (requirements section 4)
    // ---------------------------------------------------------------------

    /**
     * @return iterable<string, array{Role, string, int}>
     */
    public static function accessMatrix(): iterable
    {
        $expected = [
            'owner' => ['list' => 200, 'show' => 200, 'create' => 201, 'put' => 200, 'patch' => 200, 'delete' => 204],
            'manager' => ['list' => 200, 'show' => 200, 'create' => 201, 'put' => 200, 'patch' => 200, 'delete' => 403],
            'cashier' => ['list' => 200, 'show' => 200, 'create' => 403, 'put' => 200, 'patch' => 200, 'delete' => 403],
        ];

        foreach ($expected as $role => $actions) {
            foreach ($actions as $action => $status) {
                yield "{$role} {$action} → {$status}" => [Role::from($role), $action, $status];
            }
        }
    }

    #[Test]
    #[DataProvider('accessMatrix')]
    public function roles_get_exactly_the_access_the_requirements_describe(Role $role, string $action, int $status): void
    {
        $item = Item::factory()->for($this->chocolate)->create();
        $this->actingAsRole($role);

        $response = match ($action) {
            'list' => $this->getJson('/api/items'),
            'show' => $this->getJson("/api/items/{$item->id}"),
            'create' => $this->postJson('/api/items', $this->validPayload()),
            'put' => $this->putJson("/api/items/{$item->id}", $this->validPayload(['sku' => 'CHO-NEW-1'])),
            'patch' => $this->patchJson("/api/items/{$item->id}", ['quantity' => 3]),
            'delete' => $this->deleteJson("/api/items/{$item->id}"),
        };

        $response->assertStatus($status);

        if ($status === 403) {
            $response->assertJsonPath('message', fn (string $m) => str_contains($m, "Your role ({$role->value})"));
        }
    }

    #[Test]
    public function forbidden_writes_change_nothing(): void
    {
        $item = Item::factory()->for($this->chocolate)->create();

        $this->actingAsRole(Role::Cashier);
        $this->postJson('/api/items', $this->validPayload())->assertForbidden();

        $this->actingAsRole(Role::Manager);
        $this->deleteJson("/api/items/{$item->id}")->assertForbidden();

        $this->assertDatabaseCount('items', 1);
        $this->assertModelExists($item);
    }

    #[Test]
    public function authorization_runs_before_validation(): void
    {
        $this->actingAsRole(Role::Cashier);

        // An invalid body still gets 403, so a cashier learns nothing about the rules.
        $this->postJson('/api/items', [])->assertForbidden();
    }

    #[Test]
    public function every_endpoint_requires_authentication(): void
    {
        $item = Item::factory()->create();

        $this->getJson('/api/items')->assertUnauthorized();
        $this->getJson("/api/items/{$item->id}")->assertUnauthorized();
        $this->postJson('/api/items', $this->validPayload())->assertUnauthorized();
        $this->putJson("/api/items/{$item->id}", $this->validPayload())->assertUnauthorized();
        $this->patchJson("/api/items/{$item->id}", ['quantity' => 1])->assertUnauthorized();
        $this->deleteJson("/api/items/{$item->id}")->assertUnauthorized();
    }

    // ---------------------------------------------------------------------
    // CRUD behaviour
    // ---------------------------------------------------------------------

    #[Test]
    public function list_returns_paginated_items_with_their_flavour(): void
    {
        Item::factory()->count(3)->for($this->chocolate)->create();
        $this->actingAsRole(Role::Cashier);

        $this->getJson('/api/items')
            ->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJsonStructure([
                'data' => [['id', 'name', 'sku', 'description', 'price', 'quantity', 'reorder_level', 'low_stock', 'category' => ['id', 'name'], 'created_at', 'updated_at']],
                'links' => ['first', 'last', 'prev', 'next'],
                'meta' => ['current_page', 'per_page', 'total', 'last_page'],
            ])
            ->assertJsonPath('data.0.category.name', 'Chocolate')
            ->assertJsonPath('meta.total', 3);
    }

    #[Test]
    public function show_returns_one_item(): void
    {
        $item = Item::factory()->for($this->vanilla)->create(['name' => 'Vanilla Cone', 'price' => 3.5]);
        $this->actingAsRole(Role::Manager);

        $this->getJson("/api/items/{$item->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $item->id)
            ->assertJsonPath('data.name', 'Vanilla Cone')
            ->assertJsonPath('data.price', 3.5)
            ->assertJsonPath('data.category.name', 'Vanilla');
    }

    #[Test]
    public function missing_items_return_a_clean_404(): void
    {
        $this->actingAsRole(Role::Owner);

        $this->getJson('/api/items/999')->assertNotFound()->assertExactJson(['message' => 'Item not found.']);
        $this->patchJson('/api/items/999', ['quantity' => 1])->assertNotFound();
        $this->deleteJson('/api/items/999')->assertNotFound();
    }

    #[Test]
    public function create_stores_the_item_and_records_who_added_it(): void
    {
        $manager = $this->actingAsRole(Role::Manager);

        $response = $this->postJson('/api/items', $this->validPayload([
            'sku' => 'cho-tub-1l', // normalised to upper case
        ]));

        $response->assertCreated()
            ->assertJsonPath('data.name', 'Chocolate Fudge Tub (1L)')
            ->assertJsonPath('data.sku', 'CHO-TUB-1L')
            ->assertJsonPath('data.price', 9.99)
            ->assertJsonPath('data.quantity', 25)
            ->assertJsonPath('data.reorder_level', 10)
            ->assertJsonPath('data.low_stock', false)
            ->assertJsonPath('data.created_by.id', $manager->id)
            ->assertJsonPath('data.updated_by.id', $manager->id);

        $this->assertDatabaseHas('items', [
            'sku' => 'CHO-TUB-1L',
            'category_id' => $this->chocolate->id,
            'price' => 9.99,
            'created_by' => $manager->id,
        ]);
    }

    #[Test]
    public function quantity_and_reorder_level_are_optional_on_create(): void
    {
        $this->actingAsRole(Role::Owner);

        $payload = $this->validPayload();
        unset($payload['quantity'], $payload['reorder_level']);

        $this->postJson('/api/items', $payload)
            ->assertCreated()
            ->assertJsonPath('data.quantity', 0)
            ->assertJsonPath('data.reorder_level', 10)
            ->assertJsonPath('data.low_stock', true);
    }

    #[Test]
    public function patch_updates_only_the_fields_sent(): void
    {
        $item = Item::factory()->for($this->chocolate)->create(['name' => 'Chocolate Scoop', 'quantity' => 50, 'price' => 2.5]);
        $cashier = $this->actingAsRole(Role::Cashier);

        $this->patchJson("/api/items/{$item->id}", ['quantity' => 42])
            ->assertOk()
            ->assertJsonPath('data.quantity', 42)
            ->assertJsonPath('data.name', 'Chocolate Scoop')
            ->assertJsonPath('data.price', 2.5)
            ->assertJsonPath('data.updated_by.id', $cashier->id);

        $this->assertDatabaseHas('items', ['id' => $item->id, 'quantity' => 42, 'updated_by' => $cashier->id]);
    }

    #[Test]
    public function put_replaces_the_item_and_requires_every_field(): void
    {
        $item = Item::factory()->for($this->chocolate)->create();
        $this->actingAsRole(Role::Owner);

        $this->putJson("/api/items/{$item->id}", ['quantity' => 5])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['category_id', 'name', 'sku', 'price']);

        $this->putJson("/api/items/{$item->id}", $this->validPayload([
            'category_id' => $this->vanilla->id,
            'name' => 'Vanilla Tub',
            'sku' => 'VAN-TUB',
        ]))
            ->assertOk()
            ->assertJsonPath('data.name', 'Vanilla Tub')
            ->assertJsonPath('data.category.name', 'Vanilla');
    }

    #[Test]
    public function an_item_can_keep_its_own_sku_when_updated(): void
    {
        $item = Item::factory()->for($this->chocolate)->create(['sku' => 'CHO-SCP']);
        $this->actingAsRole(Role::Owner);

        $this->putJson("/api/items/{$item->id}", $this->validPayload(['sku' => 'CHO-SCP']))->assertOk();
    }

    #[Test]
    public function delete_removes_the_item(): void
    {
        $item = Item::factory()->create();
        $this->actingAsRole(Role::Owner);

        $this->deleteJson("/api/items/{$item->id}")->assertNoContent();

        $this->assertModelMissing($item);
    }

    // ---------------------------------------------------------------------
    // Validation
    // ---------------------------------------------------------------------

    /**
     * @return array<string, array{array<string, mixed>, string}>
     */
    public static function invalidPayloads(): array
    {
        return [
            'missing name' => [['name' => ''], 'name'],
            'name too long' => [['name' => str_repeat('a', 101)], 'name'],
            'unknown category' => [['category_id' => 999], 'category_id'],
            'missing price' => [['price' => null], 'price'],
            'negative price' => [['price' => -1], 'price'],
            'price with 3 decimals' => [['price' => 1.999], 'price'],
            'price not numeric' => [['price' => 'cheap'], 'price'],
            'negative quantity' => [['quantity' => -5], 'quantity'],
            'fractional quantity' => [['quantity' => 1.5], 'quantity'],
            'negative reorder level' => [['reorder_level' => -1], 'reorder_level'],
            'sku with spaces' => [['sku' => 'CHO TUB'], 'sku'],
            'missing sku' => [['sku' => ''], 'sku'],
        ];
    }

    #[Test]
    #[DataProvider('invalidPayloads')]
    public function create_rejects_invalid_input(array $override, string $field): void
    {
        $this->actingAsRole(Role::Owner);

        $this->postJson('/api/items', $this->validPayload($override))
            ->assertUnprocessable()
            ->assertJsonValidationErrors([$field]);

        $this->assertDatabaseCount('items', 0);
    }

    #[Test]
    public function sku_must_be_unique_ignoring_case(): void
    {
        Item::factory()->create(['sku' => 'CHO-SCP']);
        $this->actingAsRole(Role::Owner);

        $this->postJson('/api/items', $this->validPayload(['sku' => 'cho-scp']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['sku']);
    }

    // ---------------------------------------------------------------------
    // Search, filters, sorting, pagination
    // ---------------------------------------------------------------------

    #[Test]
    public function items_can_be_searched_by_name(): void
    {
        Item::factory()->for($this->chocolate)->create(['name' => 'Chocolate Scoop']);
        Item::factory()->for($this->chocolate)->create(['name' => 'Double Chocolate Tub']);
        Item::factory()->for($this->vanilla)->create(['name' => 'Vanilla Cone']);
        $this->actingAsRole(Role::Cashier);

        $names = $this->getJson('/api/items?search=CHOC')->assertOk()->json('data.*.name');

        $this->assertEqualsCanonicalizing(['Chocolate Scoop', 'Double Chocolate Tub'], $names);
    }

    #[Test]
    public function search_treats_wildcards_literally(): void
    {
        Item::factory()->create(['name' => 'Vanilla Cone']);
        Item::factory()->create(['name' => 'Sundae 50% Off']);
        Item::factory()->create(['name' => 'Mint_Chip Scoop']);
        Item::factory()->create(['name' => 'Choc!Chip Scoop']);
        $this->actingAsRole(Role::Cashier);

        $this->assertSame(['Sundae 50% Off'], $this->getJson('/api/items?search=%25')->json('data.*.name'));
        $this->assertSame(['Mint_Chip Scoop'], $this->getJson('/api/items?search=_')->json('data.*.name'));
        $this->assertSame(['Choc!Chip Scoop'], $this->getJson('/api/items?search=!')->json('data.*.name'));
    }

    #[Test]
    public function search_with_no_match_returns_an_empty_list(): void
    {
        Item::factory()->create(['name' => 'Vanilla Cone']);
        $this->actingAsRole(Role::Cashier);

        $this->getJson('/api/items?search=pistachio')
            ->assertOk()
            ->assertJsonCount(0, 'data')
            ->assertJsonPath('meta.total', 0);
    }

    #[Test]
    public function items_can_be_filtered_by_flavour_and_low_stock(): void
    {
        Item::factory()->for($this->chocolate)->create(['name' => 'Choc OK', 'quantity' => 50]);
        Item::factory()->for($this->chocolate)->lowStock()->create(['name' => 'Choc Low']);
        Item::factory()->for($this->vanilla)->lowStock()->create(['name' => 'Van Low']);
        $this->actingAsRole(Role::Manager);

        $this->assertEqualsCanonicalizing(
            ['Choc OK', 'Choc Low'],
            $this->getJson("/api/items?category_id={$this->chocolate->id}")->json('data.*.name'),
        );

        $this->assertEqualsCanonicalizing(
            ['Choc Low', 'Van Low'],
            $this->getJson('/api/items?low_stock=1')->json('data.*.name'),
        );

        $this->assertSame(
            ['Choc Low'],
            $this->getJson("/api/items?low_stock=1&category_id={$this->chocolate->id}")->json('data.*.name'),
        );
    }

    #[Test]
    public function items_can_be_sorted(): void
    {
        Item::factory()->create(['name' => 'B', 'price' => 5]);
        Item::factory()->create(['name' => 'A', 'price' => 9]);
        Item::factory()->create(['name' => 'C', 'price' => 1]);
        $this->actingAsRole(Role::Cashier);

        $this->assertSame(['A', 'B', 'C'], $this->getJson('/api/items')->json('data.*.name'));
        $this->assertSame(['A', 'B', 'C'], $this->getJson('/api/items?sort=-price')->json('data.*.name'));
        $this->assertSame(['C', 'B', 'A'], $this->getJson('/api/items?sort=price')->json('data.*.name'));
    }

    #[Test]
    public function list_is_paginated(): void
    {
        Item::factory()->count(7)->create();
        $this->actingAsRole(Role::Cashier);

        $this->getJson('/api/items?per_page=5&page=2')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('meta.current_page', 2)
            ->assertJsonPath('meta.last_page', 2)
            ->assertJsonPath('meta.total', 7);
    }

    #[Test]
    public function list_parameters_are_validated(): void
    {
        $this->actingAsRole(Role::Cashier);

        $this->getJson('/api/items?sort=password&per_page=500&category_id=999')
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['sort', 'per_page', 'category_id']);
    }

    /**
     * @param  array<string, mixed>  $override
     * @return array<string, mixed>
     */
    private function validPayload(array $override = []): array
    {
        return array_merge([
            'category_id' => $this->chocolate->id,
            'name' => 'Chocolate Fudge Tub (1L)',
            'sku' => 'CHO-TUB-1L',
            'description' => 'Rich chocolate fudge ice cream.',
            'price' => 9.99,
            'quantity' => 25,
            'reorder_level' => 10,
        ], $override);
    }
}
