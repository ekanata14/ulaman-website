<div>
    {{-- HEADER --}}
    <x-header title="{{ __('Manajemen Supplier') }}" subtitle="{{ __('Daftar supplier') }}" separator
        progress-indicator>
        <x-slot:actions>
            <span data-tour="supplier-add">
                <x-button label="{{ __('Tambah Supplier') }}" icon="o-plus" class="btn-primary" wire:click="create" />
            </span>
        </x-slot:actions>
    </x-header>

    {{-- INLINE FILTER BAR --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6 items-end">
        <x-input placeholder="{{ __('Cari nama, PIC, atau telepon') }}..." wire:model.live.debounce="search"
            icon="o-magnifying-glass" />

        <x-select wire:model.live="filterStatus" :options="[
            ['id' => '', 'name' => __('Semua Status')],
            ['id' => 'active', 'name' => __('Aktif')],
            ['id' => 'inactive', 'name' => __('Nonaktif')],
        ]" icon="o-check-badge" />

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
    <x-card class="bg-base-100 shadow-sm" data-tour="supplier-table">
        <div class="overflow-x-auto">
            <table class="table table-zebra">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>{{ __('Nama') }}</th>
                        <th>{{ __('PIC') }}</th>
                        <th>{{ __('Telepon') }}</th>
                        <th>{{ __('Transaksi') }}</th>
                        <th>{{ __('Status') }}</th>
                        <th class="text-right">{{ __('Aksi') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($suppliers as $supplier)
                        <tr wire:key="{{ $supplier->id }}">
                            <th>{{ $loop->iteration + ($suppliers->firstItem() - 1) }}</th>
                            <td>
                                <div class="font-bold">{{ $supplier->nama }}</div>
                                @if ($supplier->alamat)
                                    <div class="text-xs text-gray-500 max-w-[220px] truncate">{{ $supplier->alamat }}
                                    </div>
                                @endif
                            </td>
                            <td><span class="text-gray-500">{{ $supplier->pic ?? '-' }}</span></td>
                            <td><span class="text-gray-500">{{ $supplier->telepon ?? '-' }}</span></td>
                            <td>
                                <span class="badge badge-ghost badge-sm">{{ $supplier->purchases_count }}</span>
                            </td>
                            <td>
                                @if ($supplier->is_active)
                                    <span class="badge badge-success text-white badge-sm">{{ __('Aktif') }}</span>
                                @else
                                    <span class="badge badge-ghost badge-sm">{{ __('Nonaktif') }}</span>
                                @endif
                            </td>
                            <td class="text-right">
                                <x-button icon="{{ $supplier->is_active ? 'o-eye-slash' : 'o-eye' }}"
                                    wire:click="confirmToggleActive({{ $supplier->id }})"
                                    class="btn-sm btn-ghost text-amber-500"
                                    tooltip="{{ $supplier->is_active ? __('Nonaktifkan') : __('Aktifkan') }}" />
                                <x-button icon="o-pencil-square" wire:click="edit({{ $supplier->id }})"
                                    class="btn-sm btn-ghost text-blue-500" />
                                <x-button icon="o-trash" wire:click="confirmDelete({{ $supplier->id }})"
                                    class="btn-sm btn-square btn-ghost text-red-500" />
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-10 text-gray-500">
                                {{ __('Tidak ada supplier yang cocok dengan filter.') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $suppliers->links() }}</div>
    </x-card>

    {{-- MODAL FORM --}}
    <x-modal wire:model="modalOpen" :title="$editingId ? __('Edit Supplier') : __('Tambah Supplier')" separator>
        <x-form wire:submit="confirmSave">
            <x-input label="{{ __('Nama') }}" wire:model="nama" icon="o-building-storefront" />
            <x-input label="{{ __('PIC') }}" wire:model="pic" icon="o-user" />
            <x-input label="{{ __('Telepon') }}" wire:model="telepon" icon="o-phone" />
            <x-textarea label="{{ __('Alamat') }}" wire:model="alamat" rows="2" />
            <x-textarea label="{{ __('Catatan') }}" wire:model="catatan" rows="2" />
            <x-toggle label="{{ __('Aktif') }}" wire:model="is_active" />

            <x-slot:actions>
                <x-button label="{{ __('Batal') }}" @click="$wire.modalOpen = false" />
                <x-button label="{{ __('Simpan') }}" class="btn-primary" type="submit" spinner="save" />
            </x-slot:actions>
        </x-form>
    </x-modal>

    {{-- MODAL DELETE --}}
    <x-modal-confirm wire:model="deleteModalOpen" title="{{ __('Hapus Supplier?') }}"
        text="{{ __('Apakah Anda yakin ingin menghapus supplier ini?') }}" confirm-text="{{ __('Ya, Hapus') }}"
        method="delete" />

    {{-- MODAL KONFIRMASI GENERIK --}}
    <x-modal-confirm wire:model="confirmModalOpen" :title="$confirmTitle" :text="$confirmMessage"
        :confirm-text="$confirmButton" :icon="$confirmIcon" :danger="$confirmDanger" method="confirmProceed" />
</div>
