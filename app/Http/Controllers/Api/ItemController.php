<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Item\ListItemsRequest;
use App\Http\Requests\Item\StoreItemRequest;
use App\Http\Requests\Item\UpdateItemRequest;
use App\Http\Resources\ItemResource;
use App\Models\Item;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class ItemController extends Controller implements HasMiddleware
{
    /**
     * Role checks run as route middleware, i.e. before request validation,
     * so a forbidden request gets a 403 rather than a 422.
     */
    public static function middleware(): array
    {
        return [
            new Middleware('can:viewAny,'.Item::class, only: ['index']),
            new Middleware('can:view,item', only: ['show']),
            new Middleware('can:create,'.Item::class, only: ['store']),
            new Middleware('can:update,item', only: ['update']),
            new Middleware('can:delete,item', only: ['destroy']),
        ];
    }

    /**
     * GET /api/items?search=&category_id=&low_stock=&sort=&per_page=
     */
    public function index(ListItemsRequest $request): AnonymousResourceCollection
    {
        $sort = $request->validated('sort', 'name');
        $direction = str_starts_with($sort, '-') ? 'desc' : 'asc';

        $items = Item::query()
            ->with(['category', 'creator', 'updater'])
            ->search($request->validated('search'))
            ->when($request->filled('category_id'), fn ($q) => $q->where('category_id', $request->validated('category_id')))
            ->when($request->boolean('low_stock'), fn ($q) => $q->lowStock())
            ->orderBy(ltrim($sort, '-'), $direction)
            ->orderBy('id')
            ->paginate($request->validated('per_page', 15))
            ->withQueryString();

        return ItemResource::collection($items);
    }

    /**
     * POST /api/items
     */
    public function store(StoreItemRequest $request): ItemResource
    {
        $item = Item::create($request->validated());

        return new ItemResource($item->load(['category', 'creator', 'updater']));
    }

    /**
     * GET /api/items/{item}
     */
    public function show(Item $item): ItemResource
    {
        return new ItemResource($item->load(['category', 'creator', 'updater']));
    }

    /**
     * PUT|PATCH /api/items/{item}
     */
    public function update(UpdateItemRequest $request, Item $item): ItemResource
    {
        $item->update($request->validated());

        return new ItemResource($item->load(['category', 'creator', 'updater']));
    }

    /**
     * DELETE /api/items/{item}
     */
    public function destroy(Item $item): Response
    {
        $item->delete();

        return response()->noContent();
    }
}
