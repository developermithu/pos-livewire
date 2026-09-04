<?php

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\Unit;
use App\Models\User;
use App\Support\Money;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

test('guests are redirected to the login page', function () {
    auth()->logout();

    $this->get(route('products.index'))->assertRedirect(route('login'));
    $this->get(route('products.create'))->assertRedirect(route('login'));
});

test('the index lists products with their price', function () {
    Product::factory()->create([
        'name' => 'Arabica Beans 1kg',
        'sku' => 'COF-ARB-1000',
        'price_cents' => 3_200,
    ]);

    $this->get(route('products.index'))
        ->assertOk()
        ->assertSeeLivewire('pages::products.index')
        ->assertSee('Arabica Beans 1kg')
        ->assertSee('COF-ARB-1000')
        ->assertSee('$32.00');
});

test('the index searches by name, sku and barcode', function () {
    Product::factory()->create(['name' => 'Arabica Beans', 'sku' => 'COF-ARB', 'barcode' => '5012345678900']);
    Product::factory()->create(['name' => 'Oat Milk', 'sku' => 'DRY-OAT', 'barcode' => '5099999999999']);

    $component = Livewire::test('pages::products.index');

    $component->set('search', 'Arabica')->assertSee('Arabica Beans')->assertDontSee('Oat Milk');
    $component->set('search', 'DRY-OAT')->assertSee('Oat Milk')->assertDontSee('Arabica Beans');
    $component->set('search', '5012345678900')->assertSee('Arabica Beans')->assertDontSee('Oat Milk');
});

test('the index filters by category, brand and status', function () {
    $coffee = Category::factory()->create(['name' => 'Coffee']);
    $dairy = Category::factory()->create(['name' => 'Dairy']);
    $monin = Brand::factory()->create(['name' => 'Monin']);

    Product::factory()->for($coffee)->for($monin)->create(['name' => 'Arabica Beans']);
    Product::factory()->for($dairy)->create(['name' => 'Oat Milk']);
    Product::factory()->for($dairy)->inactive()->create(['name' => 'Retired Cream']);

    Livewire::test('pages::products.index')
        ->set('categoryId', (string) $coffee->id)
        ->assertSee('Arabica Beans')
        ->assertDontSee('Oat Milk');

    Livewire::test('pages::products.index')
        ->set('brandId', (string) $monin->id)
        ->assertSee('Arabica Beans')
        ->assertDontSee('Oat Milk');

    Livewire::test('pages::products.index')
        ->set('status', 'inactive')
        ->assertSee('Retired Cream')
        ->assertDontSee('Arabica Beans');
});

test('filters can be cleared', function () {
    Product::factory()->create(['name' => 'Arabica Beans']);

    Livewire::test('pages::products.index')
        ->set('search', 'nothing-matches-this')
        ->assertDontSee('Arabica Beans')
        ->call('clearFilters')
        ->assertSet('search', '')
        ->assertSee('Arabica Beans');
});

test('the create form renders', function () {
    Category::factory()->create();
    Unit::factory()->create();

    $this->get(route('products.create'))
        ->assertOk()
        ->assertSeeLivewire('pages::products.form')
        ->assertSee('New product');
});

test('a product can be created', function () {
    $category = Category::factory()->create();
    $brand = Brand::factory()->create();
    $unit = Unit::factory()->create();

    Livewire::test('pages::products.form')
        ->set('name', 'Arabica Beans 1kg')
        ->set('sku', 'COF-ARB-1000')
        ->set('barcode', '5012345678900')
        ->set('categoryId', (string) $category->id)
        ->set('brandId', (string) $brand->id)
        ->set('unitId', (string) $unit->id)
        ->set('costPrice', '18.50')
        ->set('price', '32.00')
        ->set('reorderPoint', '24')
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect(route('products.index'));

    $product = Product::firstWhere('sku', 'COF-ARB-1000');

    expect($product)->not->toBeNull()
        ->and($product->slug)->toBe('arabica-beans-1kg')
        ->and($product->cost_price_cents->minorUnits)->toBe(1850)
        ->and($product->price_cents->minorUnits)->toBe(3200)
        ->and($product->reorder_point)->toBe(24)
        ->and($product->brand_id)->toBe($brand->id)
        ->and($product->is_active)->toBeTrue();
});

