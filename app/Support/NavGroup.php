<?php

namespace App\Support;

/**
 * A labelled section of the primary navigation.
 */
final class NavGroup
{
    /**
     * @param  list<NavItem>  $items
     */
    public function __construct(
        public string $heading,
        public array $items = [],
    ) {}
}
