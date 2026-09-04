@props([
    'active' => true,
])

{{--
    Whether a catalog record may be used on new documents. Deliberately not
    drawn in the accent hue — the accent means "primary action or current
    location" and nothing else.
--}}

<flux:badge size="sm" :color="$active ? 'green' : 'zinc'" :variant="$active ? 'solid' : 'outline'">
    {{ $active ? __('Active') : __('Inactive') }}
</flux:badge>
