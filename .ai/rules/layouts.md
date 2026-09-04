---
paths:
  - 'resources/views/layouts/**'
---

# Layouts

## Never hardcode the dark class; nav gets one source
Do NOT put `class="dark"` on `<html>`. `@fluxAppearance` in `partials/head.blade.php` owns that class — it reads the stored preference and the system setting before first paint. Hardcoding it makes light mode unreachable (this was the shipped starter-kit bug, fixed in phase 1). Drive appearance through `window.Flux.applyAppearance()` / `$flux.appearance`; the localStorage key is `flux.appearance`. Note the class is applied on an Alpine effect, so it is not observable synchronously right after setting it.

Shells use tokens for chrome: `bg-surface text-ink` on body, `bg-surface-sunken border-line` on sidebar/header.

Navigation must be rendered from ONE source (an `App\Support\Navigation` class or `config/navigation.php`), consumed by both the desktop sidebar and the mobile sheet. The kit shipped with the tree hand-written in three places; do not add a fourth. Target end state is two shells only: `app` and `pos`.
