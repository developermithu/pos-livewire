---
paths:
  - 'resources/js/**'
---

# Js

## Alpine ships inside Livewire — there is no npm Alpine
Alpine is bundled inside Livewire's own script; it is NOT a package.json dependency and `resources/js/app.js` is effectively empty. Never write `import Alpine from 'alpinejs'` — it will fail silently or pull in a second Alpine instance.

Register components on the event instead:

```js
document.addEventListener('alpine:init', () => {
    Alpine.data('quantityStepper', () => ({ /* … */ }))
})
```

Use Alpine for interaction that must not cost a server round trip (command-menu keyboard nav, barcode keystroke buffer, quantity stepper, POS keypad, copy-to-clipboard). Anything with server state is Livewire.
