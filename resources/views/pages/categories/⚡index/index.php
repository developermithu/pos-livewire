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
}; 