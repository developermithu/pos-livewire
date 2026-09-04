@props([
    'title',
    'subtitle' => null,
    'breadcrumbs' => [],
])

{{--
    The opening block of every page. Breadcrumbs are declared as data
    (`['label' => 'Catalog', 'href' => route('products.index')]`) so the trail
    cannot drift from the route, and page-level actions live here — beside the
    title they act on — rather than in the application header.
--}}

<div {{ $attributes->class('flex flex-col gap-4 border-b border-line pb-5') }}>
    @if (filled($breadcrumbs))
        <flux:breadcrumbs>
            @foreach ($breadcrumbs as $crumb)
                @if (filled($crumb['href'] ?? null))
                    <flux:breadcrumbs.item :href="$crumb['href']" wire:navigate>
                        {{ $crumb['label'] }}
                    </flux:breadcrumbs.item>
                @else
                    <flux:breadcrumbs.item>{{ $crumb['label'] }}</flux:breadcrumbs.item>
                @endif
            @endforeach
        </flux:breadcrumbs>
    @endif

    <div class="flex flex-wrap items-start justify-between gap-4">
        <div class="min-w-0 space-y-1">
            <flux:heading size="xl" level="1">{{ $title }}</flux:heading>

            @if (filled($subtitle))
                <flux:subheading>{{ $subtitle }}</flux:subheading>
            @endif
        </div>

        @isset($actions)
            <div class="flex shrink-0 flex-wrap items-center gap-2">
                {{ $actions }}
            </div>
        @endisset
    </div>
</div>
