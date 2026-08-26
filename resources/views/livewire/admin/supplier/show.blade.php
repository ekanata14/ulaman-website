@php
    use App\Support\Money;
@endphp

<div>
    {{-- HEADER --}}
    <x-header title="{{ $supplier->nama }}" subtitle="{{ __('Detail Supplier') }}" separator>
        <x-slot:actions>
            <x-button label="{{ __('Kembali') }}" icon="o-arrow-left" link="{{ route('admin.suppliers') }}"
                class="btn-ghost" />
        </x-slot:actions>
    </x-header>

    {{-- PROFIL + KPI --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-6">
        {{-- Profil supplier --}}
        <x-card class="bg-base-100 shadow-sm lg:col-span-1">
            <div class="flex items-center gap-2 mb-3">
                <x-icon name="o-building-storefront" class="w-5 h-5 text-primary" />
                <span class="font-bold">{{ __('Profil') }}</span>
                @if ($supplier->is_active)
                    <span class="badge badge-success text-white badge-sm ml-auto">{{ __('Aktif') }}</span>
                @else
                    <span class="badge badge-ghost badge-sm ml-auto">{{ __('Nonaktif') }}</span>
                @endif
            </div>
            <dl class="text-sm space-y-2">
                <div class="flex justify-between gap-3">
                    <dt class="text-gray-500">{{ __('PIC') }}</dt>
                    <dd class="text-right">{{ $supplier->pic ?? '-' }}</dd>
                </div>
                <div class="flex justify-between gap-3">
                    <dt class="text-gray-500">{{ __('Telepon') }}</dt>
                    <dd class="text-right">{{ $supplier->telepon ?? '-' }}</dd>
                </div>
                <div class="flex justify-between gap-3">
                    <dt class="text-gray-500">{{ __('Alamat') }}</dt>
                    <dd class="text-right">{{ $supplier->alamat ?? '-' }}</dd>
                </div>
                @if ($supplier->catatan)
                    <div class="pt-2 border-t border-base-200">
                        <dt class="text-gray-500 mb-1">{{ __('Catatan') }}</dt>
                        <dd>{{ $supplier->catatan }}</dd>
                    </div>
                @endif
            </dl>
        </x-card>

        {{-- KPI belanja --}}
        <div class="lg:col-span-2 grid grid-cols-1 sm:grid-cols-2 gap-4">
            <x-stat title="{{ __('Total Belanja') }}" value="{{ Money::format($detail['grandTotal']) }}"
                icon="o-banknotes" class="bg-base-100 shadow-sm border-l-4 border-primary" color="text-primary" />
            <x-stat title="{{ __('Jumlah Nota') }}" value="{{ number_format($detail['notaCount'], 0, ',', '.') }}"
                icon="o-document-text" class="bg-base-100 shadow-sm border-l-4 border-secondary"
                color="text-secondary" />
        </div>
    </div>

    {{-- BARANG DARI SUPPLIER INI --}}
    <x-card class="bg-base-100 shadow-sm mb-6">
        <x-slot:title><span class="font-bold text-lg">{{ __('Barang dari supplier ini') }}</span></x-slot:title>
        <div class="overflow-x-auto">
            <table class="table table-zebra">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>{{ __('Barang') }}</th>
                        <th>{{ __('Satuan') }}</th>
                        <th class="text-right">{{ __('Total Qty') }}</th>
                        <th class="text-center">{{ __('Frekuensi') }}</th>
                        <th class="text-right">{{ __('Total Belanja') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($detail['items'] as $i => $row)
                        <tr wire:key="det-item-{{ $i }}">
                            <th>{{ $i + 1 }}</th>
                            <td>
                                @if ($row['itemId'])
                                    <a href="{{ route('admin.items.show', $row['itemId']) }}" wire:navigate
                                        class="font-medium hover:underline">{{ $row['nama'] }}</a>
                                @else
                                    <span class="font-medium">{{ $row['nama'] }}</span>
                                @endif
                            </td>
                            <td class="text-gray-500">{{ $row['unitNama'] ?? '-' }}</td>
                            <td class="text-right font-mono">{{ number_format((float) $row['totalQty'], 2, ',', '.') }}</td>
                            <td class="text-center"><span class="badge badge-ghost badge-sm">{{ $row['times'] }}×</span></td>
                            <td class="text-right font-mono font-semibold">{{ Money::format($row['totalSpend']) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-10 text-gray-500">
                                {{ __('Belum ada barang dari supplier ini.') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-card>

    {{-- NOTA TERKAIT --}}
    <x-card class="bg-base-100 shadow-sm">
        <x-slot:title><span class="font-bold text-lg">{{ __('Nota pembelian') }}</span></x-slot:title>
        <div class="overflow-x-auto">
            <table class="table table-zebra">
                <thead>
                    <tr>
                        <th>{{ __('Kode') }}</th>
                        <th>{{ __('Tanggal') }}</th>
                        <th>{{ __('No. Nota') }}</th>
                        <th class="text-right">{{ __('Grand Total') }}</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($recent as $nota)
                        <tr wire:key="nota-{{ $nota->id }}">
                            <td class="font-mono">{{ $nota->kode }}</td>
                            <td>{{ $nota->tanggal?->format('d M Y') }}</td>
                            <td class="text-gray-500">{{ $nota->nomor_nota ?? '-' }}</td>
                            <td class="text-right font-mono">{{ Money::format($nota->grand_total) }}</td>
                            <td class="text-right">
                                <x-button icon="o-pencil-square" link="{{ route('admin.purchases.edit', $nota) }}"
                                    class="btn-sm btn-ghost text-blue-500" tooltip-left="{{ __('Buka nota') }}" />
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center py-10 text-gray-500">
                                {{ __('Belum ada nota untuk supplier ini.') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $recent->links() }}</div>
    </x-card>
</div>
