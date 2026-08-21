<div>
    {{-- HEADER --}}
    <x-header title="{{ __('Purchase Notes') }}" subtitle="{{ __('Recorded purchase notes') }}" separator
        progress-indicator>
        <x-slot:actions>
            <x-dropdown label="{{ __('Export') }}" icon="o-arrow-down-tray" class="btn-ghost">
                <x-menu-item title="Excel (.xlsx)" icon="o-table-cells" wire:click="exportExcel" />
                <x-menu-item title="CSV" icon="o-document-text" wire:click="exportCsv" />
                <x-menu-item title="PDF" icon="o-document" wire:click="exportPdf" />
            </x-dropdown>
            <x-button label="{{ __('Add Note') }}" icon="o-plus" class="btn-primary"
                link="{{ route('admin.purchases.create') }}" />
        </x-slot:actions>
    </x-header>

    {{-- FILTER BAR --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-6 gap-4 mb-6 items-end">
        <x-input placeholder="{{ __('Code / note no.') }}..." wire:model.live.debounce="search"
            icon="o-magnifying-glass" />
        <x-input type="date" label="{{ __('From') }}" wire:model.live="dari" />
        <x-input type="date" label="{{ __('To') }}" wire:model.live="sampai" />
        <x-select placeholder="{{ __('All Suppliers') }}" wire:model.live="supplierId" :options="$suppliers"
            icon="o-building-storefront" />
        <x-select placeholder="{{ __('All Status') }}" wire:model.live="status" :options="$statusOptions"
            icon="o-flag" />
        <x-button label="{{ __('Clear') }}" wire:click="clearFilters" icon="o-x-mark"
            class="btn-ghost text-gray-500" />
    </div>

    {{-- TABLE --}}
    <x-card class="bg-base-100 shadow-sm">
        <div class="overflow-x-auto">
            <table class="table table-zebra">
                <thead>
                    <tr>
                        <th>{{ __('Code') }}</th>
                        <th>{{ __('Date') }}</th>
                        <th>{{ __('Supplier') }}</th>
                        <th class="text-center">{{ __('Items') }}</th>
                        <th class="text-right">{{ __('Grand Total') }}</th>
                        <th>{{ __('Status') }}</th>
                        <th class="text-right">{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($purchases as $purchase)
                        <tr wire:key="purchase-{{ $purchase->id }}">
                            <td class="font-mono font-semibold">{{ $purchase->kode }}</td>
                            <td>{{ $purchase->tanggal->format('d M Y') }}</td>
                            <td>{{ $purchase->supplier?->nama ?? __('Lain-lain') }}</td>
                            <td class="text-center">{{ $purchase->items_count }}</td>
                            <td class="text-right font-semibold">{{ \App\Support\Money::format($purchase->grand_total) }}</td>
                            <td>
                                <span
                                    class="badge badge-sm {{ $purchase->status === \App\Enums\PurchaseStatus::FINAL ? 'badge-success' : 'badge-ghost' }} text-white">
                                    {{ $purchase->status->label() }}
                                </span>
                            </td>
                            <td class="text-right whitespace-nowrap">
                                <x-button icon="o-pencil-square" link="{{ route('admin.purchases.edit', $purchase->id) }}"
                                    class="btn-sm btn-ghost text-blue-500" tooltip="{{ __('Edit') }}" />
                                <x-button icon="o-document-duplicate" wire:click="duplicate({{ $purchase->id }})"
                                    class="btn-sm btn-ghost text-gray-500" tooltip="{{ __('Duplicate') }}"
                                    spinner="duplicate({{ $purchase->id }})" />
                                <x-button icon="o-trash" wire:click="confirmDelete({{ $purchase->id }})"
                                    class="btn-sm btn-ghost text-red-500" tooltip="{{ __('Delete') }}" />
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-10 text-gray-500">
                                {{ __('No purchase notes found.') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $purchases->links() }}</div>
    </x-card>

    {{-- DELETE CONFIRM --}}
    <x-modal-confirm wire:model="deleteModalOpen" title="{{ __('Delete Purchase Note?') }}"
        text="{{ __('This will remove the note and all its items. This action cannot be undone.') }}"
        confirm-text="{{ __('Yes, Delete') }}" method="delete" />
</div>
