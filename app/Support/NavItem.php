<?php

namespace App\Support;

use Illuminate\Support\Facades\Route;

/**
 * A single entry in the application's primary navigation.
 *
 * Items name a route rather than a URL, and an item whose route does not exist
 * yet reports itself unavailable. The sidebar renders it disabled, so the
 * navigation fills in on its own as later phases add routes instead of needing
 * to be edited a second time.
 */
final class NavItem
{
    /**
     * @param  list<NavItem>  $children
     */
    public function __construct(
        public string $label,
        public ?string $icon = null,
        public ?string $route = null,
        public ?string $pattern = null,
        public array $children = [],
    ) {}

    /**
     * Create a leaf item pointing at a route.
     */
    public static function to(string $label, string $icon, string $route, ?string $pattern = null): self
    {
        return new self($label, $icon, $route, $pattern);
    }

    /**
     * Create a parent item that expands to reveal its children.
     *
     * @param  list<NavItem>  $children
     */
    public static function group(string $label, string $icon, array $children): self
    {
        return new self($label, $icon, children: $children);
    }

    /**
     * Determine whether this destination can be reached yet.
     */
    public function isAvailable(): bool
    {
        if ($this->hasChildren()) {
            return $this->availableChildren() !== [];
        }

        return $this->route !== null && Route::has($this->route);
    }

    public function hasChildren(): bool
    {
        return $this->children !== [];
    }

    /**
     * @return list<NavItem>
     */
    public function availableChildren(): array
    {
        return array_values(array_filter(
            $this->children,
            fn (NavItem $child): bool => $child->isAvailable(),
        ));
    }

    public function url(): ?string
    {
        if ($this->hasChildren() || $this->route === null || ! Route::has($this->route)) {
            return null;
        }

        return route($this->route);
    }

    /**
     * Determine whether the current request is at, or below, this item.
     */
    public function isCurrent(): bool
    {
        if ($this->hasChildren()) {
            foreach ($this->children as $child) {
                if ($child->isCurrent()) {
                    return true;
                }
            }

            return false;
        }

        $pattern = $this->pattern ?? $this->route;

        return $pattern !== null && request()->routeIs($pattern);
    }
}
