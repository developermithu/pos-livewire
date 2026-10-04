<?php

use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Dashboard')] class extends Component
{
    /**
     * Headline figures for the current trading day.
     *
     * Sample data until the sales schema lands in phase 8. Swapping these for
     * real aggregates is a change to this method and nothing else.
     *
     * @return array<int, array{label: string, value: string, delta: string|null, trend: string|null, intent: string|null, icon: string, hint: string}>
     */
    #[Computed]
    public function stats(): array
    {
        return [
            [
                'label' => __('Sales today'),
                'value' => '$4,182.60',
                'delta' => '12.4%',
                'trend' => 'up',
                'intent' => null,
                'icon' => 'banknotes',
                'hint' => __('vs. $3,721.40 last Tuesday'),
            ],
            [
                'label' => __('Transactions'),
                'value' => '138',
                'delta' => '6.2%',
                'trend' => 'up',
                'intent' => null,
                'icon' => 'receipt-percent',
                'hint' => __('Across 2 registers'),
            ],
            [
                'label' => __('Average basket'),
                'value' => '$30.31',
                'delta' => '2.1%',
                'trend' => 'down',
                'intent' => null,
                'icon' => 'shopping-cart',
                'hint' => __('Rolling 7-day average'),
            ],
            [
                'label' => __('Needs reorder'),
                'value' => '12',
                'delta' => __('4 new'),
                'trend' => 'up',
                'intent' => 'caution',
                'icon' => 'exclamation-triangle',
                'hint' => __('Below reorder point'),
            ],
        ];
    }

    /**
     * Products sitting at or below their reorder point.
     *
     * Sample data until the inventory ledger lands in phase 5.
     *
     * @return array<int, array{name: string, sku: string, on_hand: int, reorder_point: int, level: string}>
     */
    #[Computed]
    public function lowStock(): array
    {
        return [
            ['name' => 'Arabica Beans 1kg', 'sku' => 'COF-ARB-1000', 'on_hand' => 0, 'reorder_point' => 24, 'level' => 'out'],
            ['name' => 'Oat Milk 1L', 'sku' => 'DRY-OAT-1000', 'on_hand' => 3, 'reorder_point' => 48, 'level' => 'critical'],
            ['name' => 'Paper Cups 12oz', 'sku' => 'PKG-CUP-0120', 'on_hand' => 140, 'reorder_point' => 500, 'level' => 'critical'],
            ['name' => 'Vanilla Syrup 750ml', 'sku' => 'SYR-VAN-0750', 'on_hand' => 9, 'reorder_point' => 12, 'level' => 'low'],
            ['name' => 'Takeaway Lids 12oz', 'sku' => 'PKG-LID-0120', 'on_hand' => 460, 'reorder_point' => 500, 'level' => 'low'],
        ];
    }

    /**
     * Recent movements across the store.
     *
     * Sample data until the stock ledger lands in phase 5.
     *
     * @return array<int, array{icon: string, description: string, meta: string, at: string}>
     */
    #[Computed]
    public function activity(): array
    {
        return [
            ['icon' => 'shopping-bag', 'description' => __('Sale #10482 completed'), 'meta' => __('Register 1 · 4 items · $46.20'), 'at' => '2 min ago'],
            ['icon' => 'truck', 'description' => __('Purchase order PO-0231 received'), 'meta' => __('Highland Roasters · 18 lines'), 'at' => '41 min ago'],
            ['icon' => 'adjustments-horizontal', 'description' => __('Stock adjustment on Oat Milk 1L'), 'meta' => __('−6 · Damaged in transit'), 'at' => '1 hr ago'],
            ['icon' => 'arrow-uturn-left', 'description' => __('Return processed for sale #10461'), 'meta' => __('1 item · $12.00 refunded'), 'at' => '3 hr ago'],
        ];
    }
}; 