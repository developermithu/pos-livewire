---
paths:
  - resources/css/app.css
---

# Css

## All colour, radius and shadow lives in @theme tokens
Semantic tokens are declared once in `@theme` and overridden for dark mode in the single `@layer theme { .dark { … } }` block. Components use `bg-surface`, `text-ink-muted`, `border-line`, `text-positive`, `bg-stock-low` etc. and must NOT carry a `dark:` variant or a literal hex — dark mode is one block of CSS, not a decision at every call site.

Two radii only: `rounded-control` (0.5rem, matches Flux controls) and `rounded-surface` (0.75rem, panels). Static surfaces are separated by a 1px `border-line` and never a shadow; `shadow-overlay` is reserved for things that actually float (dropdown, modal, toast, POS cart drawer).

The `zinc-*` ramp override is load-bearing, not a mistake: Flux hardcodes `zinc-*` across ~71 of its own components, so overriding that ramp is the supported way to retheme Flux's neutrals. Do not delete it.
