---
paths:
  - 'app/Support/**'
---

# Support

## Navigation is defined once in App\Support\Navigation
`App\Support\Navigation` is the single source of truth for the primary navigation. The sidebar (`x-app.sidebar-nav`) and the command menu (`x-app.command-menu`) both render from it. Never hand-write a nav tree in a Blade file.

Items name a ROUTE, not a URL. `NavItem::to($label, $icon, $route, $pattern)` for a leaf, `NavItem::group($label, $icon, $children)` for an expandable parent. An item whose route does not exist yet reports `isAvailable() === false` and renders dimmed and inert, so the full information architecture is visible and each later phase lights its items up simply by registering the route — no edit to the nav tree required.

`pattern` is the `request()->routeIs()` pattern for the current-state check; pass e.g. `'products.*'` so child pages keep the parent highlighted. It defaults to the route name.

`destinations()` returns the flattened reachable list the command menu consumes. Add permission filtering here when policies land in phase 11 — one place, not per screen.
