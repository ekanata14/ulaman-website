@php
    use App\Enums\BundleType;
    use App\Enums\DiscountType;
    use App\Support\Money;

    $diskonOptions = collect(DiscountType::cases())->map(fn($c) => ['id' => $c->value, 'name' => $c->label()])->all();
    $bundleOptions = collect(BundleType::cases())->map(fn($c) => ['id' => $c->value, 'name' => $c->label()])->all();

    // UID item yang sudah menjadi anggota bundle mana pun.
    $bundledUids = collect($form->bundles)->flatMap(fn($b) => $b['itemUids'])->all();
    // Peta uid => deskripsi untuk menampilkan anggota bundle.
    $descByUid = collect($form->items)->mapWithKeys(fn($r) => [$r['uid'] => $r['deskripsi']])->all();
@endphp

<div>
    @unless ($embedded)
        <x-header :title="$form->purchaseId ? __('Edit Purchase Item') : __('Add Purchase Item')"
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
    @endunless

    <x-form wire:submit="{{ $embedded ? 'save' : 'confirmSave' }}">
        {{-- HEADER NOTA --}}
        <x-card class="bg-base-100 shadow-sm mb-6" data-tour="form-header">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <x-input type="date" label="{{ __('Date') }}" wire:model="form.tanggal" icon="o-calendar" required />
                <div class="flex items-end gap-1">
                    <div class="flex-1 min-w-0">
                        <x-choices-offline label="{{ __('Supplier') }}" wire:model="form.supplierId" :options="$suppliers"
                            single searchable placeholder="{{ __('Pick or leave empty (Lain-lain)') }}"
                            icon="o-building-storefront" />
                    </div>
                    <x-button icon="o-plus" wire:click="openSupplierModal" class="btn-square btn-outline btn-primary"
                        tooltip-left="{{ __('Add Supplier') }}" />
                </div>
                <x-input label="{{ __('Item No.') }}" wire:model="form.nomorNota" icon="o-hashtag" />
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

            {{-- Penjelasan fungsi checkbox bundle agar tidak membingungkan --}}
            <div class="flex items-start gap-2 text-xs text-gray-500 bg-amber-50 border border-amber-100 rounded-lg px-3 py-2 mb-4">
                <x-icon name="o-information-circle" class="w-4 h-4 shrink-0 mt-0.5 text-amber-500" />
                <span>{{ __('Check the box on the left of an item to select it, then pick 2 or more items to create a Bundle (a shared discount / package price).') }}</span>
            </div>

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
                            <label class="flex flex-col items-center gap-1 mt-1 shrink-0 cursor-pointer"
                                title="{{ $inBundle ? __('Already in a bundle') : __('Select this item for a bundle') }}">
                                <input type="checkbox" class="checkbox checkbox-sm" wire:model.live="selectedForBundle"
                                    value="{{ $row['uid'] }}" @disabled($inBundle) />
                                <span class="text-[10px] leading-none {{ $inBundle ? 'text-amber-600 font-medium' : 'text-gray-400' }}">
                                    {{ $inBundle ? __('In bundle') : __('Bundle') }}
                                </span>
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
                                <label class="fieldset-label text-xs text-gray-500 mb-1 block">{{ __('Description') }} <span class="text-error">*</span></label>
                                <x-input wire:model="form.items.{{ $i }}.deskripsi" placeholder="{{ __('Description') }}" />
                                @error("form.items.$i.deskripsi")
                                    <span class="text-error text-xs">{{ $message }}</span>
                                @enderror
                            </div>
                            <x-button icon="o-trash" wire:click="confirmRemoveItem('{{ $row['uid'] }}')"
                                class="btn-sm btn-ghost text-red-500 shrink-0" tooltip-left="{{ __('Remove') }}" />
                        </div>

                        {{-- Grid field berlabel — semua kolom rata bawah (md:items-end) --}}
                        <div class="grid grid-cols-1 md:grid-cols-6 gap-x-3 gap-y-4 mt-3 md:items-end">
                            {{-- Qty --}}
                            <div class="md:col-span-1">
                                <label class="fieldset-label text-xs text-gray-500 mb-1 block">{{ __('Qty') }} <span class="text-error">*</span></label>
                                <x-input type="number" step="0.01" wire:model="form.items.{{ $i }}.qty" />
                                @error("form.items.$i.qty")
                                    <span class="text-error text-xs">{{ $message }}</span>
                                @enderror
                            </div>

                            {{-- Unit --}}
                            <div class="md:col-span-1">
                                <label class="fieldset-label text-xs text-gray-500 mb-1 block">{{ __('Unit') }}</label>
                                <div class="flex items-center gap-1">
                                    <div class="flex-1 min-w-0">
                                        <x-select wire:model="form.items.{{ $i }}.unitId" :options="$units"
                                            placeholder="—" />
                                    </div>
                                    <x-button icon="o-plus" wire:click="openUnitModal({{ $i }})"
                                        class="btn-square btn-outline btn-primary" tooltip-left="{{ __('Add Unit') }}" />
                                </div>
                            </div>

                            {{-- Unit Price --}}
                            <div class="md:col-span-1">
                                <label class="fieldset-label text-xs text-gray-500 mb-1 block">{{ __('Unit Price') }}</label>
                                <x-money-input prefix="Rp" wire-model="form.items.{{ $i }}.hargaSatuan"
                                    :value="$row['hargaSatuan'] ?? ''" />
                            </div>

                            {{-- Discount: tipe + nilai + tombol Apply (sebaris) --}}
                            <div class="md:col-span-2">
                                <label class="fieldset-label text-xs text-gray-500 mb-1 block">{{ __('Discount') }}</label>
                                <div class="flex items-center gap-2">
                                    <div class="w-36 shrink-0">
                                        <x-select wire:model="form.items.{{ $i }}.diskonTipe" :options="$diskonOptions" />
                                    </div>
                                    <div class="flex-1 min-w-0"
                                        x-show="$wire.form.items[{{ $i }}]?.diskonTipe !== 'NONE'">
                                        <x-money-input :prefix="null" wire-model="form.items.{{ $i }}.diskonNilai"
                                            :value="$row['diskonNilai'] ?? ''" />
                                    </div>
                                    <x-button icon="o-calculator" wire:click="applyDiscount"
                                        x-show="$wire.form.items[{{ $i }}]?.diskonTipe !== 'NONE'"
                                        class="btn-square btn-primary shrink-0" spinner="applyDiscount"
                                        tooltip-left="{{ __('Apply Discount') }}" />
                                </div>
                            </div>

                            {{-- Subtotal --}}
                            <div class="md:col-span-1">
                                <label class="fieldset-label text-xs text-gray-500 mb-1 block">{{ __('Subtotal') }}</label>
                                <div class="font-mono font-semibold h-10 flex items-center"
                                    x-text="'Rp ' + new Intl.NumberFormat('id-ID').format(sub)"></div>
                                @if ($alloc && bccomp($alloc->diskonAmount, '0', 2) === 1)
                                    <div class="text-xs text-emerald-600 font-mono">
                                        {{ __('Discount') }}: -{{ Money::format($alloc->diskonAmount) }}
                                    </div>
                                @endif
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
                    <x-input label="{{ __('Bundle Name') }}" wire:model="bundleNama" required />
                    <x-select label="{{ __('Bundle Type') }}" wire:model="bundleTipe" :options="$bundleOptions" required />
                    <div>
                        <x-money-input :prefix="null" label="{{ __('Value / Package Price') }}"
                            wire-model="bundleNilai" :value="$bundleNilai" required />
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

        {{-- DISKON NOTA + PRATINJAU SERVER (angka final sebelum simpan) --}}
        <x-card class="bg-base-100 shadow-sm mb-6" data-tour="form-preview">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                {{-- Diskon tingkat nota + tombol Apply --}}
                <div class="space-y-3">
                    <span class="font-semibold text-sm">{{ __('Overall Discount') }}</span>
                    <div class="flex items-end gap-3">
                        <div class="w-40">
                            <x-select label="{{ __('Discount') }}" wire:model="form.diskonNotaTipe"
                                :options="$diskonOptions" />
                        </div>
                        <div class="flex-1" x-show="$wire.form.diskonNotaTipe !== 'NONE'">
                            <x-money-input :prefix="null" label="{{ __('Value') }}"
                                wire-model="form.diskonNotaNilai" :value="$form->diskonNotaNilai" />
                        </div>
                    </div>
                    <p class="fieldset-label text-xs text-gray-400">
                        {{ __('% for PERSEN, Rp for NOMINAL') }}</p>
                    <x-button label="{{ __('Apply Discount') }}" icon="o-calculator" wire:click="applyDiscount"
                        class="btn-primary btn-sm" spinner="applyDiscount" />
                </div>

                {{-- Pratinjau total --}}
                <div class="flex md:justify-end">
                    @if ($preview)
                        <div class="text-sm space-y-1 md:text-right">
                            <div class="text-gray-500">{{ __('Subtotal') }}: <span class="font-mono">{{ Money::format($preview->subtotal) }}</span></div>
                            <div class="text-gray-500">{{ __('Item Discounts') }}: <span class="font-mono">-{{ Money::format($preview->totalDiskonItem) }}</span></div>
                            <div class="text-gray-500">{{ __('Bundle Discounts') }}: <span class="font-mono">-{{ Money::format($preview->totalDiskonBundle) }}</span></div>
                            <div class="text-gray-500">{{ __('Overall Discount') }}: <span class="font-mono">-{{ Money::format($preview->diskonNotaAmount) }}</span></div>
                            <div class="text-lg font-bold pt-1">{{ __('Grand Total') }}:
                                <span class="font-mono text-primary">{{ Money::format($preview->grandTotal) }}</span></div>
                            <div class="text-xs text-gray-400">{{ __('Computed on the server (authoritative).') }}</div>
                        </div>
                    @else
                        <div class="text-sm text-gray-400">{{ __('Complete item data to preview totals.') }}</div>
                    @endif
                </div>
            </div>
        </x-card>

        {{-- BUKTI TRANSFER (bisa diunggah sebelum nota disimpan) --}}
        <x-card class="bg-base-100 shadow-sm mb-6">
            <x-slot:title><span class="font-bold text-lg">{{ __('Bukti Transfer') }}</span></x-slot:title>

            <div x-data="buktiTransferUpload">
                <input type="file" x-ref="input" accept="image/*,application/pdf" multiple
                    class="file-input file-input-bordered w-full" @change="handle($event)" :disabled="uploading" />
                <div class="text-xs text-gray-400 mt-1">
                    {{ __('Max 5 files, 50 MB each. JPG/PNG/WEBP/PDF. Images are compressed on your device.') }}
                </div>
                <div x-show="uploading" class="mt-2">
                    <progress class="progress progress-primary w-full" :value="progress" max="100"></progress>
                    <span class="text-xs" x-text="`${progress}%`"></span>
                </div>
            </div>

            @error('buktiTransfers.*')
                <x-alert icon="o-exclamation-triangle" class="alert-error mt-3">{{ $message }}</x-alert>
            @enderror

            {{-- Berkas menunggu (temp, belum disimpan) --}}
            @if (count($buktiTransfers) > 0)
                <div class="grid grid-cols-3 md:grid-cols-5 gap-3 mt-4">
                    @foreach ($buktiTransfers as $idx => $file)
                        <div class="relative group border border-base-300 rounded-lg p-2 flex flex-col items-center justify-center h-28"
                            wire:key="pending-bt-{{ $idx }}">
                            @if (str_starts_with($file->getMimeType() ?? '', 'image/'))
                                <img src="{{ $file->temporaryUrl() }}" class="w-full h-full object-cover rounded" />
                            @else
                                <x-icon name="o-document" class="w-8 h-8 text-gray-400" />
                                <span class="text-[10px] text-gray-500 truncate w-full text-center mt-1">{{ $file->getClientOriginalName() }}</span>
                            @endif
                            <x-button icon="o-x-mark" wire:click="removeBuktiTransfer({{ $idx }})"
                                class="btn-xs btn-circle btn-error absolute top-1 right-1 opacity-0 group-hover:opacity-100 transition" />
                        </div>
                    @endforeach
                </div>
            @endif

            {{-- Berkas tersimpan (mode edit) --}}
            @if (count($savedBuktiTransfers) > 0)
                <div class="grid grid-cols-3 md:grid-cols-5 gap-3 mt-4">
                    @foreach ($savedBuktiTransfers as $bt)
                        <div class="relative group border border-base-300 rounded-lg h-28"
                            wire:key="bt-{{ $bt['id'] }}">
                            @if ($bt['isPdf'])
                                <a href="{{ $bt['url'] }}" target="_blank"
                                    class="flex flex-col items-center justify-center h-full p-2">
                                    <x-icon name="o-document-text" class="w-8 h-8 text-red-500" />
                                    <span class="text-[10px] text-gray-500 truncate w-full text-center mt-1">{{ $bt['nama'] }}</span>
                                </a>
                            @else
                                <a href="{{ $bt['url'] }}" target="_blank" class="block h-full">
                                    <img src="{{ $bt['thumb'] }}" alt="{{ $bt['nama'] }}"
                                        class="w-full h-full object-cover rounded-lg" />
                                </a>
                            @endif
                            <x-button icon="o-trash" wire:click="deleteBuktiTransfer({{ $bt['id'] }})"
                                class="btn-xs btn-circle btn-error absolute top-1 right-1 opacity-0 group-hover:opacity-100 transition" />
                        </div>
                    @endforeach
                </div>
            @endif
        </x-card>

        <x-slot:actions>
            @if ($embedded)
                <x-button label="{{ __('Cancel') }}" wire:click="cancelEmbedded" class="btn-ghost" />
            @else
                <x-button label="{{ __('Cancel') }}" link="{{ route('admin.purchases') }}" class="btn-ghost" />
            @endif
            <x-button label="{{ __('Save') }}" type="submit" icon="o-check" class="btn-primary"
                spinner="{{ $embedded ? 'save' : 'confirmSave' }}" />
        </x-slot:actions>
    </x-form>

    {{-- FOTO NOTA (mode edit) --}}
    @if ($form->purchaseId && $purchase)
        <livewire:admin.purchase.photo-uploader :purchase="$purchase" :key="'uploader-' . $purchase->id" />
    @endif

    <x-photo-lightbox />

    @if ($form->purchaseId)
        <x-modal-confirm wire:model="deleteModalOpen" title="{{ __('Delete Purchase Item?') }}"
            text="{{ __('This will remove the purchase item and all its lines. This action cannot be undone.') }}"
            confirm-text="{{ __('Yes, Delete') }}" method="delete" />
    @endif

    {{-- QUICK-ADD: SUPPLIER --}}
    <x-modal wire:model="supplierModalOpen" title="{{ __('Add Supplier') }}" separator>
        <x-form wire:submit="confirmSaveSupplier">
            <x-input label="{{ __('Nama') }}" wire:model="qsNama" icon="o-building-storefront" required />
            <x-input label="{{ __('PIC') }}" wire:model="qsPic" icon="o-user" />
            <x-input label="{{ __('Telepon') }}" wire:model="qsTelepon" icon="o-phone" />
            <x-slot:actions>
                <x-button label="{{ __('Batal') }}" @click="$wire.supplierModalOpen = false" />
                <x-button label="{{ __('Simpan') }}" type="submit" class="btn-primary" spinner="confirmSaveSupplier" />
            </x-slot:actions>
        </x-form>
    </x-modal>

    {{-- QUICK-ADD: SATUAN --}}
    <x-modal wire:model="unitModalOpen" title="{{ __('Add Unit') }}" separator>
        <x-form wire:submit="confirmSaveUnit">
            <x-input label="{{ __('Nama') }}" wire:model="quNama" required />
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
            <x-input label="{{ __('Nama') }}" wire:model="qiNama" icon="o-cube" required />
            <x-select label="{{ __('Unit') }}" wire:model="qiUnitId" :options="$units" placeholder="—" />
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
