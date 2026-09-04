@props([
    'item',
])

{{--
    One sidebar destination. An item whose route does not exist yet renders
    dimmed and inert rather than being hidden, so the full information
    architecture is visible while later phases fill it in.
--}}

@if ($item->isAvailable())
    <flux:sidebar.item
        :icon="$item->icon"
        :href="$item->url()"
        :current="$item->isCurrent()"
        wire:navigate
    >
        {{ $item->label }}
    </flux:sidebar.item>
@else
    <flux:sidebar.item
        :icon="$item->icon"
        class="cursor-default opacity-45"
        aria-disabled="true"
        :title="__('Coming soon')"
    >
        {{ $item->label }}
    </flux:sidebar.item>
@endif
