@php
    $destinations = app(\App\Support\Navigation::class)->destinations();
@endphp

{{--
    Keyboard-first navigation over the same tree the sidebar renders.

    Entirely Alpine: opening, filtering and arrow-key selection must not cost a
    server round trip. When the catalog and sales schemas land, a lazy Livewire
    island is added inside this shell for record search — the keyboard handling
    stays here.
--}}

<div
    x-data="commandMenu(@js($destinations))"
    x-on:keydown.window.meta.k.prevent="show()"
    x-on:keydown.window.ctrl.k.prevent="show()"
>
    <button
        type="button"
        x-on:click="show()"
        class="flex h-8 items-center gap-2 rounded-control border border-line bg-surface px-2.5 text-ink-faint transition-colors hover:text-ink-muted focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-accent"
        :aria-expanded="open"
        aria-haspopup="dialog"
        data-test="command-menu-trigger"
    >
        <flux:icon.magnifying-glass variant="micro" />
        <span class="max-sm:hidden">{{ __('Search') }}</span>
        <kbd class="rounded border border-line px-1 font-sans text-[10px] leading-4 max-sm:hidden">&#8984;K</kbd>
    </button>

    <div
        x-show="open"
        x-cloak
        x-on:keydown.escape.window="hide()"
        class="fixed inset-0 z-50 p-4"
        role="dialog"
        aria-modal="true"
        :aria-label="'{{ __('Search') }}'"
    >
        <div class="absolute inset-0 bg-black/40" x-on:click="hide()" aria-hidden="true"></div>

        <div class="relative mx-auto mt-[12vh] w-full max-w-lg overflow-hidden rounded-surface border border-line bg-surface-raised shadow-overlay">
            <div class="flex items-center gap-2.5 border-b border-line px-4">
                <flux:icon.magnifying-glass variant="micro" class="shrink-0 text-ink-faint" />

                <input
                    x-ref="input"
                    x-model="query"
                    x-on:keydown.down.prevent="move(1)"
                    x-on:keydown.up.prevent="move(-1)"
                    x-on:keydown.enter.prevent="go()"
                    type="text"
                    class="h-11 w-full bg-transparent text-sm text-ink placeholder:text-ink-faint focus:outline-none"
                    placeholder="{{ __('Go to…') }}"
                    autocomplete="off"
                    spellcheck="false"
                />
            </div>

            <ul class="max-h-80 overflow-y-auto p-1.5" role="listbox">
                <template x-for="(item, index) in results" :key="item.url">
                    <li>
                        <button
                            type="button"
                            role="option"
                            :aria-selected="index === activeIndex"
                            x-on:click="go(index)"
                            x-on:mousemove="activeIndex = index"
                            class="flex w-full items-center justify-between gap-3 rounded-control px-3 py-2 text-left transition-colors"
                            :class="index === activeIndex ? 'bg-accent-wash text-ink' : 'text-ink-muted'"
                        >
                            <span class="truncate text-sm" x-text="item.label"></span>
                            <span class="shrink-0 text-xs text-ink-faint" x-text="item.group"></span>
                        </button>
                    </li>
                </template>
            </ul>

            <p x-show="results.length === 0" class="px-4 py-6 text-center text-sm text-ink-faint">
                {{ __('Nothing matches that yet.') }}
            </p>
        </div>
    </div>
</div>
