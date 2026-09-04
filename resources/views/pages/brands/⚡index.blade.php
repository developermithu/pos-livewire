<?php

use App\Models\Brand;
use Flux\Flux;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Brands')] class extends Component
{
    use WithPagination;

    #[Url(as: 'q', except: '')]
    public string $search = '';

    /** The brand currently open in the form modal; null while creating. */
    public ?Brand $editing = null;

    public string $name = '';

    public string $description = '';

    public bool $isActive = true;

    /**
     * @return LengthAwarePaginator<int, Brand>
     */
    #[Computed]
    public function brands(): LengthAwarePaginator
    {
        return Brand::query()
            ->when($this->search !== '', fn ($query) => $query->where('name', 'like', "%{$this->search}%"))
            ->withCount('products')
            ->orderBy('name')
            ->paginate(12);
    }

    /**
     * The empty state reads differently depending on whether the catalog is
     * genuinely empty or a filter simply matched nothing.
     */
    #[Computed]
    public function emptyHeading(): string
    {
        return $this->search !== ''
            ? __('No matching brands')
            : __('No brands yet');
    }

    #[Computed]
    public function emptyDescription(): string
    {
        return $this->search !== ''
            ? __('Nothing matches ":term".', ['term' => $this->search])
            : __('Brands record who makes the products you stock.');
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function createBrand(): void
    {
        $this->reset('editing', 'name', 'description');
        $this->isActive = true;
        $this->resetValidation();

        Flux::modal('brand-form')->show();
    }

    public function editBrand(Brand $brand): void
    {
        $this->editing = $brand;
        $this->name = $brand->name;
        $this->description = (string) $brand->description;
        $this->isActive = $brand->is_active;
        $this->resetValidation();

        Flux::modal('brand-form')->show();
    }

    public function save(): void
    {
        $validated = $this->validate([
            'name' => [
                'required', 'string', 'max:255',
                Rule::unique('brands', 'name')
                    ->ignore($this->editing?->id)
                    ->withoutTrashed(),
            ],
            'description' => ['nullable', 'string', 'max:2000'],
            'isActive' => ['boolean'],
        ]);

        $attributes = [
            'name' => $validated['name'],
            'description' => $validated['description'] !== '' ? $validated['description'] : null,
            'is_active' => $validated['isActive'],
        ];

        if ($this->editing !== null) {
            $this->editing->update($attributes);
        } else {
            Brand::create($attributes + ['slug' => Brand::uniqueSlugFor($validated['name'])]);

            $this->resetPage();
        }

        unset($this->brands);

        Flux::modal('brand-form')->close();
        Flux::toast(variant: 'success', text: __('Brand saved.'));
    }

    /**
     * Brands are soft-deleted, but one still holding products is refused.
     * A product's brand is nullable at the schema level, so the delete would
     * succeed — and every product under it would quietly render as unbranded,
     * because the relation excludes trashed rows. Refusing keeps that decision
     * with the person making it.
     */
    public function delete(Brand $brand): void
    {
        if ($brand->products()->exists()) {
            Flux::toast(
                variant: 'warning',
                text: __('Reassign the products under :name before deleting it.', ['name' => $brand->name]),
            );

            return;
        }

        $brand->delete();

        unset($this->brands);

        Flux::toast(variant: 'success', text: __('Brand deleted.'));
    }
}; ?>

<div class="flex flex-col gap-6">
    <x-ui.page-header
        :title="__('Brands')"
        :subtitle="__('The makers and labels behind the products you stock.')"
        :breadcrumbs="[
            ['label' => __('Catalog')],
            ['label' => __('Brands')],
        ]"
    >
        <x-slot:actions>
            <flux:button size="sm" variant="primary" icon="plus" wire:click="createBrand">
                {{ __('New brand') }}
            </flux:button>
        </x-slot:actions>
    </x-ui.page-header>

    <x-ui.panel :padded="false">
        <x-slot:actions>
            <flux:input
                size="sm"
                icon="magnifying-glass"
                wire:model.live.debounce.300ms="search"
                :placeholder="__('Search brands')"
                class="w-56"
            />
        </x-slot:actions>

        @if ($this->brands->isEmpty())
            <x-ui.empty-state icon="building-storefront" :heading="$this->emptyHeading" :description="$this->emptyDescription">
                @if ($search === '')
                    <flux:button size="sm" variant="primary" icon="plus" wire:click="createBrand">
                        {{ __('New brand') }}
                    </flux:button>
                @endif
            </x-ui.empty-state>
        @else
            <flux:table>
                <flux:table.columns>
                    <flux:table.column>{{ __('Name') }}</flux:table.column>
                    <flux:table.column>{{ __('Products') }}</flux:table.column>
                    <flux:table.column>{{ __('Status') }}</flux:table.column>
                    <flux:table.column />
                </flux:table.columns>

                <flux:table.rows>
                    @foreach ($this->brands as $brand)
                        @php($actionsLabel = __('Actions for :name', ['name' => $brand->name]))
                        @php($deletePrompt = __('Delete :name?', ['name' => $brand->name]))

                        <flux:table.row :key="$brand->id">
                            <flux:table.cell>
                                <div class="min-w-0">
                                    <p class="truncate font-medium text-ink">{{ $brand->name }}</p>

                                    @if (filled($brand->description))
                                        <p class="truncate text-xs text-ink-faint">{{ $brand->description }}</p>
                                    @endif
                                </div>
                            </flux:table.cell>

                            <flux:table.cell class="tabular-nums">
                                {{ $brand->products_count }}
                            </flux:table.cell>

                            <flux:table.cell>
                                <x-domain.active-badge :active="$brand->is_active" />
                            </flux:table.cell>

                            <flux:table.cell align="end">
                                <flux:dropdown position="bottom" align="end">
                                    <flux:button size="sm" variant="ghost" icon="ellipsis-horizontal" :aria-label="$actionsLabel" />

                                    <flux:menu>
                                        <flux:menu.item icon="pencil-square" wire:click="editBrand({{ $brand->id }})">
                                            {{ __('Edit') }}
                                        </flux:menu.item>

                                        <flux:menu.separator />

                                        <flux:menu.item
                                            variant="danger"
                                            icon="trash"
                                            wire:click="delete({{ $brand->id }})"
                                            :wire:confirm="$deletePrompt"
                                        >
                                            {{ __('Delete') }}
                                        </flux:menu.item>
                                    </flux:menu>
                                </flux:dropdown>
                            </flux:table.cell>
                        </flux:table.row>
                    @endforeach
                </flux:table.rows>
            </flux:table>

            @if ($this->brands->hasPages())
                <div class="border-t border-line px-5 py-3">
                    {{ $this->brands->links() }}
                </div>
            @endif
        @endif
    </x-ui.panel>

    <flux:modal name="brand-form" class="w-full max-w-md" :dismissible="false">
        <form wire:submit="save" class="flex flex-col gap-6">
            <div>
                <flux:heading size="lg">
                    {{ $editing ? __('Edit brand') : __('New brand') }}
                </flux:heading>

                <flux:subheading>
                    {{ __('Brands record who makes the products you stock.') }}
                </flux:subheading>
            </div>

            <flux:input wire:model="name" :label="__('Name')" required autofocus />

            <flux:textarea wire:model="description" :label="__('Description')" rows="3" />

            <flux:switch wire:model="isActive" :label="__('Active')" :description="__('Inactive brands stay in reports but cannot be assigned to new products.')" />

            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="ghost">{{ __('Cancel') }}</flux:button>
                </flux:modal.close>

                <flux:button type="submit" variant="primary">{{ __('Save') }}</flux:button>
            </div>
        </form>
    </flux:modal>
</div>
