---
paths:
  - 'database/migrations/**'
---

# Migrations

## Money as minor units; stock as an append-only ledger
Settled schema decisions for the POS domain. All three are schema-wide and expensive to retrofit:

1. Money is an integer of minor units (`unsignedBigInteger price_cents`) plus a currency code, wrapped in an `App\Support\Money` cast. Never `float`.
2. Stock is a ledger, not a column. Append-only `stock_movements` (product, location, qty delta, type, reference morph, actor, occurred_at) is the truth; `product_stock` holds the per-product-per-location cached quantity, updated in the SAME transaction with `lockForUpdate()`. Every sale, return, receipt, transfer and adjustment writes a movement. Never increment a `products.stock` column in place.
3. `location_id` goes on stock and sales rows from the first migration even while the app is single-location.

Completed sales are immutable — corrections are new return/void rows, never updates. Catalog models (products, variants) soft-delete so POS history can still resolve a deleted product's name.
