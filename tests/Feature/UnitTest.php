<?php

use App\Models\Product;
use App\Models\Unit;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

test('guests are redirected to the login page', function () {
    auth()->logout();

    $this->get(route('units.index'))->assertRedirect(route('login'));
});

test('the index lists units', function () {
    Unit::factory()->create(['name' => 'Kilogram', 'code' => 'kg']);

    $this->get(route('units.index'))
        ->assertOk()
        ->assertSeeLivewire('pages::units.index')
        ->assertSee('Kilogram')
        ->assertSee('kg');
});

test('the index searches by name or code', function () {
    Unit::factory()->create(['name' => 'Kilogram', 'code' => 'kg']);
    Unit::factory()->create(['name' => 'Litre', 'code' => 'L']);

    Livewire::test('pages::units.index')
        ->set('search', 'kg')
        ->assertSee('Kilogram')
        ->assertDontSee('Litre');
});

test('a unit can be created', function () {
    Livewire::test('pages::units.index')
        ->call('createUnit')
        ->set('name', 'Kilogram')
        ->set('code', 'kg')
        ->set('allowsFractional', true)
        ->call('save')
        ->assertHasNoErrors();

    $unit = Unit::firstWhere('code', 'kg');

    expect($unit)->not->toBeNull()
        ->and($unit->name)->toBe('Kilogram')
        ->and($unit->allows_fractional)->toBeTrue();
});

test('a unit code must be unique', function () {
    Unit::factory()->create(['name' => 'Kilogram', 'code' => 'kg']);

    Livewire::test('pages::units.index')
        ->call('createUnit')
        ->set('name', 'Kilos')
        ->set('code', 'kg')
        ->call('save')
        ->assertHasErrors(['code' => 'unique']);
});

test('a unit requires a name and a code', function () {
    Livewire::test('pages::units.index')
        ->call('createUnit')
        ->call('save')
        ->assertHasErrors(['name' => 'required', 'code' => 'required']);
});

test('a unit can be edited', function () {
    $unit = Unit::factory()->create(['name' => 'Kilogram', 'code' => 'kg', 'allows_fractional' => false]);

    Livewire::test('pages::units.index')
        ->call('editUnit', $unit)
        ->assertSet('code', 'kg')
        ->set('allowsFractional', true)
        ->call('save')
        ->assertHasNoErrors();

    expect($unit->refresh()->allows_fractional)->toBeTrue();
});

test('an unused unit is soft deleted', function () {
    $unit = Unit::factory()->create();

    Livewire::test('pages::units.index')->call('delete', $unit);

    expect($unit->refresh()->trashed())->toBeTrue();
});

test('a unit still measuring products is not deleted', function () {
    $unit = Unit::factory()->create();
    Product::factory()->for($unit)->create();

    Livewire::test('pages::units.index')->call('delete', $unit);

    expect($unit->refresh()->trashed())->toBeFalse();
});
