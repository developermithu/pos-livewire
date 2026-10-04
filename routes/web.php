<?php

use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::livewire('dashboard', 'pages::dashboard')->name('dashboard');

    /*
    | Catalog. Registering these route names is what lights the Catalog group
    | up in the sidebar and the command menu — App\Support\Navigation reports
    | an item unavailable until its route exists, so the nav tree itself needs
    | no edit.
    */
    Route::livewire('products', 'pages::products.index')->name('products.index');
    Route::livewire('products/create', 'pages::products.form')->name('products.create');
    Route::livewire('products/{product}/edit', 'pages::products.form')->name('products.edit');

    Route::livewire('categories', 'pages::categories.index')->name('categories.index');
    Route::livewire('brands', 'pages::brands.index')->name('brands.index');
    Route::livewire('units', 'pages::units.index')->name('units.index');
});

require __DIR__.'/settings.php';
