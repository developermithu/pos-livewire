<?php

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

test('guests are redirected to the login page', function () {
    auth()->logout();

    $this->get(route('categories.index'))->assertRedirect(route('login'));
});

test('the index lists categories', function () {
    Category::factory()->create(['name' => 'Coffee']);
    Category::factory()->create(['name' => 'Bakery']);

    $this->get(route('categories.index'))
        ->assertOk()
        ->assertSeeLivewire('pages::categories.index')
        ->assertSee('Coffee')
        ->assertSee('Bakery');
});

test('the index searches by name', function () {
    Category::factory()->create(['name' => 'Coffee']);
    Category::factory()->create(['name' => 'Bakery']);

    Livewire::test('pages::categories.index')
        ->set('search', 'Cof')
        ->assertSee('Coffee')
        ->assertDontSee('Bakery');
});

test('a category can be created', function () {
    Livewire::test('pages::categories.index')
        ->call('createCategory')
        ->set('name', 'Cold Drinks')
        ->set('description', 'Chilled and ready to go')
        ->call('save')
        ->assertHasNoErrors();

    $category = Category::firstWhere('name', 'Cold Drinks');

    expect($category)->not->toBeNull()
        ->and($category->slug)->toBe('cold-drinks')
        ->and($category->description)->toBe('Chilled and ready to go')
        ->and($category->is_active)->toBeTrue();
});

test('creating a category requires a unique name', function () {
    Category::factory()->create(['name' => 'Coffee']);

    Livewire::test('pages::categories.index')
        ->call('createCategory')
        ->set('name', 'Coffee')
        ->call('save')
        ->assertHasErrors(['name' => 'unique']);

    expect(Category::where('name', 'Coffee')->count())->toBe(1);
});

test('creating a category requires a name', function () {
    Livewire::test('pages::categories.index')
        ->call('createCategory')
        ->set('name', '')
        ->call('save')
        ->assertHasErrors(['name' => 'required']);
});

test('a category can be edited', function () {
    $category = Category::factory()->create(['name' => 'Coffee', 'is_active' => true]);

    Livewire::test('pages::categories.index')
        ->call('editCategory', $category)
        ->assertSet('name', 'Coffee')
        ->set('name', 'Hot Drinks')
        ->set('isActive', false)
        ->call('save')
        ->assertHasNoErrors();

    $category->refresh();

    expect($category->name)->toBe('Hot Drinks')
        ->and($category->is_active)->toBeFalse();
});

test('editing a category keeps its own name available', function () {
    $category = Category::factory()->create(['name' => 'Coffee']);

    Livewire::test('pages::categories.index')
        ->call('editCategory', $category)
        ->set('description', 'Beans and ground')
        ->call('save')
        ->assertHasNoErrors();

    expect($category->refresh()->description)->toBe('Beans and ground');
});

test('an empty category is soft deleted', function () {
    $category = Category::factory()->create();

    Livewire::test('pages::categories.index')
        ->call('delete', $category);

    expect($category->refresh()->trashed())->toBeTrue();
});

test('a category holding products is not deleted', function () {
    $category = Category::factory()->create();
    Product::factory()->for($category)->create();

    Livewire::test('pages::categories.index')
        ->call('delete', $category);

    expect($category->refresh()->trashed())->toBeFalse();
});
