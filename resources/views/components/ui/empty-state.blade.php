@props([
    'icon' => 'inbox',
    'heading',
    'description' => null,
])

{{--
    Shown where a table or list would be. The slot carries the action that
    resolves the emptiness — creating the first record, or clearing a filter —
    so an empty screen always offers a way forward.
--}}

<div {{ $attributes->class('flex flex-col items-center justify-center gap-3 px-6 py-14 text-center') }}>
    <span class="flex size-11 items-center justify-center rounded-surface bg-surface-sunken text-ink-faint">
        <flux:icon :icon="$icon" class="size-6" />
    </span>

    <div class="space-y-1">
        <flux:heading size="sm">{{ $heading }}</flux:heading>

        @if (filled($description))
            <flux:subheading class="mx-auto max-w-sm">{{ $description }}</flux:subheading>
        @endif
    </div>

    @if (filled($slot))
        <div class="mt-1 flex items-center gap-2">
            {{ $slot }}
        </div>
    @endif
</div>
