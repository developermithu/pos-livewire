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
}; 