<?php

namespace App\Support;

/**
 * The single source of truth for the application's primary navigation.
 *
 * Both the sidebar and the command menu render from this. Nothing else may
 * hand-write a navigation tree — the starter kit shipped with the tree written
 * out in three places, which is how navigation drifts.
 *
 * Groups are named for what the user is doing, not for database tables.
 */
final class Navigation
{
    /**
     * @return list<NavGroup>
     */
    public function groups(): array
    {
        return [
            new NavGroup(__('Overview'), [
                NavItem::to(__('Dashboard'), 'home', 'dashboard'),
                NavItem::to(__('Reports'), 'chart-bar', 'reports.index', 'reports.*'),
            ]),

            new NavGroup(__('Operations'), [
                NavItem::to(__('Point of Sale'), 'computer-desktop', 'pos.register'),
                NavItem::to(__('Sales'), 'shopping-bag', 'sales.index', 'sales.*'),
                NavItem::to(__('Purchasing'), 'truck', 'purchasing.index', 'purchasing.*'),
            ]),

            new NavGroup(__('Records'), [
                NavItem::group(__('Catalog'), 'squares-2x2', [
                    NavItem::to(__('Products'), 'tag', 'products.index', 'products.*'),
                    NavItem::to(__('Categories'), 'squares-2x2', 'categories.index', 'categories.*'),
                    NavItem::to(__('Brands'), 'building-storefront', 'brands.index', 'brands.*'),
                    NavItem::to(__('Units'), 'scale', 'units.index', 'units.*'),
                ]),
                NavItem::group(__('Inventory'), 'archive-box', [
                    NavItem::to(__('Stock'), 'archive-box', 'stock.index', 'stock.*'),
                    NavItem::to(__('Adjustments'), 'clipboard-document-list', 'adjustments.index', 'adjustments.*'),
                    NavItem::to(__('Transfers'), 'arrow-path-rounded-square', 'transfers.index', 'transfers.*'),
                ]),
                NavItem::to(__('Customers'), 'users', 'customers.index', 'customers.*'),
                NavItem::to(__('Suppliers'), 'building-storefront', 'suppliers.index', 'suppliers.*'),
            ]),
        ];
    }

    /**
     * Every destination that can actually be reached, flattened for the
     * command menu.
     *
     * @return list<array{label: string, group: string, url: string, icon: string|null}>
     */
    public function destinations(): array
    {
        $destinations = [];

        foreach ($this->groups() as $group) {
            foreach ($group->items as $item) {
                foreach ($item->hasChildren() ? $item->children : [$item] as $entry) {
                    if (($url = $entry->url()) !== null) {
                        $destinations[] = [
                            'label' => $entry->label,
                            'group' => $item->hasChildren() ? $item->label : $group->heading,
                            'url' => $url,
                            'icon' => $entry->icon,
                        ];
                    }
                }
            }
        }

        return $destinations;
    }
}
