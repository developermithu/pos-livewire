<?php

use App\Models\User;

test('guests are redirected to the login page', function () {
    $response = $this->get(route('dashboard'));
    $response->assertRedirect(route('login'));
});

test('authenticated users can visit the dashboard', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->get(route('dashboard'));
    $response->assertOk();
});

test('the dashboard renders as a livewire page component', function () {
    $this->actingAs(User::factory()->create());

    $this->get(route('dashboard'))
        ->assertOk()
        ->assertSeeLivewire('pages::dashboard')
        ->assertSee('Needs reorder')
        ->assertSee('Recent activity');
});
