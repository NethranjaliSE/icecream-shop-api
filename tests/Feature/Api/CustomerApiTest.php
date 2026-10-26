<?php

namespace Tests\Feature\Api;

use App\Enums\Role;
use App\Models\Customer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CustomerApiTest extends TestCase
{
    use RefreshDatabase;

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
            'cashier' => ['list' => 200, 'show' => 200, 'create' => 201, 'put' => 200, 'patch' => 200, 'delete' => 403],
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
        $customer = Customer::factory()->create();
        $this->actingAsRole($role);

        $response = match ($action) {
            'list' => $this->getJson('/api/customers'),
            'show' => $this->getJson("/api/customers/{$customer->id}"),
            'create' => $this->postJson('/api/customers', $this->validPayload()),
            'put' => $this->putJson("/api/customers/{$customer->id}", $this->validPayload()),
            'patch' => $this->patchJson("/api/customers/{$customer->id}", ['loyalty_points' => 10]),
            'delete' => $this->deleteJson("/api/customers/{$customer->id}"),
        };

        $response->assertStatus($status);

        if ($status === 403) {
            $response->assertJsonPath('message', fn (string $m) => str_contains($m, 'customers.delete'));
        }
    }

    #[Test]
    public function only_the_owner_can_delete_and_forbidden_deletes_change_nothing(): void
    {
        $customer = Customer::factory()->create();

        foreach ([Role::Manager, Role::Cashier] as $role) {
            $this->actingAsRole($role);
            $this->deleteJson("/api/customers/{$customer->id}")->assertForbidden();
        }

        $this->assertModelExists($customer);

        $this->actingAsRole(Role::Owner);
        $this->deleteJson("/api/customers/{$customer->id}")->assertNoContent();

        $this->assertModelMissing($customer);
    }

    #[Test]
    public function every_endpoint_requires_authentication(): void
    {
        $customer = Customer::factory()->create();

        $this->getJson('/api/customers')->assertUnauthorized();
        $this->getJson("/api/customers/{$customer->id}")->assertUnauthorized();
        $this->postJson('/api/customers', $this->validPayload())->assertUnauthorized();
        $this->putJson("/api/customers/{$customer->id}", $this->validPayload())->assertUnauthorized();
        $this->patchJson("/api/customers/{$customer->id}", ['name' => 'X'])->assertUnauthorized();
        $this->deleteJson("/api/customers/{$customer->id}")->assertUnauthorized();
    }

    // ---------------------------------------------------------------------
    // CRUD behaviour
    // ---------------------------------------------------------------------

    #[Test]
    public function list_returns_paginated_customers(): void
    {
        Customer::factory()->count(3)->create();
        $this->actingAsRole(Role::Cashier);

        $this->getJson('/api/customers')
            ->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJsonStructure([
                'data' => [['id', 'name', 'email', 'phone', 'address', 'loyalty_points', 'created_at', 'updated_at']],
                'links',
                'meta' => ['current_page', 'per_page', 'total'],
            ]);
    }

    #[Test]
    public function show_returns_one_customer(): void
    {
        $customer = Customer::factory()->create(['name' => 'Amara Silva']);
        $this->actingAsRole(Role::Cashier);

        $this->getJson("/api/customers/{$customer->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $customer->id)
            ->assertJsonPath('data.name', 'Amara Silva');
    }

    #[Test]
    public function missing_customers_return_a_clean_404(): void
    {
        $this->actingAsRole(Role::Owner);

        $this->getJson('/api/customers/999')->assertNotFound()->assertExactJson(['message' => 'Customer not found.']);
    }

    #[Test]
    public function a_cashier_can_register_a_customer_and_is_recorded_as_the_creator(): void
    {
        $cashier = $this->actingAsRole(Role::Cashier);

        $this->postJson('/api/customers', $this->validPayload(['email' => 'Amara.Silva@Example.COM']))
            ->assertCreated()
            ->assertJsonPath('data.name', 'Amara Silva')
            ->assertJsonPath('data.email', 'amara.silva@example.com')
            ->assertJsonPath('data.loyalty_points', 0)
            ->assertJsonPath('data.created_by.id', $cashier->id)
            ->assertJsonPath('data.created_by.role', 'cashier');

        $this->assertDatabaseHas('customers', ['email' => 'amara.silva@example.com', 'created_by' => $cashier->id]);
    }

    #[Test]
    public function only_a_name_is_required(): void
    {
        $this->actingAsRole(Role::Cashier);

        $this->postJson('/api/customers', ['name' => 'Walk-in Customer'])
            ->assertCreated()
            ->assertJsonPath('data.email', null)
            ->assertJsonPath('data.phone', null);
    }

    #[Test]
    public function patch_updates_only_the_fields_sent(): void
    {
        $customer = Customer::factory()->create(['name' => 'Kevin Fernando', 'loyalty_points' => 10]);
        $manager = $this->actingAsRole(Role::Manager);

        $this->patchJson("/api/customers/{$customer->id}", ['loyalty_points' => 60])
            ->assertOk()
            ->assertJsonPath('data.loyalty_points', 60)
            ->assertJsonPath('data.name', 'Kevin Fernando')
            ->assertJsonPath('data.updated_by.id', $manager->id);
    }

    #[Test]
    public function put_requires_a_name(): void
    {
        $customer = Customer::factory()->create();
        $this->actingAsRole(Role::Manager);

        $this->putJson("/api/customers/{$customer->id}", ['email' => 'x@example.com'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name']);
    }

    #[Test]
    public function contact_details_can_be_cleared(): void
    {
        $customer = Customer::factory()->create();
        $this->actingAsRole(Role::Cashier);

        $this->patchJson("/api/customers/{$customer->id}", ['email' => null, 'address' => null])
            ->assertOk()
            ->assertJsonPath('data.email', null)
            ->assertJsonPath('data.address', null);
    }

    #[Test]
    public function a_customer_can_keep_their_own_email_and_phone(): void
    {
        $customer = Customer::factory()->create(['email' => 'nimali@example.com', 'phone' => '+94 76 456 7890']);
        $this->actingAsRole(Role::Cashier);

        $this->putJson("/api/customers/{$customer->id}", [
            'name' => 'Nimali P.',
            'email' => 'nimali@example.com',
            'phone' => '+94 76 456 7890',
        ])->assertOk()->assertJsonPath('data.name', 'Nimali P.');
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
            'bad email' => [['email' => 'not-an-email'], 'email'],
            'phone with letters' => [['phone' => '+94 7X 123 4567'], 'phone'],
            'phone too short' => [['phone' => '123'], 'phone'],
            'negative loyalty points' => [['loyalty_points' => -1], 'loyalty_points'],
            'address too long' => [['address' => str_repeat('a', 256)], 'address'],
        ];
    }

    #[Test]
    #[DataProvider('invalidPayloads')]
    public function create_rejects_invalid_input(array $override, string $field): void
    {
        $this->actingAsRole(Role::Owner);

        $this->postJson('/api/customers', $this->validPayload($override))
            ->assertUnprocessable()
            ->assertJsonValidationErrors([$field]);

        $this->assertDatabaseCount('customers', 0);
    }

    #[Test]
    public function email_and_phone_must_be_unique(): void
    {
        Customer::factory()->create(['email' => 'amara.silva@example.com', 'phone' => '+94 71 234 5678']);
        $this->actingAsRole(Role::Owner);

        $this->postJson('/api/customers', $this->validPayload(['email' => 'AMARA.SILVA@example.com']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['email', 'phone']);
    }

    // ---------------------------------------------------------------------
    // Search, sorting, pagination
    // ---------------------------------------------------------------------

    /**
     * @return array<string, array{string}>
     */
    public static function searchTerms(): array
    {
        return [
            'by part of the name' => ['silv'],
            'by email' => ['amara.silva@'],
            'by phone' => ['234 5678'],
        ];
    }

    #[Test]
    #[DataProvider('searchTerms')]
    public function customers_can_be_searched_by_name_email_or_phone(string $term): void
    {
        Customer::factory()->create(['name' => 'Amara Silva', 'email' => 'amara.silva@example.com', 'phone' => '+94 71 234 5678']);
        Customer::factory()->create(['name' => 'Kevin Fernando', 'email' => 'kevin@example.com', 'phone' => '+94 77 345 6789']);
        $this->actingAsRole(Role::Cashier);

        $this->assertSame(['Amara Silva'], $this->getJson('/api/customers?search='.urlencode($term))->json('data.*.name'));
    }

    #[Test]
    public function customers_can_be_sorted_by_loyalty_points(): void
    {
        Customer::factory()->create(['name' => 'Low', 'loyalty_points' => 5]);
        Customer::factory()->create(['name' => 'High', 'loyalty_points' => 500]);
        Customer::factory()->create(['name' => 'Mid', 'loyalty_points' => 50]);
        $this->actingAsRole(Role::Manager);

        $this->assertSame(['High', 'Mid', 'Low'], $this->getJson('/api/customers?sort=-loyalty_points')->json('data.*.name'));
    }

    #[Test]
    public function list_is_paginated(): void
    {
        Customer::factory()->count(4)->create();
        $this->actingAsRole(Role::Cashier);

        $this->getJson('/api/customers?per_page=3')
            ->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJsonPath('meta.total', 4)
            ->assertJsonPath('meta.last_page', 2);
    }

    /**
     * @param  array<string, mixed>  $override
     * @return array<string, mixed>
     */
    private function validPayload(array $override = []): array
    {
        return array_merge([
            'name' => 'Amara Silva',
            'email' => 'amara.silva@example.com',
            'phone' => '+94 71 234 5678',
            'address' => '12 Beach Road, Negombo',
        ], $override);
    }
}
