<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;

trait SearchesText
{
    /**
     * Adds "any of these columns contains $term". % and _ typed by the user
     * are matched literally. Uses an explicit ESCAPE character so it behaves
     * the same on MySQL and SQLite (which has no default LIKE escape).
     *
     * @param  list<string>  $columns
     */
    protected function whereAnyContains(Builder $query, array $columns, ?string $term): void
    {
        $term = trim((string) $term);

        if ($term === '') {
            return;
        }

        $pattern = '%'.str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $term).'%';

        $query->where(function (Builder $q) use ($columns, $pattern) {
            $grammar = $q->getQuery()->getGrammar();

            foreach ($columns as $column) {
                $q->orWhereRaw($grammar->wrap($column)." LIKE ? ESCAPE '!'", [$pattern]);
            }
        });
    }
}
