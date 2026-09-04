@php
    $groups = app(\App\Support\Navigation::class)->groups();
@endphp

{{--
    The primary navigation, rendered from App\Support\Navigation. This is the
    only place the tree is drawn — the desktop sidebar and the mobile sheet
    both mount this component.
--}}

@foreach ($groups as $group)
    <flux:sidebar.group :heading="$group->heading" class="grid">
        @foreach ($group->items as $item)
            @if ($item->hasChildren())
                <flux:sidebar.group
                    expandable
                    :heading="$item->label"
                    :icon="$item->icon"
                    :expanded="$item->isCurrent()"
                >
                    @foreach ($item->children as $child)
                        <x-app.nav-item :item="$child" />
                    @endforeach
                </flux:sidebar.group>
            @else
                <x-app.nav-item :item="$item" />
            @endif
        @endforeach
    </flux:sidebar.group>
@endforeach
