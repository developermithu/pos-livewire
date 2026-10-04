<?php

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use Flux\Flux;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Products')] class extends Component
{
    use WithPagination;

    #[Url(as: 'q', except: '')]
    public string $search = '';

    #[Url(as: 'category', except: '')]
    public string $categoryId = '';

    #[Url(as: 'brand', except: '')]
    public string $brandId = '';

    #[Url(as: 'status', except: 'all')]
    public string $status = 'all';

    /**
     * @return LengthAwarePaginator<int, Product>
     */
    #[Computed]
    public function products(): LengthAwarePaginator
    {
        return Product::query()
            ->search($this->search)
            ->when($this->categoryId !== '', fn ($query) => $query->where('category_id', $this->categoryId))
            ->when($this->brandId !== '', fn ($query) => $query->where('brand_id', $this->brandId))
            ->when($this->status !== 'all', fn ($query) => $query->where('is_active', $this->status === 'active'))
            // The table shows each product's category, brand and unit, so they
            // are eager-loaded: without this the page issues three queries per
            // row instead of three in total.
            ->with(['category', 'brand', 'unit'])
            ->orderBy('name')
            ->paginate(15);
    }

    /**
     * @return Collection<int, Category>
     */
    #[Computed]
    public function categories(): Collection
    {
        return Category::query()->orderBy('name')->get();
    }

    /**
     * @return Collection<int, Brand>
     */
    #[Computed]
    public function brands(): Collection
    {
        return Brand::query()->orderBy('name')->get();
    }

    public function hasFilters(): bool
    {
        return $this->search !== ''
            || $this->categoryId !== ''
            || $this->brandId !== ''
            || $this->status !== 'all';
    }

    public function clearFilters(): void
    {
        $this->reset('search', 'categoryId', 'brandId', 'status');
        $this->resetPage();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedCategoryId(): void
    {
        $this->resetPage();
    }

    public function updatedBrandId(): void
    {
        $this->resetPage();
    }

    public function updatedStatus(): void
    {
        $this->resetPage();
    }

    /**
     * Soft-delete, so that sales already recorded against this product can
     * still resolve its name and price.
     */
    public function delete(Product $product): void
    {
        $product->delete();

        unset($this->products);

        Flux::toast(variant: 'success', text: __('Product deleted.'));
    }
}; ?>

<div class="flex flex-col gap-6">
    <x-ui.page-header
        :title="__('Products')"
        :subtitle="__('Everything you sell, with its cost, price and margin.')"
        :breadcrumbs="[
            ['label' => __('Catalog')],
            ['label' => __('Products')],
        ]"
    >
        <x-slot:actions>
            <flux:button size="sm" variant="primary" icon="plus" :href="route('products.create')" wire:navigate>
                {{ __('New product') }}
            </flux:button>
        </x-slot:actions>
    </x-ui.page-header>

    <x-ui.panel :padded="false">
        <x-slot:actions>
            <div class="flex flex-wrap items-center gap-2">
                <flux:input
                    size="sm"
                    icon="magnifying-glass"
                    wire:model.live.debounce.300ms="search"
                    :placeholder="__('Name, SKU or barcode')"
                    class="w-56"
                />

                <flux:select size="sm" wire:model.live="categoryId" class="w-40">
                    <flux:select.option value="">{{ __('All categories') }}</flux:select.option>

                    @foreach ($this->categories as $category)
                        <flux:select.option :value="$category->id" wire:key="cat-{{ $category->id }}">
                            {{ $category->name }}
                        </flux:select.option>
                    @endforeach
                </flux:select>

                <flux:select size="sm" wire:model.live="brandId" class="w-40">
                    <flux:select.option value="">{{ __('All brands') }}</flux:select.option>

                    @foreach ($this->brands as $brand)
                        <flux:select.option :value="$brand->id" wire:key="brand-{{ $brand->id }}">
                            {{ $brand->name }}
                        </flux:select.option>
                    @endforeach
                </flux:select>

                <flux:select size="sm" wire:model.live="status" class="w-32">
                    <flux:select.option value="all">{{ __('All status') }}</flux:select.option>
                    <flux:select.option value="active">{{ __('Active') }}</flux:select.option>
                    <flux:select.option value="inactive">{{ __('Inactive') }}</flux:select.option>
                </flux:select>

                @if ($this->hasFilters())
                    <flux:button size="sm" variant="subtle" icon="x-mark" wire:click="clearFilters">
                        {{ __('Clear') }}
                    </flux:button>
                @endif
            </div>
        </x-slot:actions>

        @if ($this->products->isEmpty())
            @php($emptyHeading = $this->hasFilters() ? __('No matching products') : __('No products yet'))
            @php($emptyDescription = $this->hasFilters()
                ? __('No product matches the current search and filters.')
                : __('Add the first product and it becomes sellable at the register.'))

            <x-ui.empty-state icon="tag" :heading="$emptyHeading" :description="$emptyDescription">
                @if ($this->hasFilters())
                    <flux:button size="sm" variant="subtle" icon="x-mark" wire:click="clearFilters">
                        {{ __('Clear filters') }}
                    </flux:button>
                @else
                    <flux:button size="sm" variant="primary" icon="plus" :href="route('products.create')" wire:navigate>
                        {{ __('New product') }}
                    </flux:button>
                @endif
            </x-ui.empty-state>
        @else
            <flux:table>
                <flux:table.columns>
                    <flux:table.column>{{ __('Product') }}</flux:table.column>
                    <flux:table.column>{{ __('Category') }}</flux:table.column>
                    <flux:table.column>{{ __('Brand') }}</flux:table.column>
                    <flux:table.column align="end">{{ __('Cost') }}</flux:table.column>
                    <flux:table.column align="end">{{ __('Price') }}</flux:table.column>
                    <flux:table.column align="end">{{ __('Margin') }}</flux:table.column>
                    <flux:table.column>{{ __('Status') }}</flux:table.column>
                    <flux:table.column />
                </flux:table.columns>

                <flux:table.rows>
                    @foreach ($this->products as $product)
                        @php($actionsLabel = __('Actions for :name', ['name' => $product->name]))
                        @php($deletePrompt = __('Delete :name?', ['name' => $product->name]))
                        @php($margin = $product->margin())

                        <flux:table.row :key="$product->id">
                            <flux:table.cell>
                                <div class="min-w-0">
                                    <a
                                        href="{{ route('products.edit', $product) }}"
                                        wire:navigate
                                        class="truncate font-medium text-ink hover:underline"
                                    >
                                        {{ $product->name }}
                                    </a>

                                    <p class="truncate text-xs tabular-nums text-ink-faint">
                                        {{ $product->sku }} · {{ $product->unit->code }}
                                    </p>
                                </div>
                            </flux:table.cell>

                            <flux:table.cell class="text-ink-muted">{{ $product->category->name }}</flux:table.cell>

                            <flux:table.cell class="text-ink-muted">
                                {{ $product->brand?->name ?? '—' }}
                            </flux:table.cell>

                            <flux:table.cell align="end" class="tabular-nums text-ink-muted">
                                {{ $product->cost_price_cents->format() }}
                            </flux:table.cell>

                            <flux:table.cell align="end" class="tabular-nums font-medium text-ink">
                                {{ $product->price_cents->format() }}
                            </flux:table.cell>

                            <flux:table.cell align="end" class="tabular-nums">
                                @if ($margin === null)
                                    <span class="text-ink-faint">—</span>
                                @else
                                    <span @class([
                                        'text-critical' => $margin < 0,
                                        'text-caution' => $margin >= 0 && $margin < 15,
                                        'text-ink-muted' => $margin >= 15,
                                    ])>
                                        {{ number_format($margin, 1) }}%
                                    </span>
                                @endif
                            </flux:table.cell>

                            <flux:table.cell>
                                <x-domain.active-badge :active="$product->is_active" />
                            </flux:table.cell>

                            <flux:table.cell align="end">
                                <flux:dropdown position="bottom" align="end">
                                    <flux:button size="sm" variant="ghost" icon="ellipsis-horizontal" :aria-label="$actionsLabel" />

                                    <flux:menu>
                                        <flux:menu.item icon="pencil-square" :href="route('products.edit', $product)" wire:navigate>
                                            {{ __('Edit') }}
                                        </flux:menu.item>

                                        <flux:menu.separator />

                                        <flux:menu.item variant="danger" icon="trash" wire:click="delete({{ $product->id }})" :wire:confirm="$deletePrompt">
                                            {{ __('Delete') }}
                                        </flux:menu.item>
                                    </flux:menu>
                                </flux:dropdown>
                            </flux:table.cell>
                        </flux:table.row>
                    @endforeach
                </flux:table.rows>
            </flux:table>

            @if ($this->products->hasPages())
                <div class="border-t border-line px-5 py-3">
                    {{ $this->products->links() }}
                </div>
            @endif
        @endif
    </x-ui.panel>
</div>
