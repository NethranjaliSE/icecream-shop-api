<?php

namespace App\Models;

use App\Models\Concerns\RecordsAuthors;
use App\Models\Concerns\SearchesText;
use Database\Factories\CustomerFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['name', 'email', 'phone', 'address', 'loyalty_points'])]
class Customer extends Model
{
    /** @use HasFactory<CustomerFactory> */
    use HasFactory, RecordsAuthors, SearchesText;

    /**
     * Mirrors the column default so new models are complete before a refresh.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'loyalty_points' => 0,
    ];

    protected function casts(): array
    {
        return [
            'loyalty_points' => 'integer',
        ];
    }

    /**
     * Matches the term against name, email or phone.
     */
    #[Scope]
    protected function search(Builder $query, ?string $term): void
    {
        $this->whereAnyContains($query, ['name', 'email', 'phone'], $term);
    }
}
