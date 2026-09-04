@props([
    'heading' => null,
    'subheading' => null,
    'padded' => true,
])

{{--
    A bordered content surface. Static surfaces are separated by a hairline and
    never a shadow — elevation is reserved for things that actually float
    (dropdowns, modals, toasts, the POS cart drawer).

    Pass `:padded="false"` when the slot is a table or list that should meet the
    panel edges.
--}}

<section {{ $attributes->class('overflow-hidden rounded-surface border border-line bg-surface-raised') }}>
    @if (filled($heading) || isset($actions))
        <header class="flex items-center justify-between gap-4 border-b border-line px-5 py-3.5">
            <div class="min-w-0">
                @if (filled($heading))
                    <flux:heading size="sm">{{ $heading }}</flux:heading>
                @endif

                @if (filled($subheading))
                    <flux:subheading size="sm">{{ $subheading }}</flux:subheading>
                @endif
            </div>

            @isset($actions)
                <div class="flex shrink-0 items-center gap-2">
                    {{ $actions }}
                </div>
            @endisset
        </header>
    @endif

    <div @class(['px-5 py-4' => $padded])>
        {{ $slot }}
    </div>
</section>
