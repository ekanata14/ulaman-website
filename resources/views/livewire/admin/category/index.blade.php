<div>
    {{-- HEADER --}}
    <x-header title="{{ __('Manajemen Kategori') }}" subtitle="{{ __('Daftar kategori barang') }}" separator
        progress-indicator>
        <x-slot:actions>
            <span data-tour="category-add">
                <x-button label="{{ __('Tambah Kategori') }}" icon="o-plus" class="btn-primary" wire:click="create" />
            </span>
        </x-slot:actions>
    </x-header>

    {{-- INLINE FILTER BAR --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6 items-end">
        <x-input placeholder="{{ __('Cari nama kategori') }}..." wire:model.live.debounce="search"
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
    <x-card class="bg-base-100 shadow-sm" data-tour="category-table">
        <div class="overflow-x-auto">
            <table class="table table-zebra">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>{{ __('Nama') }}</th>
                        <th>{{ __('Warna') }}</th>
                        <th>{{ __('Barang') }}</th>
                        <th>{{ __('Nota') }}</th>
                        <th class="text-right">{{ __('Aksi') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($categories as $category)
                        <tr wire:key="{{ $category->id }}">
                            <th>{{ $loop->iteration + ($categories->firstItem() - 1) }}</th>
                            <td>
                                <div class="font-bold">{{ $category->nama }}</div>
                            </td>
                            <td>
                                @if ($category->warna)
                                    <div class="flex items-center gap-2">
                                        <span class="inline-block w-4 h-4 rounded-full border border-gray-300"
                                            style="background-color: {{ $category->warna }}"></span>
                                        <span class="text-xs text-gray-500">{{ $category->warna }}</span>
                                    </div>
                                @else
                                    <span class="text-gray-500">-</span>
                                @endif
                            </td>
                            <td><span class="badge badge-ghost badge-sm">{{ $category->items_count }}</span></td>
                            <td><span class="badge badge-ghost badge-sm">{{ $category->purchases_count }}</span></td>
                            <td class="text-right">
                                <x-button icon="o-pencil-square" wire:click="edit({{ $category->id }})"
                                    class="btn-sm btn-ghost text-blue-500" />
                                <x-button icon="o-trash" wire:click="confirmDelete({{ $category->id }})"
                                    class="btn-sm btn-square btn-ghost text-red-500" />
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-10 text-gray-500">
                                {{ __('Tidak ada kategori yang cocok dengan filter.') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $categories->links() }}</div>
    </x-card>

    {{-- MODAL FORM --}}
    <x-modal wire:model="modalOpen" :title="$editingId ? __('Edit Kategori') : __('Tambah Kategori')" separator>
        <x-form wire:submit="confirmSave">
            <x-input label="{{ __('Nama') }}" wire:model="nama" icon="o-tag" />
            <x-input label="{{ __('Warna') }}" wire:model="warna" type="color" />

            <x-slot:actions>
                <x-button label="{{ __('Batal') }}" @click="$wire.modalOpen = false" />
                <x-button label="{{ __('Simpan') }}" class="btn-primary" type="submit" spinner="save" />
            </x-slot:actions>
        </x-form>
    </x-modal>

    {{-- MODAL DELETE --}}
    <x-modal-confirm wire:model="deleteModalOpen" title="{{ __('Hapus Kategori?') }}"
        text="{{ __('Apakah Anda yakin ingin menghapus kategori ini?') }}" confirm-text="{{ __('Ya, Hapus') }}"
        method="delete" />

    {{-- MODAL KONFIRMASI GENERIK --}}
    <x-modal-confirm wire:model="confirmModalOpen" :title="$confirmTitle" :text="$confirmMessage"
        :confirm-text="$confirmButton" :icon="$confirmIcon" :danger="$confirmDanger" method="confirmProceed" />
</div>
