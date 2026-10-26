<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Customer\ListCustomersRequest;
use App\Http\Requests\Customer\StoreCustomerRequest;
use App\Http\Requests\Customer\UpdateCustomerRequest;
use App\Http\Resources\CustomerResource;
use App\Models\Customer;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class CustomerController extends Controller implements HasMiddleware
{
    /**
     * Role checks run as route middleware, i.e. before request validation,
     * so a forbidden request gets a 403 rather than a 422.
     */
    public static function middleware(): array
    {
        return [
            new Middleware('can:viewAny,'.Customer::class, only: ['index']),
            new Middleware('can:view,customer', only: ['show']),
            new Middleware('can:create,'.Customer::class, only: ['store']),
            new Middleware('can:update,customer', only: ['update']),
            new Middleware('can:delete,customer', only: ['destroy']),
        ];
    }

    /**
     * GET /api/customers?search=&sort=&per_page=
     */
    public function index(ListCustomersRequest $request): AnonymousResourceCollection
    {
        $sort = $request->validated('sort', 'name');
        $direction = str_starts_with($sort, '-') ? 'desc' : 'asc';

        $customers = Customer::query()
            ->with(['creator', 'updater'])
            ->search($request->validated('search'))
            ->orderBy(ltrim($sort, '-'), $direction)
            ->orderBy('id')
            ->paginate($request->validated('per_page', 15))
            ->withQueryString();

        return CustomerResource::collection($customers);
    }

    /**
     * POST /api/customers
     */
    public function store(StoreCustomerRequest $request): CustomerResource
    {
        $customer = Customer::create($request->validated());

        return new CustomerResource($customer->load(['creator', 'updater']));
    }

    /**
     * GET /api/customers/{customer}
     */
    public function show(Customer $customer): CustomerResource
    {
        return new CustomerResource($customer->load(['creator', 'updater']));
    }

    /**
     * PUT|PATCH /api/customers/{customer}
     */
    public function update(UpdateCustomerRequest $request, Customer $customer): CustomerResource
    {
        $customer->update($request->validated());

        return new CustomerResource($customer->load(['creator', 'updater']));
    }

    /**
     * DELETE /api/customers/{customer}
     */
    public function destroy(Customer $customer): Response
    {
        $customer->delete();

        return response()->noContent();
    }
}
