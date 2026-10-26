<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\Item
 */
class ItemResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'sku' => $this->sku,
            'description' => $this->description,
            'price' => (float) $this->price,
            'quantity' => $this->quantity,
            'reorder_level' => $this->reorder_level,
            'low_stock' => $this->isLowStock(),
            'category' => new CategoryResource($this->whenLoaded('category')),
            'created_by' => new AuthorResource($this->whenLoaded('creator')),
            'updated_by' => new AuthorResource($this->whenLoaded('updater')),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
