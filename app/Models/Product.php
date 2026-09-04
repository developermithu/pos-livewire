<?php

namespace App\Models;

use App\Concerns\HasUniqueSlug;
use App\Support\Money;
use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * A sellable item in the catalog.
 *
 * Stock is deliberately absent: on-hand quantity is derived from the
 * append-only ledger that lands in phase 5, never stored as a column here.
 *
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property string $sku
 * @property string|null $barcode
 * @property int $category_id
 * @property int|null $brand_id
 * @property int $unit_id
 * @property string|null $description
 * @property Money $cost_price_cents
 * @property Money $price_cents
 * @property string $currency
 * @property int $reorder_point
 * @property bool $is_active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read Category $category
 * @property-read Brand|null $brand
 * @property-read Unit $unit
 */
#[Fillable([
    'name',
    'slug',
    'sku',
    'barcode',
    'category_id',
    'brand_id',
    'unit_id',
    'description',
    'cost_price_cents',
    'price_cents',
    'currency',
    'reorder_point',
    'is_active',
])]
class Product extends Model
{
    /** @use HasFactory<ProductFactory> */
    use HasFactory, HasUniqueSlug, SoftDeletes;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'cost_price_cents' => Money::class,
            'price_cents' => Money::class,
            'reorder_point' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<Category, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * @return BelongsTo<Brand, $this>
     */
    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    /**
     * @return BelongsTo<Unit, $this>
     */
    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    /**
     * The gross margin this product's price earns over its cost, as a
     * percentage. Null when the price is zero and the figure is undefined.
     */
    public function margin(): ?float
    {
        return $this->price_cents->marginOver($this->cost_price_cents);
    }

    /**
     * @param  Builder<static>  $query
     */
    #[Scope]
    protected function active(Builder $query): void
    {
        $query->where('is_active', true);
    }

    /**
     * Match against the fields a person would actually type: the name, the SKU
     * they read off a label, or a scanned barcode.
     *
     * @param  Builder<static>  $query
     */
    #[Scope]
    protected function search(Builder $query, ?string $term): void
    {
        $term = trim((string) $term);

        if ($term === '') {
            return;
        }

        $query->where(function (Builder $query) use ($term): void {
            $query->where('name', 'like', "%{$term}%")
                ->orWhere('sku', 'like', "%{$term}%")
                ->orWhere('barcode', 'like', "%{$term}%");
        });
    }
}
