---
paths:
  - 'tests/**'
---

# Tests

## Tests run on MySQL, the same engine as the app
`phpunit.xml` points at the MySQL schema `pos-livewire-test`, not SQLite. This is deliberate: the inventory domain depends on `lockForUpdate()`, decimal handling and full-text search, and SQLite cannot express the concurrency behaviour the tests exist to catch. Do not "simplify" it back to `sqlite :memory:`.

Create the schema locally if it is missing:
`CREATE DATABASE \`pos-livewire-test\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;`

Feature tests over unit tests. Test the Action and the page component, not Blade markup. For a Livewire page, assert through the route (`assertSeeLivewire('pages::…')`) rather than rendering the layout by hand.