test('a product can be created without a brand', function () {
    $category = Category::factory()->create();
    $unit = Unit::factory()->create();

    Livewire::test('pages::products.form')
        ->set('name', 'Own Label Sugar')
        ->set('sku', 'GEN-SUG-0001')
        ->set('categoryId', (string) $category->id)
        ->set('unitId', (string) $unit->id)
        ->set('costPrice', '1.00')
        ->set('price', '2.00')
        ->call('save')
        ->assertHasNoErrors();

    expect(Product::firstWhere('sku', 'GEN-SUG-0001')->brand_id)->toBeNull();
});

test('creating a product requires name, sku, category and unit', function () {
    Livewire::test('pages::products.form')
        ->set('name', '')
        ->set('sku', '')
        ->set('categoryId', '')
        ->set('unitId', '')
        ->call('save')
        ->assertHasErrors([
            'name' => 'required',
            'sku' => 'required',
            'categoryId' => 'required',
            'unitId' => 'required',
        ]);

    expect(Product::count())->toBe(0);
});

test('a sku must be unique', function () {
    Product::factory()->create(['sku' => 'COF-ARB-1000']);

    $category = Category::factory()->create();
    $unit = Unit::factory()->create();

    Livewire::test('pages::products.form')
        ->set('name', 'Another Coffee')
        ->set('sku', 'COF-ARB-1000')
        ->set('categoryId', (string) $category->id)
        ->set('unitId', (string) $unit->id)
        ->call('save')
        ->assertHasErrors(['sku' => 'unique']);
});

test('prices must be numeric and not negative', function () {
    $category = Category::factory()->create();
    $unit = Unit::factory()->create();

    Livewire::test('pages::products.form')
        ->set('name', 'Odd Product')
        ->set('sku', 'ODD-0001')
        ->set('categoryId', (string) $category->id)
        ->set('unitId', (string) $unit->id)
        ->set('costPrice', '-1')
        ->set('price', 'free')
        ->call('save')
        ->assertHasErrors(['costPrice' => 'min', 'price' => 'numeric']);
});

test('the edit form loads the product and saves changes', function () {
    $product = Product::factory()->create([
        'name' => 'Arabica Beans',
        'sku' => 'COF-ARB-1000',
        'price_cents' => 3_200,
        'cost_price_cents' => 1_850,
    ]);

    $this->get(route('products.edit', $product))
        ->assertOk()
        ->assertSeeLivewire('pages::products.form');

    Livewire::test('pages::products.form', ['product' => $product])
        ->assertSet('name', 'Arabica Beans')
        ->assertSet('price', '32.00')
        ->assertSet('costPrice', '18.50')
        ->set('name', 'Arabica Beans 1kg')
        ->set('price', '34.50')
        ->call('save')
        ->assertHasNoErrors();

    $product->refresh();

    expect($product->name)->toBe('Arabica Beans 1kg')
        ->and($product->price_cents->minorUnits)->toBe(3450);
});

test('a product keeps its own sku when edited', function () {
    $product = Product::factory()->create(['sku' => 'COF-ARB-1000']);

    Livewire::test('pages::products.form', ['product' => $product])
        ->set('name', 'Renamed')
        ->call('save')
        ->assertHasNoErrors();

    expect($product->refresh()->name)->toBe('Renamed');
});

test('the form previews the margin the entered prices imply', function () {
    Livewire::test('pages::products.form')
        ->set('costPrice', '10.00')
        ->set('price', '25.00')
        ->assertSee('60.0%');
});

test('a product is soft deleted so sales history still resolves it', function () {
    $product = Product::factory()->create();

    Livewire::test('pages::products.index')->call('delete', $product);

    expect($product->refresh()->trashed())->toBeTrue()
        ->and(Product::withTrashed()->find($product->id)->name)->toBe($product->name);
});

test('margin is null when the price is zero', function () {
    $product = Product::factory()->create(['price_cents' => 0, 'cost_price_cents' => 500]);

    expect($product->margin())->toBeNull();
});

test('money round trips through the database as minor units', function () {
    $product = Product::factory()->create(['price_cents' => 1999, 'currency' => 'USD']);

    expect($product->fresh()->price_cents)->toBeInstanceOf(Money::class)
        ->and($product->fresh()->price_cents->minorUnits)->toBe(1999)
        ->and($product->fresh()->price_cents->format())->toBe('$19.99');
});
