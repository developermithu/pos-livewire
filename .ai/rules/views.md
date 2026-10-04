---
paths:
  - 'resources/views/**'
---

# Views

## Flux is the FREE edition — these components do not exist
This project uses Flux free (livewire/flux, no flux-pro). Pro tags fail silently as unresolved markup. NOT available: `tabs`, `date-picker`, `calendar`, `command`/autocomplete, `combobox`, `chart`, `editor`, `accordion`, `context-menu`, `kanban`, `dropzone`. Build these as custom Blade + Alpine (`x-ui.tabs` etc.).

Available and should be used rather than reimplemented: button, input, select, checkbox, radio, switch, field/label/error, dropdown, menu, modal, tooltip, toast, badge, avatar, breadcrumbs, pagination, separator, table, skeleton, progress, callout, card, sidebar, navlist, navbar, navmenu, heading, subheading, text, profile, otp.

`--color-accent{,-content,-foreground}` is Flux's theming contract: it drives `flux:button variant="primary"` AND the current sidebar item. That is why one accent means "primary action or current location" and nothing else — never use it for status.

Icons: Heroicons via `<flux:icon.name />` or `<flux:icon :$icon />`. Pull Lucide gaps in with `php artisan flux:icon <name>`. Sizes 16 (inline/menu), 20 (nav/toolbar), 24 (empty states). Raw inline `<svg>` in a page template is not acceptable.

Every column of figures — money, quantities, SKUs, order numbers — gets `tabular-nums`.

## Never nest a __() array argument inside a Blade component attribute
Blade's component-attribute parser splits on commas before it parses PHP, so an array argument inside a component attribute expression compiles to garbage and throws a ParseError at render time — not at compile time, so it looks like a runtime bug.

Broken: `<x-ui.empty-state :description="__('Nothing matches :term.', ['term' => $search])" />`
Also broken on Flux tags: `<flux:button :aria-label="__('Actions for :name', ['name' => $x])" />`

This only affects component tags (`<x-…>`, `<flux:…>`), not plain HTML elements.

Fix: precompute the string first — `@php($label = __('Actions for :name', ['name' => $x]))` then `:aria-label="$label"`, or expose it as a `#[Computed]` property on the page component. Applies to `wire:confirm` on a Flux tag too: bind it (`:wire:confirm="$prompt"`) rather than interpolating.
