<?php

namespace App\Models;

use App\Models\Concerns\RecordsAuthors;
use Database\Factories\ItemFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * An inventory item, e.g. "Belgian Chocolate Tub (1L)".
 */
#[Fillable(['category_id', 'name', 'sku', 'description', 'price', 'quantity', 'reorder_level'])]
class Item extends Model
{
    /** @use HasFactory<ItemFactory> */
    use HasFactory, RecordsAuthors;

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'quantity' => 'integer',
            'reorder_level' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Category, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function isLowStock(): bool
    {
        return $this->quantity <= $this->reorder_level;
    }

    /**
     * Case-insensitive "contains" search on the item name.
     */
    #[Scope]
    protected function search(Builder $query, ?string $term): void
    {
        $term = trim((string) $term);

        if ($term !== '') {
            $query->where('name', 'like', '%'.addcslashes($term, '%_\\').'%');
        }
    }

    #[Scope]
    protected function lowStock(Builder $query): void
    {
        $query->whereColumn('quantity', '<=', 'reorder_level');
    }
}
