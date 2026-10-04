<?php

namespace Database\Factories;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\Unit;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // The leading word is drawn uniquely, which keeps the derived slug
        // unique too — `slug` carries a unique index.
        $name = Str::title(fake()->unique()->word().' '.fake()->word().' '.fake()->word());

        // Price is derived from cost so that generated products carry a
        // plausible margin rather than a random one, which keeps margin
        // assertions and reports meaningful.
        $costCents = fake()->numberBetween(50, 40_000);

        return [
            'name' => $name,
            'slug' => Str::slug($name),
            'sku' => Str::upper(fake()->unique()->bothify('???-####')),
            'barcode' => fake()->optional(0.7)->ean13(),
            'category_id' => Category::factory(),
            'brand_id' => Brand::factory(),
            'unit_id' => Unit::factory(),
            'description' => fake()->optional()->paragraph(),
            'cost_price_cents' => $costCents,
            'price_cents' => (int) round($costCents * fake()->randomFloat(2, 1.15, 2.4)),
            'currency' => 'USD',
            'reorder_point' => fake()->randomElement([0, 6, 12, 24, 48]),
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (): array => ['is_active' => false]);
    }

    /**
     * A product with no brand — own-label or unbranded stock.
     */
    public function unbranded(): static
    {
        return $this->state(fn (): array => ['brand_id' => null]);
    }
}
