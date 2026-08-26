@php
    use App\Support\Money;
@endphp

<div>
    {{-- HEADER --}}
    <x-header title="{{ $unit->nama }}" subtitle="{{ __('Detail Satuan') }}" separator>
        <x-slot:actions>
            <x-button label="{{ __('Kembali') }}" icon="o-arrow-left" link="{{ route('admin.units') }}"
                class="btn-ghost" />
        </x-slot:actions>
    </x-header>

    {{-- PROFIL --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
        <x-stat title="{{ __('Simbol') }}" value="{{ $unit->simbol ?? '-' }}" icon="o-tag"
            class="bg-base-100 shadow-sm border-l-4 border-secondary" color="text-secondary" />
        <x-stat title="{{ __('Jumlah Barang') }}" value="{{ number_format($items->total(), 0, ',', '.') }}"
            icon="o-cube" class="bg-base-100 shadow-sm border-l-4 border-primary" color="text-primary" />
    </div>

    {{-- BARANG MEMAKAI SATUAN INI --}}
    <x-card class="bg-base-100 shadow-sm">
        <x-slot:title><span class="font-bold text-lg">{{ __('Barang yang memakai satuan ini') }}</span></x-slot:title>
        <div class="overflow-x-auto">
            <table class="table table-zebra">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>{{ __('Nama') }}</th>
                        <th class="text-right">{{ __('Harga Terakhir') }}</th>
                        <th>{{ __('Supplier Terakhir') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($items as $barang)
                        <tr wire:key="unit-item-{{ $barang->id }}">
                            <th>{{ $loop->iteration + ($items->firstItem() - 1) }}</th>
                            <td>
                                <a href="{{ route('admin.items.show', $barang) }}" wire:navigate
                                    class="font-medium hover:underline">{{ $barang->nama }}</a>
                            </td>
                            <td class="text-right font-mono">
                                {{ $barang->harga_terakhir !== null ? Money::format($barang->harga_terakhir) : '-' }}</td>
                            <td class="text-gray-500">
                                @if ($barang->supplierTerakhir)
                                    <a href="{{ route('admin.suppliers.show', $barang->supplierTerakhir) }}"
                                        wire:navigate class="hover:underline">{{ $barang->supplierTerakhir->nama }}</a>
                                @else
                                    -
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center py-10 text-gray-500">
                                {{ __('Belum ada barang dengan satuan ini.') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $items->links() }}</div>
    </x-card>
</div>
