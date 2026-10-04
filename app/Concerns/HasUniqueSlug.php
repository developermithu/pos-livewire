<?php

namespace App\Concerns;

use Illuminate\Support\Str;

/**
 * Generates a URL-safe slug that does not collide with an existing row.
 *
 * Soft-deleted rows still occupy their slug — the unique index does not know
 * about `deleted_at` — so the check runs against trashed rows too. A slug is
 * assigned once, at creation, and is not regenerated when the name changes:
 * a slug is an identifier, and identifiers that move break the links that
 * point at them.
 */
trait HasUniqueSlug
{
    public static function uniqueSlugFor(string $value, ?int $ignoreId = null): string
    {
        $base = Str::slug($value) ?: 'item';
        $slug = $base;
        $suffix = 2;

        while (static::slugIsTaken($slug, $ignoreId)) {
            $slug = "{$base}-{$suffix}";
            $suffix++;
        }

        return $slug;
    }

    protected static function slugIsTaken(string $slug, ?int $ignoreId): bool
    {
        return static::withTrashed()
            ->where('slug', $slug)
            ->when($ignoreId !== null, fn ($query) => $query->whereKeyNot($ignoreId))
            ->exists();
    }
}
