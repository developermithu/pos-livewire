@props([
    'variant' => 'sidebar',
])

{{--
    The account dropdown. One component serves the sidebar footer and the
    mobile header so the logout form exists exactly once — it was previously
    written out in two shells.
--}}

@php
    $user = auth()->user();
@endphp

<flux:dropdown
    :position="$variant === 'sidebar' ? 'bottom' : 'top'"
    :align="$variant === 'sidebar' ? 'start' : 'end'"
    {{ $attributes }}
>
    @if ($variant === 'sidebar')
        <flux:sidebar.profile
            :name="$user->name"
            :initials="$user->initials()"
            icon:trailing="chevrons-up-down"
            data-test="sidebar-menu-button"
        />
    @else
        <flux:profile
            :initials="$user->initials()"
            icon-trailing="chevron-down"
            data-test="header-menu-button"
        />
    @endif

    <flux:menu>
        <div class="flex items-center gap-2 px-1 py-1.5 text-start text-sm">
            <flux:avatar :name="$user->name" :initials="$user->initials()" />

            <div class="grid flex-1 text-start text-sm leading-tight">
                <flux:heading class="truncate">{{ $user->name }}</flux:heading>
                <flux:text class="truncate">{{ $user->email }}</flux:text>
            </div>
        </div>

        <flux:menu.separator />

        <flux:menu.item :href="route('profile.edit')" icon="cog" wire:navigate>
            {{ __('Settings') }}
        </flux:menu.item>

        <flux:menu.separator />

        <form method="POST" action="{{ route('logout') }}" class="w-full">
            @csrf

            <flux:menu.item
                as="button"
                type="submit"
                icon="arrow-right-start-on-rectangle"
                class="w-full cursor-pointer"
                data-test="logout-button"
            >
                {{ __('Log out') }}
            </flux:menu.item>
        </form>
    </flux:menu>
</flux:dropdown>
