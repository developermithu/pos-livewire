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
}; ?>

<div class="flex flex-col gap-6">
    <x-ui.page-header
        :title="__('Dashboard')"
        :subtitle="__('Trading activity and stock health across the store.')"
        :breadcrumbs="[
            ['label' => __('Overview')],
            ['label' => __('Dashboard')],
        ]"
    >
        <x-slot:actions>
            <flux:badge size="sm" color="zinc">{{ __('Sample data') }}</flux:badge>

            <flux:button size="sm" icon="plus" variant="primary">
                {{ __('New sale') }}
            </flux:button>
        </x-slot:actions>
    </x-ui.page-header>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @foreach ($this->stats as $stat)
            <x-ui.stat-card
                :label="$stat['label']"
                :value="$stat['value']"
                :delta="$stat['delta']"
                :trend="$stat['trend']"
                :intent="$stat['intent']"
                :icon="$stat['icon']"
                :hint="$stat['hint']"
            />
        @endforeach
    </div>

    <div class="grid gap-4 lg:grid-cols-3">
        <x-ui.panel
            class="lg:col-span-2"
            :heading="__('Needs reorder')"
            :subheading="__('At or below reorder point')"
            :padded="false"
        >
            <x-slot:actions>
                <flux:button size="sm" variant="ghost" icon-trailing="arrow-right">
                    {{ __('All stock') }}
                </flux:button>
            </x-slot:actions>

            <ul class="divide-y divide-line">
                @foreach ($this->lowStock as $item)
                    <li class="flex items-center gap-4 px-5 py-3">
                        <span
                            @class([
                                'size-2 shrink-0 rounded-full',
                                'bg-stock-out' => $item['level'] === 'out',
                                'bg-stock-critical' => $item['level'] === 'critical',
                                'bg-stock-low' => $item['level'] === 'low',
                                'bg-stock-healthy' => $item['level'] === 'healthy',
                            ])
                            aria-hidden="true"
                        ></span>

                        <div class="min-w-0 flex-1">
                            <p class="truncate font-medium text-ink">{{ $item['name'] }}</p>
                            <p class="truncate text-xs tabular-nums text-ink-faint">{{ $item['sku'] }}</p>
                        </div>

                        <div class="shrink-0 text-right">
                            <p
                                @class([
                                    'font-medium tabular-nums',
                                    'text-stock-out' => $item['level'] === 'out',
                                    'text-stock-critical' => $item['level'] === 'critical',
                                    'text-stock-low' => $item['level'] === 'low',
                                    'text-ink' => $item['level'] === 'healthy',
                                ])
                            >
                                {{ $item['on_hand'] }}
                            </p>
                            <p class="text-xs tabular-nums text-ink-faint">
                                {{ __('of :count', ['count' => $item['reorder_point']]) }}
                            </p>
                        </div>
                    </li>
                @endforeach
            </ul>
        </x-ui.panel>

        <x-ui.panel :heading="__('Recent activity')" :padded="false">
            <ul class="divide-y divide-line">
                @foreach ($this->activity as $event)
                    <li class="flex items-start gap-3 px-5 py-3">
                        <span class="mt-0.5 flex size-7 shrink-0 items-center justify-center rounded-control bg-surface-sunken text-ink-muted">
                            <flux:icon :icon="$event['icon']" variant="micro" />
                        </span>

                        <div class="min-w-0 flex-1">
                            <p class="text-ink">{{ $event['description'] }}</p>
                            <p class="truncate text-xs text-ink-faint">{{ $event['meta'] }}</p>
                        </div>

                        <span class="shrink-0 text-xs whitespace-nowrap text-ink-faint">{{ $event['at'] }}</span>
                    </li>
                @endforeach
            </ul>
        </x-ui.panel>
    </div>
</div>
