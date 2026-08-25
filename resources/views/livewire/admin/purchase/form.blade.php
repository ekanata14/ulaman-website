@php
    use App\Enums\BundleType;
    use App\Enums\DiscountType;
    use App\Enums\PaymentMethod;
    use App\Enums\PurchaseStatus;
    use App\Support\Money;

    $diskonOptions = collect(DiscountType::cases())->map(fn($c) => ['id' => $c->value, 'name' => $c->label()])->all();
    $bundleOptions = collect(BundleType::cases())->map(fn($c) => ['id' => $c->value, 'name' => $c->label()])->all();
    $metodeOptions = collect(PaymentMethod::cases())->map(fn($c) => ['id' => $c->value, 'name' => $c->label()])->all();
    $statusOptions = collect(PurchaseStatus::cases())->map(fn($c) => ['id' => $c->value, 'name' => $c->label()])->all();

    // UID item yang sudah menjadi anggota bundle mana pun.
    $bundledUids = collect($form->bundles)->flatMap(fn($b) => $b['itemUids'])->all();
    // Peta uid => deskripsi untuk menampilkan anggota bundle.
    $descByUid = collect($form->items)->mapWithKeys(fn($r) => [$r['uid'] => $r['deskripsi']])->all();
@endphp

<div>
    <x-header :title="$form->purchaseId ? __('Edit Purchase Note') : __('Add Purchase Note')"
        subtitle="{{ $purchase?->kode }}" separator>
        <x-slot:actions>
            <x-button label="{{ __('Back') }}" icon="o-arrow-left" link="{{ route('admin.purchases') }}"
                class="btn-ghost" />
            @if ($form->purchaseId)
                <x-button label="{{ __('Delete') }}" icon="o-trash" wire:click="confirmDelete"
                    class="btn-error btn-outline" />
            @endif
        </x-slot:actions>
    </x-header>

    <x-form wire:submit="confirmSave">
        {{-- HEADER NOTA --}}
        <x-card class="bg-base-100 shadow-sm mb-6" data-tour="form-header">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <x-input type="date" label="{{ __('Date') }}" wire:model="form.tanggal" icon="o-calendar" />
                <div class="flex items-end gap-1">
                    <div class="flex-1 min-w-0">
                        <x-choices-offline label="{{ __('Supplier') }}" wire:model="form.supplierId" :options="$suppliers"
                            single searchable placeholder="{{ __('Pick or leave empty (Lain-lain)') }}"
                            icon="o-building-storefront" />
                    </div>
                    <x-button icon="o-plus" wire:click="openSupplierModal" class="btn-square btn-outline btn-primary"
                        tooltip-left="{{ __('Add Supplier') }}" />
                </div>
                <x-input label="{{ __('Note No.') }}" wire:model="form.nomorNota" icon="o-hashtag" />
                <div class="flex items-end gap-1">
                    <div class="flex-1 min-w-0">
                        <x-select label="{{ __('Category') }}" wire:model="form.categoryId" :options="$categories"
                            placeholder="{{ __('None') }}" icon="o-tag" />
                    </div>
                    <x-button icon="o-plus" wire:click="openCategoryModal" class="btn-square btn-outline btn-primary"
                        tooltip-left="{{ __('Add Category') }}" />
                </div>
                <x-select label="{{ __('Payment Method') }}" wire:model="form.metodeBayar" :options="$metodeOptions"
                    placeholder="{{ __('None') }}" icon="o-banknotes" />
                <x-select label="{{ __('Status') }}" wire:model="form.status" :options="$statusOptions" icon="o-flag" />
                <div class="md:col-span-2 lg:col-span-3">
                    <x-textarea label="{{ __('Remark') }}" wire:model="form.remark" rows="2" />
                </div>
            </div>
        </x-card>

        {{-- ITEMS --}}
        <x-card class="bg-base-100 shadow-sm mb-6" data-tour="form-items">
            <x-slot:title>
                <div class="flex items-center justify-between">
                    <span class="font-bold text-lg">{{ __('Items') }}</span>
                    <x-button label="{{ __('Add Row') }}" icon="o-plus" wire:click="addItem" class="btn-sm btn-primary" />
                </div>
            </x-slot:title>

            @error('form.items')
                <x-alert icon="o-exclamation-triangle" class="alert-error mb-3">{{ $message }}</x-alert>
            @enderror

            @if (count($form->items) === 0)
                <div class="text-center py-10 text-gray-400 text-sm">
                    {{ __('No items yet — click "Add Row" to start.') }}
                </div>
            @endif

            <div class="space-y-4">
                @foreach ($form->items as $i => $row)
                    @php($alloc = $preview?->items[$row['uid']] ?? null)
                    @php($inBundle = in_array($row['uid'], $bundledUids, true))
                    <div wire:key="row-{{ $row['uid'] }}"
                        class="rounded-lg border border-base-300 bg-base-100 p-4"
                        x-data="{ get sub() { return (parseFloat($wire.form.items[{{ $i }}]?.qty) || 0) * (parseFloat($wire.form.items[{{ $i }}]?.hargaSatuan) || 0); } }">
                        {{-- Baris atas: checkbox bundle · deskripsi · hapus --}}
                        <div class="flex items-start gap-3">
                            <label class="mt-2 shrink-0"
                                title="{{ $inBundle ? __('Already in a bundle') : __('Select for bundle') }}">
                                <input type="checkbox" class="checkbox checkbox-sm" wire:model.live="selectedForBundle"
                                    value="{{ $row['uid'] }}" @disabled($inBundle) />
                            </label>
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-1 mb-1">
                                    <div class="flex-1 min-w-0">
                                        <x-choices-offline wire:model.live="form.items.{{ $i }}.itemId" :options="$items"
                                            single searchable placeholder="{{ __('Pick master item (optional)') }}" />
                                    </div>
                                    <x-button icon="o-plus" wire:click="openItemModal({{ $i }})"
                                        class="btn-sm btn-square btn-outline btn-primary"
                                        tooltip-left="{{ __('Add Item') }}" />
                                </div>
                                <x-input wire:model="form.items.{{ $i }}.deskripsi" placeholder="{{ __('Description') }}" />
                                @error("form.items.$i.deskripsi")
                                    <span class="text-error text-xs">{{ $message }}</span>
                                @enderror
                            </div>
                            <x-button icon="o-trash" wire:click="confirmRemoveItem('{{ $row['uid'] }}')"
                                class="btn-sm btn-ghost text-red-500 shrink-0" tooltip-left="{{ __('Remove') }}" />
                        </div>

                        {{-- Grid field berlabel --}}
                        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3 mt-3">
                            <div>
                                <x-input label="{{ __('Qty') }}" type="number" step="0.01"
                                    wire:model="form.items.{{ $i }}.qty" />
                                @error("form.items.$i.qty")
                                    <span class="text-error text-xs">{{ $message }}</span>
                                @enderror
                            </div>
                            <div class="flex items-end gap-1">
                                <div class="flex-1 min-w-0">
                                    <x-select label="{{ __('Unit') }}" wire:model="form.items.{{ $i }}.unitId"
                                        :options="$units" placeholder="—" />
                                </div>
                                <x-button icon="o-plus" wire:click="openUnitModal({{ $i }})"
                                    class="btn-square btn-outline btn-primary" tooltip-left="{{ __('Add Unit') }}" />
                            </div>
                            <x-money-input label="{{ __('Unit Price') }}" prefix="Rp"
                                wire-model="form.items.{{ $i }}.hargaSatuan" :value="$row['hargaSatuan'] ?? ''" />
                            <div>
                                <x-select label="{{ __('Discount') }}" wire:model.live="form.items.{{ $i }}.diskonTipe"
                                    :options="$diskonOptions" />
                                <div class="mt-1" x-show="$wire.form.items[{{ $i }}]?.diskonTipe !== 'NONE'">
                                    <x-money-input :prefix="null" wire-model="form.items.{{ $i }}.diskonNilai"
                                        :value="$row['diskonNilai'] ?? ''" />
                                </div>
                            </div>
                            <div>
                                <label class="fieldset-label text-xs text-gray-500">{{ __('Subtotal') }}</label>
                                <div class="font-mono font-semibold mt-1"
                                    x-text="'Rp ' + new Intl.NumberFormat('id-ID').format(sub)"></div>
                                @if ($alloc && bccomp($alloc->alokasiDiskonBundle, '0', 2) === 1)
                                    <div class="text-xs text-amber-600 font-mono">
                                        {{ __('Bundle') }}: -{{ Money::format($alloc->alokasiDiskonBundle) }}
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </x-card>

        {{-- BUNDLE MANAGER (muncul saat >= 2 item dicentang) --}}
        @if (count($selectedForBundle) >= 2)
            <x-card class="bg-amber-50 border border-amber-200 shadow-sm mb-6">
                <x-slot:title>
                    <span class="font-bold text-amber-800">{{ __('Create Bundle') }}
                        ({{ count($selectedForBundle) }} {{ __('items selected') }})</span>
                </x-slot:title>
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
                    <x-input label="{{ __('Bundle Name') }}" wire:model="bundleNama" />
                    <x-select label="{{ __('Bundle Type') }}" wire:model="bundleTipe" :options="$bundleOptions" />
                    <div>
                        <x-money-input :prefix="null" label="{{ __('Value / Package Price') }}"
                            wire-model="bundleNilai" :value="$bundleNilai" />
                        <p class="fieldset-label text-xs text-gray-400 mt-1">
                            {{ __('% for PERSEN, Rp for NOMINAL / package price') }}</p>
                    </div>
                    <x-button label="{{ __('Create Bundle') }}" icon="o-cube" wire:click="confirmCreateBundle"
                        class="btn-primary" spinner="confirmCreateBundle" />
                </div>
            </x-card>
        @endif

        {{-- DAFTAR BUNDLE + PRATINJAU ALOKASI --}}
        @if (count($form->bundles) > 0)
            <x-card class="bg-base-100 shadow-sm mb-6">
                <x-slot:title><span class="font-bold text-lg">{{ __('Bundles') }}</span></x-slot:title>
                <div class="space-y-4">
                    @foreach ($form->bundles as $bIndex => $bundle)
                        <div class="border border-base-300 rounded-lg p-4" wire:key="bundle-{{ $bundle['uid'] }}">
                            <div class="flex items-center justify-between mb-2">
                                <div>
                                    <span class="font-bold">{{ $bundle['nama'] }}</span>
                                    <span class="badge badge-sm badge-neutral ml-2">
                                        {{ BundleType::from($bundle['tipe'])->label() }}: {{ $bundle['nilai'] }}</span>
                                </div>
                                <x-button icon="o-trash" wire:click="confirmRemoveBundle('{{ $bundle['uid'] }}')"
                                    class="btn-xs btn-ghost text-red-500" label="{{ __('Dissolve') }}" />
                            </div>
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>{{ __('Member') }}</th>
                                        <th class="text-right">{{ __('Allocated Discount') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($bundle['itemUids'] as $uid)
                                        @php($a = $preview?->items[$uid] ?? null)
                                        <tr>
                                            <td>{{ $descByUid[$uid] ?? $uid }}</td>
                                            <td class="text-right font-mono">
                                                {{ $a ? Money::format($a->alokasiDiskonBundle) : '—' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endforeach
                </div>
            </x-card>
        @endif

        {{-- PRATINJAU SERVER (angka final sebelum simpan) --}}
        <x-card class="bg-base-100 shadow-sm mb-6" data-tour="form-preview">
            <div class="flex flex-col md:flex-row md:justify-end gap-6">
                @if ($preview)
                    <div class="text-sm space-y-1 md:text-right">
                        <div class="text-gray-500">{{ __('Subtotal') }}: <span class="font-mono">{{ Money::format($preview->subtotal) }}</span></div>
                        <div class="text-gray-500">{{ __('Item Discounts') }}: <span class="font-mono">-{{ Money::format($preview->totalDiskonItem) }}</span></div>
                        <div class="text-gray-500">{{ __('Bundle Discounts') }}: <span class="font-mono">-{{ Money::format($preview->totalDiskonBundle) }}</span></div>
                        <div class="text-lg font-bold pt-1">{{ __('Grand Total') }}:
                            <span class="font-mono text-primary">{{ Money::format($preview->grandTotal) }}</span></div>
                        <div class="text-xs text-gray-400">{{ __('Computed on the server (authoritative).') }}</div>
                    </div>
                @else
                    <div class="text-sm text-gray-400">{{ __('Complete item data to preview totals.') }}</div>
                @endif
            </div>
        </x-card>

        <x-slot:actions>
            <x-button label="{{ __('Cancel') }}" link="{{ route('admin.purchases') }}" class="btn-ghost" />
            <x-button label="{{ __('Save') }}" type="submit" icon="o-check" class="btn-primary"
                spinner="confirmSave" />
        </x-slot:actions>
    </x-form>

    {{-- FOTO NOTA (mode edit) --}}
    @if ($form->purchaseId && $purchase)
        <livewire:admin.purchase.photo-uploader :purchase="$purchase" :key="'uploader-' . $purchase->id" />
    @endif

    <x-photo-lightbox />

    @if ($form->purchaseId)
        <x-modal-confirm wire:model="deleteModalOpen" title="{{ __('Delete Purchase Note?') }}"
            text="{{ __('This will remove the note and all its items. This action cannot be undone.') }}"
            confirm-text="{{ __('Yes, Delete') }}" method="delete" />
    @endif

    {{-- QUICK-ADD: SUPPLIER --}}
    <x-modal wire:model="supplierModalOpen" title="{{ __('Add Supplier') }}" separator>
        <x-form wire:submit="confirmSaveSupplier">
            <x-input label="{{ __('Nama') }}" wire:model="qsNama" icon="o-building-storefront" />
            <x-input label="{{ __('PIC') }}" wire:model="qsPic" icon="o-user" />
            <x-input label="{{ __('Telepon') }}" wire:model="qsTelepon" icon="o-phone" />
            <x-slot:actions>
                <x-button label="{{ __('Batal') }}" @click="$wire.supplierModalOpen = false" />
                <x-button label="{{ __('Simpan') }}" type="submit" class="btn-primary" spinner="confirmSaveSupplier" />
            </x-slot:actions>
        </x-form>
    </x-modal>

    {{-- QUICK-ADD: KATEGORI --}}
    <x-modal wire:model="categoryModalOpen" title="{{ __('Add Category') }}" separator>
        <x-form wire:submit="confirmSaveCategory">
            <x-input label="{{ __('Nama') }}" wire:model="qcNama" icon="o-tag" />
            <x-input label="{{ __('Warna') }}" wire:model="qcWarna" placeholder="#RRGGBB" />
            <x-slot:actions>
                <x-button label="{{ __('Batal') }}" @click="$wire.categoryModalOpen = false" />
                <x-button label="{{ __('Simpan') }}" type="submit" class="btn-primary" spinner="confirmSaveCategory" />
            </x-slot:actions>
        </x-form>
    </x-modal>

    {{-- QUICK-ADD: SATUAN --}}
    <x-modal wire:model="unitModalOpen" title="{{ __('Add Unit') }}" separator>
        <x-form wire:submit="confirmSaveUnit">
            <x-input label="{{ __('Nama') }}" wire:model="quNama" />
            <x-input label="{{ __('Simbol') }}" wire:model="quSimbol" />
            <x-slot:actions>
                <x-button label="{{ __('Batal') }}" @click="$wire.unitModalOpen = false" />
                <x-button label="{{ __('Simpan') }}" type="submit" class="btn-primary" spinner="confirmSaveUnit" />
            </x-slot:actions>
        </x-form>
    </x-modal>

    {{-- QUICK-ADD: MASTER ITEM --}}
    <x-modal wire:model="itemModalOpen" title="{{ __('Add Item') }}" separator>
        <x-form wire:submit="confirmSaveItem">
            <x-input label="{{ __('Nama') }}" wire:model="qiNama" icon="o-cube" />
            <div class="grid grid-cols-2 gap-3">
                <x-select label="{{ __('Unit') }}" wire:model="qiUnitId" :options="$units" placeholder="—" />
                <x-select label="{{ __('Category') }}" wire:model="qiCategoryId" :options="$categories"
                    placeholder="{{ __('None') }}" />
            </div>
            <x-slot:actions>
                <x-button label="{{ __('Batal') }}" @click="$wire.itemModalOpen = false" />
                <x-button label="{{ __('Simpan') }}" type="submit" class="btn-primary" spinner="confirmSaveItem" />
            </x-slot:actions>
        </x-form>
    </x-modal>

    {{-- MODAL KONFIRMASI GENERIK --}}
    <x-modal-confirm wire:model="confirmModalOpen" :title="$confirmTitle" :text="$confirmMessage"
        :confirm-text="$confirmButton" :icon="$confirmIcon" :danger="$confirmDanger" method="confirmProceed" />
</div>
