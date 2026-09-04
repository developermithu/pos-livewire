<?php

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\Unit;
use App\Support\Money;
use Flux\Flux;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Component;

/**
 * Creates and edits a product.
 *
 * One component serves both routes: the fields, the rules and the layout are
 * identical, and keeping them in one file is what stops the two forms drifting
 * apart as the catalog grows.
 */
new class extends Component
{
    public ?Product $product = null;

    public string $name = '';

    public string $sku = '';

    public string $barcode = '';

    public string $categoryId = '';

    public string $brandId = '';

    public string $unitId = '';

    public string $description = '';

    /** Major units, as typed — converted to minor units on save. */
    public string $costPrice = '0.00';

    public string $price = '0.00';

    public string $reorderPoint = '0';

    public bool $isActive = true;

    public function mount(?Product $product = null): void
    {
        if ($product?->exists) {
            $this->product = $product;
            $this->name = $product->name;
            $this->sku = $product->sku;
            $this->barcode = (string) $product->barcode;
            $this->categoryId = (string) $product->category_id;
            $this->brandId = (string) $product->brand_id;
            $this->unitId = (string) $product->unit_id;
            $this->description = (string) $product->description;
            $this->costPrice = number_format($product->cost_price_cents->toDecimal(), 2, '.', '');
            $this->price = number_format($product->price_cents->toDecimal(), 2, '.', '');
            $this->reorderPoint = (string) $product->reorder_point;
            $this->isActive = $product->is_active;

            return;
        }

        // A new product defaults to the only category and unit when there is
        // just one of each, which is the common case in a small store.
        $this->categoryId = (string) ($this->categories->count() === 1 ? $this->categories->first()->id : '');
        $this->unitId = (string) ($this->units->count() === 1 ? $this->units->first()->id : '');
    }

    /**
     * @return Collection<int, Category>
     */
    #[Computed]
    public function categories(): Collection
    {
        return Category::query()->active()->orderBy('name')->get();
    }

    /**
     * @return Collection<int, Brand>
     */
    #[Computed]
    public function brands(): Collection
    {
        return Brand::query()->active()->orderBy('name')->get();
    }

    /**
     * @return Collection<int, Unit>
     */
    #[Computed]
    public function units(): Collection
    {
        return Unit::query()->orderBy('name')->get();
    }

    #[Computed]
    public function isEditing(): bool
    {
        return $this->product !== null;
    }

    /**
     * The margin the entered cost and price imply, shown live so the person
     * pricing the product sees the consequence as they type.
     */
    #[Computed]
    public function previewMargin(): ?float
    {
        if (! is_numeric($this->price) || ! is_numeric($this->costPrice)) {
            return null;
        }

        return Money::fromDecimal($this->price)->marginOver(Money::fromDecimal($this->costPrice));
    }

    public function save(): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'sku' => [
                'required', 'string', 'max:64',
                Rule::unique('products', 'sku')->ignore($this->product?->id)->withoutTrashed(),
            ],
            'barcode' => [
                'nullable', 'string', 'max:64',
                Rule::unique('products', 'barcode')->ignore($this->product?->id)->withoutTrashed(),
            ],
            'categoryId' => ['required', Rule::exists('categories', 'id')->withoutTrashed()],
            'brandId' => ['nullable', Rule::exists('brands', 'id')->withoutTrashed()],
            'unitId' => ['required', Rule::exists('units', 'id')->withoutTrashed()],
            'description' => ['nullable', 'string', 'max:5000'],
            'costPrice' => ['required', 'numeric', 'min:0', 'max:99999999'],
            'price' => ['required', 'numeric', 'min:0', 'max:99999999'],
            'reorderPoint' => ['required', 'integer', 'min:0'],
            'isActive' => ['boolean'],
        ], attributes: [
            'categoryId' => __('category'),
            'brandId' => __('brand'),
            'unitId' => __('unit'),
            'costPrice' => __('cost price'),
            'reorderPoint' => __('reorder point'),
        ]);

        $attributes = [
            'name' => $validated['name'],
            'sku' => $validated['sku'],
            'barcode' => $validated['barcode'] !== '' ? $validated['barcode'] : null,
            'category_id' => (int) $validated['categoryId'],
            'brand_id' => $validated['brandId'] !== '' ? (int) $validated['brandId'] : null,
            'unit_id' => (int) $validated['unitId'],
            'description' => $validated['description'] !== '' ? $validated['description'] : null,
            'cost_price_cents' => Money::fromDecimal($validated['costPrice']),
            'price_cents' => Money::fromDecimal($validated['price']),
            'reorder_point' => (int) $validated['reorderPoint'],
            'is_active' => $validated['isActive'],
        ];

        if ($this->product !== null) {
            $this->product->update($attributes);

            Flux::toast(variant: 'success', text: __('Product updated.'));
        } else {
            Product::create($attributes + ['slug' => Product::uniqueSlugFor($validated['name'])]);

            Flux::toast(variant: 'success', text: __('Product created.'));
        }

        $this->redirectRoute('products.index', navigate: true);
    }
}; ?>

