<?php

use App\Models\Category;
use Flux\Flux;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Categories')] class extends Component
{
    use WithPagination;

    #[Url(as: 'q', except: '')]
    public string $search = '';

    /** The category currently open in the form modal; null while creating. */
    public ?Category $editing = null;

    public string $name = '';

    public string $description = '';

    public bool $isActive = true;

    /**
     * @return LengthAwarePaginator<int, Category>
     */
    #[Computed]
    public function categories(): LengthAwarePaginator
    {
        return Category::query()
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
            ? __('No matching categories')
            : __('No categories yet');
    }

    #[Computed]
    public function emptyDescription(): string
    {
        return $this->search !== ''
            ? __('Nothing matches ":term".', ['term' => $this->search])
            : __('Categories group products for browsing and reporting.');
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function createCategory(): void
    {
        $this->reset('editing', 'name', 'description');
        $this->isActive = true;
        $this->resetValidation();

        Flux::modal('category-form')->show();
    }

    public function editCategory(Category $category): void
    {
        $this->editing = $category;
        $this->name = $category->name;
        $this->description = (string) $category->description;
        $this->isActive = $category->is_active;
        $this->resetValidation();

        Flux::modal('category-form')->show();
    }

    public function save(): void
    {
        $validated = $this->validate([
            'name' => [
                'required', 'string', 'max:255',
                Rule::unique('categories', 'name')
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
            Category::create($attributes + ['slug' => Category::uniqueSlugFor($validated['name'])]);

            $this->resetPage();
        }

        unset($this->categories);

        Flux::modal('category-form')->close();
        Flux::toast(variant: 'success', text: __('Category saved.'));
    }

    /**
     * Categories are soft-deleted, but one still holding products is refused:
     * products carry a required category, so removing it would leave the
     * catalog pointing at nothing.
     */
    public function delete(Category $category): void
    {
        if ($category->products()->exists()) {
            Flux::toast(
                variant: 'warning',
                text: __('Move the products in :name to another category first.', ['name' => $category->name]),
            );

            return;
        }

        $category->delete();

        unset($this->categories);

        Flux::toast(variant: 'success', text: __('Category deleted.'));
    }
}; ?>

<div class="flex flex-col gap-6">
    <x-ui.page-header
        :title="__('Categories')"
        :subtitle="__('How the catalog is grouped for browsing and reporting.')"
        :breadcrumbs="[
            ['label' => __('Catalog')],
            ['label' => __('Categories')],
        ]"
    >
        <x-slot:actions>
            <flux:button size="sm" variant="primary" icon="plus" wire:click="createCategory">
                {{ __('New category') }}
            </flux:button>
        </x-slot:actions>
    </x-ui.page-header>

    <x-ui.panel :padded="false">
        <x-slot:actions>
            <flux:input
                size="sm"
                icon="magnifying-glass"
                wire:model.live.debounce.300ms="search"
                :placeholder="__('Search categories')"
                class="w-56"
            />
        </x-slot:actions>

        @if ($this->categories->isEmpty())
            <x-ui.empty-state icon="squares-2x2" :heading="$this->emptyHeading" :description="$this->emptyDescription">
                @if ($search === '')
                    <flux:button size="sm" variant="primary" icon="plus" wire:click="createCategory">
                        {{ __('New category') }}
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
                    @foreach ($this->categories as $category)
                        @php($actionsLabel = __('Actions for :name', ['name' => $category->name]))
                        @php($deletePrompt = __('Delete :name?', ['name' => $category->name]))

                        <flux:table.row :key="$category->id">
                            <flux:table.cell>
                                <div class="min-w-0">
                                    <p class="truncate font-medium text-ink">{{ $category->name }}</p>

                                    @if (filled($category->description))
                                        <p class="truncate text-xs text-ink-faint">{{ $category->description }}</p>
                                    @endif
                                </div>
                            </flux:table.cell>

                            <flux:table.cell class="tabular-nums">
                                {{ $category->products_count }}
                            </flux:table.cell>

                            <flux:table.cell>
                                <x-domain.active-badge :active="$category->is_active" />
                            </flux:table.cell>

                            <flux:table.cell align="end">
                                <flux:dropdown position="bottom" align="end">
                                    <flux:button size="sm" variant="ghost" icon="ellipsis-horizontal" :aria-label="$actionsLabel" />

                                    <flux:menu>
                                        <flux:menu.item icon="pencil-square" wire:click="editCategory({{ $category->id }})">
                                            {{ __('Edit') }}
                                        </flux:menu.item>

                                        <flux:menu.separator />

                                        <flux:menu.item
                                            variant="danger"
                                            icon="trash"
                                            wire:click="delete({{ $category->id }})"
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

            @if ($this->categories->hasPages())
                <div class="border-t border-line px-5 py-3">
                    {{ $this->categories->links() }}
                </div>
            @endif
        @endif
    </x-ui.panel>

    <flux:modal name="category-form" class="w-full max-w-md" :dismissible="false">
        <form wire:submit="save" class="flex flex-col gap-6">
            <div>
                <flux:heading size="lg">
                    {{ $editing ? __('Edit category') : __('New category') }}
                </flux:heading>

                <flux:subheading>
                    {{ __('Categories group products for browsing and reporting.') }}
                </flux:subheading>
            </div>

            <flux:input wire:model="name" :label="__('Name')" required autofocus />

            <flux:textarea wire:model="description" :label="__('Description')" rows="3" />

            <flux:switch wire:model="isActive" :label="__('Active')" :description="__('Inactive categories stay in reports but cannot be assigned to new products.')" />

            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="ghost">{{ __('Cancel') }}</flux:button>
                </flux:modal.close>

                <flux:button type="submit" variant="primary">{{ __('Save') }}</flux:button>
            </div>
        </form>
    </flux:modal>
</div>
