@php
    use App\Support\Money;
@endphp

<div>
    <x-modal wire:model="modalOpen" :title="$purchase?->kode ?? __('Purchase Note')" separator
        box-class="max-w-3xl">
        @if ($purchase)
            {{-- INFO NOTA --}}
            <div class="grid grid-cols-2 md:grid-cols-4 gap-3 text-sm mb-4">
                <div>
                    <div class="text-gray-400 text-xs">{{ __('Date') }}</div>
                    <div class="font-semibold">{{ $purchase->tanggal->format('d M Y') }}</div>
                </div>
                <div>
                    <div class="text-gray-400 text-xs">{{ __('Supplier') }}</div>
                    <div class="font-semibold">{{ $purchase->supplier?->nama ?? __('Lain-lain') }}</div>
                </div>
                <div>
                    <div class="text-gray-400 text-xs">{{ __('Note No.') }}</div>
                    <div class="font-semibold">{{ $purchase->nomor_nota ?? '—' }}</div>
                </div>
                <div>
                    <div class="text-gray-400 text-xs">{{ __('Payment Method') }}</div>
                    <div class="font-semibold">{{ $purchase->metode_bayar?->label() ?? '—' }}</div>
                </div>
            </div>

            {{-- ITEMS --}}
            <div class="overflow-x-auto">
                <table class="table table-sm">
                    <thead>
                        <tr>
                            <th>{{ __('Description') }}</th>
                            <th class="text-right">{{ __('Qty') }}</th>
                            <th class="text-right">{{ __('Unit Price') }}</th>
                            <th class="text-right">{{ __('Discount') }}</th>
                            <th class="text-right">{{ __('Net') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($purchase->items as $item)
                            @php($totalDiskon = bcadd(bcadd($item->diskon_amount, $item->alokasi_diskon_bundle, 2), $item->alokasi_diskon_nota, 2))
                            <tr>
                                <td>
                                    {{ $item->deskripsi }}
                                    @if ($item->unit)
                                        <span class="text-xs text-gray-400">/ {{ $item->unit->nama }}</span>
                                    @endif
                                </td>
                                <td class="text-right">{{ rtrim(rtrim(number_format((float) $item->qty, 2, ',', '.'), '0'), ',') }}</td>
                                <td class="text-right">{{ $item->harga_satuan !== null ? Money::format($item->harga_satuan) : '—' }}</td>
                                <td class="text-right text-amber-600">
                                    {{ bccomp($totalDiskon, '0', 2) === 1 ? '-' . Money::format($totalDiskon) : '—' }}</td>
                                <td class="text-right font-semibold">{{ Money::format($item->net_total) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- BUNDLE --}}
            @if ($purchase->bundles->isNotEmpty())
                <div class="mt-4">
                    <div class="text-xs font-bold text-gray-500 uppercase mb-1">{{ __('Bundles') }}</div>
                    @foreach ($purchase->bundles as $bundle)
                        <div class="text-sm flex justify-between border-b border-base-200 py-1">
                            <span>{{ $bundle->nama }}
                                <span class="badge badge-xs badge-neutral">{{ $bundle->tipe_diskon->label() }}</span></span>
                            <span class="text-amber-600 font-mono">-{{ Money::format($bundle->diskon_amount) }}</span>
                        </div>
                    @endforeach
                </div>
            @endif

            {{-- TOTAL --}}
            <div class="mt-4 flex flex-col items-end gap-1 text-sm">
                <div class="text-gray-500">{{ __('Subtotal') }}: {{ Money::format($purchase->subtotal) }}</div>
                <div class="text-gray-500">{{ __('Item Discounts') }}: -{{ Money::format($purchase->total_diskon_item) }}</div>
                <div class="text-gray-500">{{ __('Bundle Discounts') }}: -{{ Money::format($purchase->total_diskon_bundle) }}</div>
                <div class="text-lg font-bold">{{ __('Grand Total') }}:
                    <span class="text-primary">{{ Money::format($purchase->grand_total) }}</span></div>
            </div>

            @if ($purchase->remark)
                <div class="mt-3 text-sm bg-base-200 rounded-lg p-3">
                    <span class="text-gray-400 text-xs">{{ __('Remark') }}:</span> {{ $purchase->remark }}
                </div>
            @endif

            {{-- FOTO NOTA --}}
            @if (count($photos) > 0)
                <div class="mt-4" x-data="{ urls: @js(collect($photos)->pluck('full')->values()) }">
                    <div class="text-xs font-bold text-gray-500 uppercase mb-2">{{ __('Nota Photos') }}</div>
                    <div class="grid grid-cols-4 md:grid-cols-6 gap-2">
                        @foreach ($photos as $idx => $photo)
                            <img src="{{ $photo['thumb'] }}"
                                class="w-full h-20 object-cover rounded-lg border border-base-300 cursor-pointer"
                                @click="$dispatch('open-photo-lightbox', { images: urls, index: {{ $idx }} })" />
                        @endforeach
                    </div>
                </div>
            @endif
        @else
            <div class="text-center py-8 text-gray-500">{{ __('Note not found.') }}</div>
        @endif

        <x-slot:actions>
            <x-button label="{{ __('Close') }}" @click="$wire.modalOpen = false" />
        </x-slot:actions>
    </x-modal>
</div>
