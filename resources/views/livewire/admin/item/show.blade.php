@php
    use App\Support\Money;
@endphp

<div>
    {{-- HEADER --}}
    <x-header title="{{ $item->nama }}" subtitle="{{ __('Detail Barang') }}" separator>
        <x-slot:actions>
            <x-button label="{{ __('Kembali') }}" icon="o-arrow-left" link="{{ route('admin.items') }}"
                class="btn-ghost" />
        </x-slot:actions>
    </x-header>

    {{-- PROFIL + KPI --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-6">
        {{-- Profil barang --}}
        <x-card class="bg-base-100 shadow-sm lg:col-span-1">
            <div class="flex items-center gap-2 mb-3">
                <x-icon name="o-cube" class="w-5 h-5 text-primary" />
                <span class="font-bold">{{ __('Profil') }}</span>
            </div>
            <dl class="text-sm space-y-2">
                <div class="flex justify-between gap-3">
                    <dt class="text-gray-500">{{ __('Satuan') }}</dt>
                    <dd class="text-right">
                        @if ($item->unit)
                            <a href="{{ route('admin.units.show', $item->unit) }}" wire:navigate
                                class="hover:underline">{{ $item->unit->nama }}</a>
                        @else
                            -
                        @endif
                    </dd>
                </div>
                <div class="flex justify-between gap-3">
                    <dt class="text-gray-500">{{ __('Harga Terakhir') }}</dt>
                    <dd class="text-right font-mono">
                        {{ $item->harga_terakhir !== null ? Money::format($item->harga_terakhir) : '-' }}</dd>
                </div>
                <div class="flex justify-between gap-3">
                    <dt class="text-gray-500">{{ __('Supplier Terakhir') }}</dt>
                    <dd class="text-right">
                        @if ($item->supplierTerakhir)
                            <a href="{{ route('admin.suppliers.show', $item->supplierTerakhir) }}" wire:navigate
                                class="hover:underline">{{ $item->supplierTerakhir->nama }}</a>
                        @else
                            -
                        @endif
                    </dd>
                </div>
            </dl>
        </x-card>

        {{-- KPI belanja --}}
        <div class="lg:col-span-2 grid grid-cols-1 sm:grid-cols-3 gap-4">
            <x-stat title="{{ __('Total Belanja') }}" value="{{ Money::format($summary['totalSpend']) }}"
                icon="o-banknotes" class="bg-base-100 shadow-sm border-l-4 border-primary" color="text-primary" />
            <x-stat title="{{ __('Total Qty') }}"
                value="{{ number_format((float) $summary['totalQty'], 2, ',', '.') }}" icon="o-scale"
                class="bg-base-100 shadow-sm border-l-4 border-secondary" color="text-secondary" />
            <x-stat title="{{ __('Jumlah Supplier') }}"
                value="{{ number_format($summary['supplierCount'], 0, ',', '.') }}" icon="o-building-storefront"
                class="bg-base-100 shadow-sm border-l-4 border-accent" color="text-accent" />
        </div>
    </div>

    {{-- RIWAYAT PEMBELIAN --}}
    <x-card class="bg-base-100 shadow-sm">
        <x-slot:title><span class="font-bold text-lg">{{ __('Riwayat pembelian') }}</span></x-slot:title>
        <div class="overflow-x-auto">
            <table class="table table-zebra">
                <thead>
                    <tr>
                        <th>{{ __('Tanggal') }}</th>
                        <th>{{ __('Kode') }}</th>
                        <th>{{ __('Supplier') }}</th>
                        <th class="text-right">{{ __('Qty') }}</th>
                        <th class="text-right">{{ __('Harga Satuan') }}</th>
                        <th class="text-right">{{ __('Net Total') }}</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($history as $line)
                        <tr wire:key="line-{{ $line->id }}">
                            <td>{{ $line->purchase?->tanggal?->format('d M Y') }}</td>
                            <td class="font-mono">{{ $line->purchase?->kode }}</td>
                            <td class="text-gray-500">{{ $line->purchase?->supplier?->nama ?? __('Lain-lain') }}</td>
                            <td class="text-right font-mono">
                                {{ number_format((float) $line->qty, 2, ',', '.') }}
                                <span class="text-gray-400">{{ $line->unit?->nama }}</span>
                            </td>
                            <td class="text-right font-mono">
                                {{ $line->harga_satuan !== null ? Money::format($line->harga_satuan) : '-' }}</td>
                            <td class="text-right font-mono font-semibold">{{ Money::format($line->net_total) }}</td>
                            <td class="text-right">
                                @if ($line->purchase)
                                    <x-button icon="o-pencil-square"
                                        link="{{ route('admin.purchases.edit', $line->purchase) }}"
                                        class="btn-sm btn-ghost text-blue-500" tooltip-left="{{ __('Buka nota') }}" />
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-10 text-gray-500">
                                {{ __('Barang ini belum pernah dibeli.') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $history->links() }}</div>
    </x-card>
</div>
