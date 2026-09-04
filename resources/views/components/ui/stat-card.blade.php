@props([
    'label',
    'value',
    'icon' => null,
    'delta' => null,
    'trend' => null,
    'intent' => null,
    'hint' => null,
])

{{--
    A single headline figure.

    `trend` ('up' | 'down' | 'flat') picks the direction indicator; `intent`
    picks the colour and defaults to the obvious reading of that direction.
    Pass `intent` explicitly for inverted metrics — rising returns or shrinkage
    are a 'critical' movement even though the arrow points up.
--}}

@php
    $intent ??= match ($trend) {
        'up' => 'positive',
        'down' => 'critical',
        default => 'neutral',
    };

    $deltaClasses = match ($intent) {
        'positive' => 'text-positive',
        'caution' => 'text-caution',
        'critical' => 'text-critical',
        default => 'text-ink-faint',
    };

    $trendIcon = match ($trend) {
        'up' => 'arrow-trending-up',
        'down' => 'arrow-trending-down',
        'flat' => 'arrows-right-left',
        default => null,
    };
@endphp

<div {{ $attributes->class('rounded-surface border border-line bg-surface-raised px-5 py-4') }}>
    <div class="flex items-center justify-between gap-3">
        <span class="truncate text-xs font-medium tracking-wide text-ink-faint uppercase">
            {{ $label }}
        </span>

        @if (filled($icon))
            <flux:icon :$icon variant="micro" class="shrink-0 text-ink-faint" />
        @endif
    </div>

    <div class="mt-2.5 flex flex-wrap items-baseline gap-x-2 gap-y-1">
        <span class="text-2xl leading-none font-semibold tabular-nums text-ink">
            {{ $value }}
        </span>

        @if (filled($delta))
            <span class="flex items-center gap-0.5 text-xs font-medium tabular-nums {{ $deltaClasses }}">
                @if ($trendIcon)
                    <flux:icon :icon="$trendIcon" variant="micro" />
                @endif

                {{ $delta }}
            </span>
        @endif
    </div>

    @if (filled($hint))
        <p class="mt-1.5 text-xs text-ink-faint">{{ $hint }}</p>
    @endif
</div>
