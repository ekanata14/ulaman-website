<div>
    {{-- HEADER --}}
    <x-header title="{{ __('Manajemen Barang') }}" subtitle="{{ __('Daftar barang / item') }}" separator
        progress-indicator>
        <x-slot:actions>
            <span data-tour="item-add">
                <x-button label="{{ __('Tambah Barang') }}" icon="o-plus" class="btn-primary" wire:click="create" />
            </span>
        </x-slot:actions>
    </x-header>

    {{-- INLINE FILTER BAR --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4 mb-6 items-end">
        <x-input placeholder="{{ __('Cari nama barang') }}..." wire:model.live.debounce="search"
            icon="o-magnifying-glass" />

        <x-select wire:model.live="filterUnit" :options="array_merge([['id' => '', 'name' => __('Semua Satuan')]], $units)"
            icon="o-scale" />

        <x-select wire:model.live="sortBy" :options="[
            ['id' => 'latest', 'name' => __('Terbaru')],
            ['id' => 'oldest', 'name' => __('Terlama')],
            ['id' => 'name_asc', 'name' => __('Nama (A-Z)')],
            ['id' => 'name_desc', 'name' => __('Nama (Z-A)')],
        ]" icon="o-arrows-up-down" />

        <div>
            <x-button label="{{ __('Bersihkan') }}" wire:click="clearFilters" icon="o-x-mark"
                class="btn-ghost w-full lg:w-auto text-gray-500" />
        </div>
    </div>

    {{-- CARD TABEL --}}
    <x-card class="bg-base-100 shadow-sm" data-tour="item-table">
        <div class="overflow-x-auto">
            <table class="table table-zebra">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>{{ __('Nama') }}</th>
                        <th>{{ __('Satuan') }}</th>
                        <th>{{ __('Harga Terakhir') }}</th>
                        <th>{{ __('Supplier Terakhir') }}</th>
                        <th class="text-right">{{ __('Aksi') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($items as $item)
                        <tr wire:key="{{ $item->id }}">
                            <th>{{ $loop->iteration + ($items->firstItem() - 1) }}</th>
                            <td>
                                <a href="{{ route('admin.items.show', $item) }}" wire:navigate
                                    class="font-bold hover:underline">{{ $item->nama }}</a>
                            </td>
                            <td><span class="text-gray-500">{{ $item->unit?->nama ?? '-' }}</span></td>
                            <td>
                                <span class="text-gray-500">
                                    {{ $item->harga_terakhir ? 'Rp ' . number_format((float) $item->harga_terakhir, 0, ',', '.') : '-' }}
                                </span>
                            </td>
                            <td><span class="text-gray-500">{{ $item->supplierTerakhir?->nama ?? '-' }}</span></td>
                            <td class="text-right">
                                <x-button icon="o-document-magnifying-glass"
                                    link="{{ route('admin.items.show', $item) }}"
                                    class="btn-sm btn-ghost text-gray-500" tooltip="{{ __('Detail') }}" />
                                <x-button icon="o-pencil-square" wire:click="edit({{ $item->id }})"
                                    class="btn-sm btn-ghost text-blue-500" />
                                <x-button icon="o-trash" wire:click="confirmDelete({{ $item->id }})"
                                    class="btn-sm btn-square btn-ghost text-red-500" />
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-10 text-gray-500">
                                {{ __('Tidak ada barang yang cocok dengan filter.') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $items->links() }}</div>
    </x-card>

    {{-- MODAL FORM --}}
    <x-modal wire:model="modalOpen" :title="$editingId ? __('Edit Barang') : __('Tambah Barang')" separator>
        <x-form wire:submit="confirmSave">
            <x-input label="{{ __('Nama') }}" wire:model="nama" icon="o-cube" required />

            <x-select label="{{ __('Satuan') }}" wire:model="unit_id" :options="$units"
                placeholder="{{ __('Pilih satuan') }}" icon="o-scale" />

            <x-slot:actions>
                <x-button label="{{ __('Batal') }}" @click="$wire.modalOpen = false" />
                <x-button label="{{ __('Simpan') }}" class="btn-primary" type="submit" spinner="save" />
            </x-slot:actions>
        </x-form>
    </x-modal>

    {{-- MODAL DELETE --}}
    <x-modal-confirm wire:model="deleteModalOpen" title="{{ __('Hapus Barang?') }}"
        text="{{ __('Apakah Anda yakin ingin menghapus barang ini?') }}" confirm-text="{{ __('Ya, Hapus') }}"
        method="delete" />

    {{-- MODAL KONFIRMASI GENERIK --}}
    <x-modal-confirm wire:model="confirmModalOpen" :title="$confirmTitle" :text="$confirmMessage"
        :confirm-text="$confirmButton" :icon="$confirmIcon" :danger="$confirmDanger" method="confirmProceed" />
</div>
