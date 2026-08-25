<div>
    {{-- HEADER --}}
    <x-header title="{{ __('Manajemen Satuan') }}" subtitle="{{ __('Daftar satuan barang') }}" separator
        progress-indicator>
        <x-slot:actions>
            <span data-tour="unit-add">
                <x-button label="{{ __('Tambah Satuan') }}" icon="o-plus" class="btn-primary" wire:click="create" />
            </span>
        </x-slot:actions>
    </x-header>

    {{-- INLINE FILTER BAR --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6 items-end">
        <x-input placeholder="{{ __('Cari nama atau simbol') }}..." wire:model.live.debounce="search"
            icon="o-magnifying-glass" />

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
    <x-card class="bg-base-100 shadow-sm" data-tour="unit-table">
        <div class="overflow-x-auto">
            <table class="table table-zebra">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>{{ __('Nama') }}</th>
                        <th>{{ __('Simbol') }}</th>
                        <th>{{ __('Barang') }}</th>
                        <th class="text-right">{{ __('Aksi') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($units as $unit)
                        <tr wire:key="{{ $unit->id }}">
                            <th>{{ $loop->iteration + ($units->firstItem() - 1) }}</th>
                            <td>
                                <div class="font-bold">{{ $unit->nama }}</div>
                            </td>
                            <td><span class="text-gray-500">{{ $unit->simbol ?? '-' }}</span></td>
                            <td>
                                <span class="badge badge-ghost badge-sm">{{ $unit->items_count }}</span>
                            </td>
                            <td class="text-right">
                                <x-button icon="o-pencil-square" wire:click="edit({{ $unit->id }})"
                                    class="btn-sm btn-ghost text-blue-500" />
                                <x-button icon="o-trash" wire:click="confirmDelete({{ $unit->id }})"
                                    class="btn-sm btn-square btn-ghost text-red-500" />
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center py-10 text-gray-500">
                                {{ __('Tidak ada satuan yang cocok dengan filter.') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $units->links() }}</div>
    </x-card>

    {{-- MODAL FORM --}}
    <x-modal wire:model="modalOpen" :title="$editingId ? __('Edit Satuan') : __('Tambah Satuan')" separator>
        <x-form wire:submit="confirmSave">
            <x-input label="{{ __('Nama') }}" wire:model="nama" icon="o-scale" />
            <x-input label="{{ __('Simbol') }}" wire:model="simbol" icon="o-hashtag" />

            <x-slot:actions>
                <x-button label="{{ __('Batal') }}" @click="$wire.modalOpen = false" />
                <x-button label="{{ __('Simpan') }}" class="btn-primary" type="submit" spinner="save" />
            </x-slot:actions>
        </x-form>
    </x-modal>

    {{-- MODAL DELETE --}}
    <x-modal-confirm wire:model="deleteModalOpen" title="{{ __('Hapus Satuan?') }}"
        text="{{ __('Apakah Anda yakin ingin menghapus satuan ini?') }}" confirm-text="{{ __('Ya, Hapus') }}"
        method="delete" />

    {{-- MODAL KONFIRMASI GENERIK --}}
    <x-modal-confirm wire:model="confirmModalOpen" :title="$confirmTitle" :text="$confirmMessage"
        :confirm-text="$confirmButton" :icon="$confirmIcon" :danger="$confirmDanger" method="confirmProceed" />
</div>
