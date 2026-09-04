---
paths:
  - 'resources/views/pages/**'
---

# Pages

## Pages are Livewire full-page components routed with Route::livewire
Every routed page is a Livewire component under `resources/views/pages` and is routed with `Route::livewire('path', 'pages::feature.name')->name(...)`. No `Route::view` — mixed routing styles fragment the kit. The default layout (`layouts::app`) is applied automatically; set the browser title with `#[Title('…')]` on the component class.

Every page opens with `<x-ui.page-header>` carrying `:title`, optional `:subtitle`, `:breadcrumbs` (array of `['label' => …, 'href' => …]`, omit `href` for the current/section crumb) and an `<x-slot:actions>`. Page actions belong here, next to the title they act on — never in the application header.

Prefer SFC. Promote to MFC (`php artisan make:livewire … --mfc`) only when the component genuinely has its own JavaScript or a test worth colocating.
