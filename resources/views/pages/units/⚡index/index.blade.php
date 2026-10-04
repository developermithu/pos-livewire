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