<div class="flex flex-col gap-6">
    @php($heading = $this->isEditing ? $product->name : __('New product'))

    <x-ui.page-header
        :title="$heading"
        :subtitle="__('Cost, price and how this product is counted.')"
        :breadcrumbs="[
            ['label' => __('Catalog')],
            ['label' => __('Products'), 'href' => route('products.index')],
            ['label' => $this->isEditing ? __('Edit') : __('New')],
        ]"
    />

    <form wire:submit="save" class="grid gap-6 lg:grid-cols-3">
        <div class="flex flex-col gap-6 lg:col-span-2">
            <x-ui.panel :heading="__('Details')">
                <div class="grid gap-5 sm:grid-cols-2">
                    <flux:input class="sm:col-span-2" wire:model="name" :label="__('Name')" required autofocus />

                    <flux:input
                        wire:model="sku"
                        :label="__('SKU')"
                        :description="__('Your internal code for this product.')"
                        required
                    />

                    <flux:input
                        wire:model="barcode"
                        :label="__('Barcode')"
                        :description="__('Optional. Scanned at the register.')"
                    />

                    <flux:textarea class="sm:col-span-2" wire:model="description" :label="__('Description')" rows="3" />
                </div>
            </x-ui.panel>

            <x-ui.panel :heading="__('Pricing')">
                <div class="grid gap-5 sm:grid-cols-3">
                    <flux:input
                        wire:model.live.debounce.500ms="costPrice"
                        :label="__('Cost price')"
                        type="number"
                        step="0.01"
                        min="0"
                        class="tabular-nums"
                        required
                    />

                    <flux:input
                        wire:model.live.debounce.500ms="price"
                        :label="__('Selling price')"
                        type="number"
                        step="0.01"
                        min="0"
                        class="tabular-nums"
                        required
                    />

                    <flux:field>
                        <flux:label>{{ __('Margin') }}</flux:label>

                        <div class="flex h-10 items-center tabular-nums">
                            @if ($this->previewMargin === null)
                                <span class="text-ink-faint">—</span>
                            @else
                                <span @class([
                                    'font-medium',
                                    'text-critical' => $this->previewMargin < 0,
                                    'text-caution' => $this->previewMargin >= 0 && $this->previewMargin < 15,
                                    'text-positive' => $this->previewMargin >= 15,
                                ])>
                                    {{ number_format($this->previewMargin, 1) }}%
                                </span>
                            @endif
                        </div>

                        <flux:description>{{ __('Of the selling price.') }}</flux:description>
                    </flux:field>
                </div>
            </x-ui.panel>
        </div>

        <div class="flex flex-col gap-6">
            <x-ui.panel :heading="__('Classification')">
                <div class="flex flex-col gap-5">
                    <flux:select wire:model="categoryId" :label="__('Category')" :placeholder="__('Choose a category')" required>
                        @foreach ($this->categories as $category)
                            <flux:select.option :value="$category->id" wire:key="cat-{{ $category->id }}">
                                {{ $category->name }}
                            </flux:select.option>
                        @endforeach
                    </flux:select>

                    {{-- No placeholder here: the empty option must stay selectable, so a
                         product that had a brand can have it cleared again. --}}
                    <flux:select wire:model="brandId" :label="__('Brand')">
                        <flux:select.option value="">{{ __('No brand') }}</flux:select.option>

                        @foreach ($this->brands as $brand)
                            <flux:select.option :value="$brand->id" wire:key="brand-{{ $brand->id }}">
                                {{ $brand->name }}
                            </flux:select.option>
                        @endforeach
                    </flux:select>

                    <flux:select wire:model="unitId" :label="__('Unit')" :placeholder="__('Choose a unit')" required>
                        @foreach ($this->units as $unit)
                            <flux:select.option :value="$unit->id" wire:key="unit-{{ $unit->id }}">
                                {{ $unit->label() }}
                            </flux:select.option>
                        @endforeach
                    </flux:select>
                </div>
            </x-ui.panel>

            <x-ui.panel :heading="__('Stock')">
                <div class="flex flex-col gap-5">
                    <flux:input
                        wire:model="reorderPoint"
                        :label="__('Reorder point')"
                        :description="__('Reported for reorder at or below this level.')"
                        type="number"
                        min="0"
                        class="tabular-nums"
                        required
                    />

                    <flux:switch
                        wire:model="isActive"
                        :label="__('Active')"
                        :description="__('Inactive products cannot be sold at the register.')"
                    />
                </div>
            </x-ui.panel>

            <div class="flex items-center justify-end gap-2">
                <flux:button variant="ghost" :href="route('products.index')" wire:navigate>
                    {{ __('Cancel') }}
                </flux:button>

                <flux:button type="submit" variant="primary">
                    {{ $this->isEditing ? __('Save changes') : __('Create product') }}
                </flux:button>
            </div>
        </div>
    </form>
</div>
