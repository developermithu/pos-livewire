<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-surface text-ink">
        <flux:sidebar sticky collapsible="mobile" class="border-e border-line bg-surface-sunken">
            <flux:sidebar.header>
                <x-app-logo :sidebar="true" href="{{ route('dashboard') }}" wire:navigate />
                <flux:sidebar.collapse class="lg:hidden" />
            </flux:sidebar.header>

            <flux:sidebar.nav>
                <x-app.sidebar-nav />
            </flux:sidebar.nav>

            <flux:spacer />

            <flux:sidebar.nav>
                <flux:sidebar.item icon="cog" :href="route('profile.edit')" :current="request()->routeIs('profile.edit', 'security.edit', 'appearance.edit')" wire:navigate>
                    {{ __('Settings') }}
                </flux:sidebar.item>
            </flux:sidebar.nav>

            <x-app.user-menu class="hidden lg:block" />
        </flux:sidebar>

        <flux:header class="border-b border-line bg-surface">
            <flux:sidebar.toggle class="lg:hidden" icon="bars-2" inset="left" />

            <flux:spacer />

            <x-app.command-menu />

            <x-app.user-menu variant="header" class="lg:hidden" />
        </flux:header>

        {{ $slot }}

        @persist('toast')
            <flux:toast.group>
                <flux:toast />
            </flux:toast.group>
        @endpersist

        @fluxScripts
    </body>
</html>
