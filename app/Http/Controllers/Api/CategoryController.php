<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Read-only list of flavours, so clients can fill an item's category_id.
 * Available to every signed-in role.
 */
class CategoryController extends Controller
{
    /**
     * GET /api/categories
     */
    public function index(): AnonymousResourceCollection
    {
        return CategoryResource::collection(
            Category::query()->withCount('items')->orderBy('name')->get()
        );
    }
}
