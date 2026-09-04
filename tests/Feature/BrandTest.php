<?php

use App\Models\Brand;
use App\Models\Product;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

test('guests are redirected to the login page', function () {
    auth()->logout();

    $this->get(route('brands.index'))->assertRedirect(route('login'));
});

test('the index lists brands', function () {
    Brand::factory()->create(['name' => 'Highland Roasters']);

    $this->get(route('brands.index'))
        ->assertOk()
        ->assertSeeLivewire('pages::brands.index')
        ->assertSee('Highland Roasters');
});

test('a brand can be created', function () {
    Livewire::test('pages::brands.index')
        ->call('createBrand')
        ->set('name', 'Monin')
        ->call('save')
        ->assertHasNoErrors();

    $brand = Brand::firstWhere('name', 'Monin');

    expect($brand)->not->toBeNull()
        ->and($brand->slug)->toBe('monin');
});

test('a brand name must be unique', function () {
    Brand::factory()->create(['name' => 'Monin']);

    Livewire::test('pages::brands.index')
        ->call('createBrand')
        ->set('name', 'Monin')
        ->call('save')
        ->assertHasErrors(['name' => 'unique']);
});

test('a brand can be edited', function () {
    $brand = Brand::factory()->create(['name' => 'Monin', 'is_active' => true]);

    Livewire::test('pages::brands.index')
        ->call('editBrand', $brand)
        ->set('name', 'Monin Syrups')
        ->set('isActive', false)
        ->call('save')
        ->assertHasNoErrors();

    $brand->refresh();

    expect($brand->name)->toBe('Monin Syrups')
        ->and($brand->is_active)->toBeFalse();
});

test('an empty brand is soft deleted', function () {
    $brand = Brand::factory()->create();

    Livewire::test('pages::brands.index')->call('delete', $brand);

    expect($brand->refresh()->trashed())->toBeTrue();
});

test('a brand holding products is not deleted', function () {
    $brand = Brand::factory()->create();
    Product::factory()->for($brand)->create();

    Livewire::test('pages::brands.index')->call('delete', $brand);

    expect($brand->refresh()->trashed())->toBeFalse();
});
