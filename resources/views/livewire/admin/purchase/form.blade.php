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

    <x-form wire:submit="save">
        {{-- HEADER NOTA --}}
        <x-card class="bg-base-100 shadow-sm mb-6">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <x-input type="date" label="{{ __('Date') }}" wire:model="form.tanggal" icon="o-calendar" />
                <x-choices-offline label="{{ __('Supplier') }}" wire:model="form.supplierId" :options="$suppliers" single
                    searchable placeholder="{{ __('Pick or leave empty (Lain-lain)') }}" icon="o-building-storefront" />
                <x-input label="{{ __('Note No.') }}" wire:model="form.nomorNota" icon="o-hashtag" />
                <x-select label="{{ __('Category') }}" wire:model="form.categoryId" :options="$categories"
                    placeholder="{{ __('None') }}" icon="o-tag" />
                <x-select label="{{ __('Payment Method') }}" wire:model="form.metodeBayar" :options="$metodeOptions"
                    placeholder="{{ __('None') }}" icon="o-banknotes" />
                <x-select label="{{ __('Status') }}" wire:model="form.status" :options="$statusOptions" icon="o-flag" />
                <div class="md:col-span-2 lg:col-span-3">
                    <x-textarea label="{{ __('Remark') }}" wire:model="form.remark" rows="2" />
                </div>
            </div>
        </x-card>

        {{-- ITEMS --}}
        <x-card class="bg-base-100 shadow-sm mb-6">
            <x-slot:title>
                <div class="flex items-center justify-between">
                    <span class="font-bold text-lg">{{ __('Items') }}</span>
                    <x-button label="{{ __('Add Row') }}" icon="o-plus" wire:click="addItem" class="btn-sm btn-primary" />
                </div>
            </x-slot:title>

            @error('form.items')
                <x-alert icon="o-exclamation-triangle" class="alert-error mb-3">{{ $message }}</x-alert>
            @enderror

            <div class="overflow-x-auto">
                <table class="table">
                    <thead>
                        <tr>
                            <th class="w-10" title="{{ __('Bundle') }}"><x-icon name="o-cube" class="w-4 h-4" /></th>
                            <th class="min-w-[220px]">{{ __('Description') }}</th>
                            <th class="w-24">{{ __('Qty') }}</th>
                            <th class="w-28">{{ __('Unit') }}</th>
                            <th class="w-36">{{ __('Unit Price') }}</th>
                            <th class="w-32">{{ __('Discount') }}</th>
                            <th class="w-28 text-right">{{ __('Subtotal') }}</th>
                            <th class="w-28 text-right">{{ __('Bundle Alloc.') }}</th>
                            <th class="w-10"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($form->items as $i => $row)
                            @php($alloc = $preview?->items[$row['uid']] ?? null)
                            <tr wire:key="row-{{ $row['uid'] }}"
                                x-data="{ get sub() { return (parseFloat($wire.form.items[{{ $i }}]?.qty) || 0) * (parseFloat($wire.form.items[{{ $i }}]?.hargaSatuan) || 0); } }">
                                <td class="align-top pt-4 text-center">
                                    <input type="checkbox" class="checkbox checkbox-sm" wire:model.live="selectedForBundle"
                                        value="{{ $row['uid'] }}" @disabled(in_array($row['uid'], $bundledUids, true))
                                        title="{{ in_array($row['uid'], $bundledUids, true) ? __('Already in a bundle') : __('Select for bundle') }}" />
                                </td>
                                <td class="align-top">
                                    <x-choices-offline wire:model.live="form.items.{{ $i }}.itemId" :options="$items" single
                                        searchable placeholder="{{ __('Pick master item (optional)') }}" class="mb-1" />
                                    <x-input wire:model="form.items.{{ $i }}.deskripsi" placeholder="{{ __('Description') }}" />
                                    @error("form.items.$i.deskripsi")
                                        <span class="text-error text-xs">{{ $message }}</span>
                                    @enderror
                                </td>
                                <td class="align-top">
                                    <x-input type="number" step="0.01" wire:model="form.items.{{ $i }}.qty" />
                                    @error("form.items.$i.qty")
                                        <span class="text-error text-xs">{{ $message }}</span>
                                    @enderror
                                </td>
                                <td class="align-top">
                                    <x-select wire:model="form.items.{{ $i }}.unitId" :options="$units" placeholder="—" />
                                </td>
                                <td class="align-top">
                                    <x-input type="number" step="0.01" wire:model="form.items.{{ $i }}.hargaSatuan"
                                        prefix="Rp" />
                                </td>
                                <td class="align-top">
                                    <x-select wire:model.live="form.items.{{ $i }}.diskonTipe" :options="$diskonOptions" />
                                    <x-input type="number" step="0.01" wire:model="form.items.{{ $i }}.diskonNilai"
                                        class="mt-1" x-show="$wire.form.items[{{ $i }}]?.diskonTipe !== 'NONE'" />
                                </td>
                                <td class="align-top pt-4 text-right font-mono"
                                    x-text="'Rp ' + new Intl.NumberFormat('id-ID').format(sub)"></td>
                                <td class="align-top pt-4 text-right font-mono text-amber-600">
                                    {{ $alloc && bccomp($alloc->alokasiDiskonBundle, '0', 2) === 1 ? '-' . Money::format($alloc->alokasiDiskonBundle) : '—' }}
                                </td>
                                <td class="align-top">
                                    <x-button icon="o-trash" wire:click="removeItem('{{ $row['uid'] }}')"
                                        class="btn-sm btn-ghost text-red-500" />
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
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
                    <x-input type="number" step="0.01" label="{{ __('Value / Package Price') }}"
                        wire:model="bundleNilai" hint="{{ __('% for PERSEN, Rp for NOMINAL / package price') }}" />
                    <x-button label="{{ __('Create Bundle') }}" icon="o-cube" wire:click="createBundle"
                        class="btn-primary" spinner="createBundle" />
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
                                <x-button icon="o-trash" wire:click="removeBundle('{{ $bundle['uid'] }}')"
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
        <x-card class="bg-base-100 shadow-sm mb-6">
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
            <x-button label="{{ __('Save') }}" type="submit" icon="o-check" class="btn-primary" spinner="save" />
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
</div>
