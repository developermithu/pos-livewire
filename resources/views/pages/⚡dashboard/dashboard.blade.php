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