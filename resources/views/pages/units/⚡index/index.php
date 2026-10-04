<?php

use App\Models\Unit;
use Flux\Flux;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Units')] class extends Component
{
    use WithPagination;

    #[Url(as: 'q', except: '')]
    public string $search = '';

    /** The unit currently open in the form modal; null while creating. */
    public ?Unit $editing = null;

    public string $name = '';

    public string $code = '';

    public bool $allowsFractional = false;

    /**
     * @return LengthAwarePaginator<int, Unit>
     */
    #[Computed]
    public function units(): LengthAwarePaginator
    {
        return Unit::query()
            ->when($this->search !== '', function ($query): void {
                $query->where(function ($query): void {
                    $query->where('name', 'like', "%{$this->search}%")
                        ->orWhere('code', 'like', "%{$this->search}%");
                });
            })
            ->withCount('products')
            ->orderBy('name')
            ->paginate(12);
    }

    #[Computed]
    public function emptyHeading(): string
    {
        return $this->search !== ''
            ? __('No matching units')
            : __('No units yet');
    }

    #[Computed]
    public function emptyDescription(): string
    {
        return $this->search !== ''
            ? __('Nothing matches ":term".', ['term' => $this->search])
            : __('Units are how a product is counted and sold — pieces, kilograms, litres.');
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function createUnit(): void
    {
        $this->reset('editing', 'name', 'code', 'allowsFractional');
        $this->resetValidation();

        Flux::modal('unit-form')->show();
    }

    public function editUnit(Unit $unit): void
    {
        $this->editing = $unit;
        $this->name = $unit->name;
        $this->code = $unit->code;
        $this->allowsFractional = $unit->allows_fractional;
        $this->resetValidation();

        Flux::modal('unit-form')->show();
    }

    public function save(): void
    {
        $validated = $this->validate([
            'name' => [
                'required', 'string', 'max:255',
                Rule::unique('units', 'name')->ignore($this->editing?->id)->withoutTrashed(),
            ],
            'code' => [
                'required', 'string', 'max:12',
                Rule::unique('units', 'code')->ignore($this->editing?->id)->withoutTrashed(),
            ],
            'allowsFractional' => ['boolean'],
        ]);

        $attributes = [
            'name' => $validated['name'],
            'code' => $validated['code'],
            'allows_fractional' => $validated['allowsFractional'],
        ];

        if ($this->editing !== null) {
            $this->editing->update($attributes);
        } else {
            Unit::create($attributes);

            $this->resetPage();
        }

        unset($this->units);

        Flux::modal('unit-form')->close();
        Flux::toast(variant: 'success', text: __('Unit saved.'));
    }

    /**
     * A unit in use is refused: every product is counted in one, so removing it
     * would leave quantities without a meaning.
     */
    public function delete(Unit $unit): void
    {
        if ($unit->products()->exists()) {
            Flux::toast(
                variant: 'warning',
                text: __('Products are still measured in :name.', ['name' => $unit->name]),
            );

            return;
        }

        $unit->delete();

        unset($this->units);

        Flux::toast(variant: 'success', text: __('Unit deleted.'));
    }
}; 