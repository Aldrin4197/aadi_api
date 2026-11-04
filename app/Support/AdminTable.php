<?php

namespace App\Support;

use Closure;
use Illuminate\Contracts\Database\Eloquent\Builder as EloquentContract;
use Illuminate\Http\Request;

/** Shared helpers for the admin data tables: whitelisted sorting and literal LIKE searches. */
final class AdminTable
{
    /**
     * Applies a sort chosen from a whitelist and returns the state echoed back to the UI.
     *
     * @param array<string, string|Closure> $columns sort key => column name, or closure(query, direction)
     */
    public static function sort(Request $request, $query, array $columns, string $default, string $defaultDirection = 'desc'): array
    {
        $requested = (string) $request->query('sort', '');
        $sort = array_key_exists($requested, $columns) ? $requested : $default;
        $direction = $requested === $sort
            ? (strtolower((string) $request->query('dir', $defaultDirection)) === 'asc' ? 'asc' : 'desc')
            : $defaultDirection;

        $column = $columns[$sort];
        $column instanceof Closure ? $column($query, $direction) : $query->orderBy($column, $direction);
        $query->orderBy($query->getModel()->getQualifiedKeyName(), $direction);

        return ['sort' => $sort, 'dir' => $direction];
    }

    /** Escapes LIKE wildcards so searches match literally. */
    public static function like(string $term): string
    {
        return '%'.addcslashes($term, '\\%_').'%';
    }
}
