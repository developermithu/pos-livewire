---
paths:
  - 'resources/views/components/**'
---

# Components

## ⚡ prefix decides Livewire vs Blade; ui/ and domain/ layers
`resources/views/components` is BOTH the Blade anonymous-component dir and a Livewire component location. The filename decides the runtime: `⚡name.blade.php` is a Livewire SFC used as `<livewire:name />`; plain `name.blade.php` is a Blade component used as `<x-name />`. Check which you intend before creating a file.

Layering, and the dependency direction is one-way:
- `ui/**` — design-system primitives. Props in, markup out. No queries, no enums, no model access. (x-ui.page-header, x-ui.panel, x-ui.stat-card so far.)
- `domain/**` — may read enums and model attributes, still stateless and query-free. If it needs a query it belongs in `pages/`.
`ui/` never depends on `domain/`.

Never wrap a Flux component in a Blade component that only forwards attributes — pass classes, or eject the Flux component properly (see resources/views/flux/navlist/group.blade.php). Never make a Livewire component that renders markup and holds no state. Grep this directory and the Flux free inventory before adding anything.
