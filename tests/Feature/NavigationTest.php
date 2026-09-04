<?php

use App\Models\User;
use App\Support\Navigation;
use App\Support\NavItem;

test('items pointing at a registered route are available and linkable', function () {
    $item = NavItem::to('Dashboard', 'home', 'dashboard');

    expect($item->isAvailable())->toBeTrue()
        ->and($item->url())->toBe(route('dashboard'));
});

test('items pointing at a route that does not exist yet are unavailable', function () {
    $item = NavItem::to('Products', 'tag', 'products.index');

    expect($item->isAvailable())->toBeFalse()
        ->and($item->url())->toBeNull();
});

test('a parent is available only once one of its children is', function () {
    $parent = NavItem::group('Catalog', 'squares-2x2', [
        NavItem::to('Products', 'tag', 'products.index'),
    ]);

    expect($parent->isAvailable())->toBeFalse()
        ->and($parent->url())->toBeNull();

    $reachable = NavItem::group('Overview', 'home', [
        NavItem::to('Dashboard', 'home', 'dashboard'),
    ]);

    expect($reachable->isAvailable())->toBeTrue();
});

test('a parent reports itself current when a child is current', function () {
    $this->actingAs(User::factory()->create())->get(route('dashboard'));

    $parent = NavItem::group('Overview', 'home', [
        NavItem::to('Dashboard', 'home', 'dashboard'),
    ]);

    expect($parent->isCurrent())->toBeTrue();
});

test('destinations only include routes that exist', function () {
    $destinations = (new Navigation)->destinations();

    expect($destinations)->toHaveCount(1)
        ->and($destinations[0]['label'])->toBe('Dashboard')
        ->and($destinations[0]['url'])->toBe(route('dashboard'));
});

test('the sidebar renders every group from the single navigation source', function () {
    $this->actingAs(User::factory()->create());

    $response = $this->get(route('dashboard'));

    $response->assertOk()
        ->assertSee('Overview')
        ->assertSee('Operations')
        ->assertSee('Records')
        ->assertSee('Point of Sale')
        ->assertSee('Catalog');
});
