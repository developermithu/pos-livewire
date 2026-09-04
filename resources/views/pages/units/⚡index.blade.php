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
}; ?>

<div class="flex flex-col gap-6">
    <x-ui.page-header
        :title="__('Units')"
        :subtitle="__('How each product is counted and sold.')"
        :breadcrumbs="[
            ['label' => __('Inventory')],
            ['label' => __('Units')],
        ]"
    >
        <x-slot:actions>
            <flux:button size="sm" variant="primary" icon="plus" wire:click="createUnit">
                {{ __('New unit') }}
            </flux:button>
        </x-slot:actions>
    </x-ui.page-header>

    <x-ui.panel :padded="false">
        <x-slot:actions>
            <flux:input
                size="sm"
                icon="magnifying-glass"
                wire:model.live.debounce.300ms="search"
                :placeholder="__('Search units')"
                class="w-56"
            />
        </x-slot:actions>

        @if ($this->units->isEmpty())
            <x-ui.empty-state icon="scale" :heading="$this->emptyHeading" :description="$this->emptyDescription">
                @if ($search === '')
                    <flux:button size="sm" variant="primary" icon="plus" wire:click="createUnit">
                        {{ __('New unit') }}
                    </flux:button>
                @endif
            </x-ui.empty-state>
        @else
            <flux:table>
                <flux:table.columns>
                    <flux:table.column>{{ __('Name') }}</flux:table.column>
                    <flux:table.column>{{ __('Code') }}</flux:table.column>
                    <flux:table.column>{{ __('Quantities') }}</flux:table.column>
                    <flux:table.column>{{ __('Products') }}</flux:table.column>
                    <flux:table.column />
                </flux:table.columns>

                <flux:table.rows>
                    @foreach ($this->units as $unit)
                        @php($actionsLabel = __('Actions for :name', ['name' => $unit->name]))
                        @php($deletePrompt = __('Delete :name?', ['name' => $unit->name]))

                        <flux:table.row :key="$unit->id">
                            <flux:table.cell class="font-medium text-ink">{{ $unit->name }}</flux:table.cell>

                            <flux:table.cell class="tabular-nums text-ink-muted">{{ $unit->code }}</flux:table.cell>

                            <flux:table.cell>
                                <flux:badge size="sm" color="zinc" variant="outline">
                                    {{ $unit->allows_fractional ? __('Fractional') : __('Whole') }}
                                </flux:badge>
                            </flux:table.cell>

                            <flux:table.cell class="tabular-nums">{{ $unit->products_count }}</flux:table.cell>

                            <flux:table.cell align="end">
                                <flux:dropdown position="bottom" align="end">
                                    <flux:button size="sm" variant="ghost" icon="ellipsis-horizontal" :aria-label="$actionsLabel" />

                                    <flux:menu>
                                        <flux:menu.item icon="pencil-square" wire:click="editUnit({{ $unit->id }})">
                                            {{ __('Edit') }}
                                        </flux:menu.item>

                                        <flux:menu.separator />

                                        <flux:menu.item variant="danger" icon="trash" wire:click="delete({{ $unit->id }})" :wire:confirm="$deletePrompt">
                                            {{ __('Delete') }}
                                        </flux:menu.item>
                                    </flux:menu>
                                </flux:dropdown>
                            </flux:table.cell>
                        </flux:table.row>
                    @endforeach
                </flux:table.rows>
            </flux:table>

            @if ($this->units->hasPages())
                <div class="border-t border-line px-5 py-3">
                    {{ $this->units->links() }}
                </div>
            @endif
        @endif
    </x-ui.panel>

    <flux:modal name="unit-form" class="w-full max-w-md" :dismissible="false">
        <form wire:submit="save" class="flex flex-col gap-6">
            <div>
                <flux:heading size="lg">{{ $editing ? __('Edit unit') : __('New unit') }}</flux:heading>
                <flux:subheading>{{ __('Units are how a product is counted and sold.') }}</flux:subheading>
            </div>

            <flux:input wire:model="name" :label="__('Name')" :placeholder="__('Kilogram')" required autofocus />

            <flux:input wire:model="code" :label="__('Code')" :placeholder="__('kg')" :description="__('The abbreviation shown beside a quantity.')" required />

            <flux:switch
                wire:model="allowsFractional"
                :label="__('Allow fractional quantities')"
                :description="__('Weighed goods sell as 1.25 kg; discrete goods must not.')"
            />

            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="ghost">{{ __('Cancel') }}</flux:button>
                </flux:modal.close>

                <flux:button type="submit" variant="primary">{{ __('Save') }}</flux:button>
            </div>
        </form>
    </flux:modal>
</div>
