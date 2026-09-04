<?php

namespace Database\Seeders;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\Unit;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * A small, realistic catalog for a café-style store.
 *
 * Hand-written rather than generated: the dashboard, the stock ledger and the
 * register are all easier to reason about against products a person recognises
 * than against three words of lorem ipsum.
 */
class CatalogSeeder extends Seeder
{
    public function run(): void
    {
        $units = collect([
            ['name' => 'Piece', 'code' => 'pc', 'allows_fractional' => false],
            ['name' => 'Kilogram', 'code' => 'kg', 'allows_fractional' => true],
            ['name' => 'Litre', 'code' => 'L', 'allows_fractional' => true],
            ['name' => 'Pack', 'code' => 'pack', 'allows_fractional' => false],
        ])->mapWithKeys(fn (array $unit): array => [
            $unit['code'] => Unit::firstOrCreate(['code' => $unit['code']], $unit),
        ]);

        $categories = collect(['Coffee', 'Dairy', 'Syrups', 'Packaging', 'Bakery'])
            ->mapWithKeys(fn (string $name): array => [
                $name => Category::firstOrCreate(
                    ['slug' => Str::slug($name)],
                    ['name' => $name, 'is_active' => true],
                ),
            ]);

        $brands = collect(['Highland Roasters', 'Oatly', 'Monin', 'Generic'])
            ->mapWithKeys(fn (string $name): array => [
                $name => Brand::firstOrCreate(
                    ['slug' => Str::slug($name)],
                    ['name' => $name, 'is_active' => true],
                ),
            ]);

        $products = [
            ['Arabica Beans 1kg', 'COF-ARB-1000', 'Coffee', 'Highland Roasters', 'kg', 1_850, 3_200, 24],
            ['Robusta Beans 1kg', 'COF-ROB-1000', 'Coffee', 'Highland Roasters', 'kg', 1_400, 2_450, 24],
            ['Decaf Blend 500g', 'COF-DEC-0500', 'Coffee', 'Highland Roasters', 'kg', 1_100, 1_980, 12],
            ['Oat Milk 1L', 'DRY-OAT-1000', 'Dairy', 'Oatly', 'L', 180, 340, 48],
            ['Whole Milk 2L', 'DRY-WHL-2000', 'Dairy', 'Generic', 'L', 150, 260, 48],
            ['Vanilla Syrup 750ml', 'SYR-VAN-0750', 'Syrups', 'Monin', 'pc', 620, 1_150, 12],
            ['Caramel Syrup 750ml', 'SYR-CAR-0750', 'Syrups', 'Monin', 'pc', 620, 1_150, 12],
            ['Paper Cups 12oz', 'PKG-CUP-0120', 'Packaging', 'Generic', 'pack', 890, 1_400, 500],
            ['Takeaway Lids 12oz', 'PKG-LID-0120', 'Packaging', 'Generic', 'pack', 430, 760, 500],
            ['Butter Croissant', 'BAK-CRS-0001', 'Bakery', null, 'pc', 95, 285, 30],
            ['Almond Danish', 'BAK-DAN-0001', 'Bakery', null, 'pc', 120, 340, 20],
        ];

        foreach ($products as [$name, $sku, $category, $brand, $unit, $cost, $price, $reorderPoint]) {
            Product::firstOrCreate(['sku' => $sku], [
                'name' => $name,
                'slug' => Str::slug($name),
                'category_id' => $categories[$category]->id,
                'brand_id' => $brand !== null ? $brands[$brand]->id : null,
                'unit_id' => $units[$unit]->id,
                'cost_price_cents' => $cost,
                'price_cents' => $price,
                'currency' => 'USD',
                'reorder_point' => $reorderPoint,
                'is_active' => true,
            ]);
        }
    }
}
