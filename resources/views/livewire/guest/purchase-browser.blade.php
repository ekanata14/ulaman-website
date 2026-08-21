@php
    use App\Support\Money;
@endphp

<div class="min-h-screen">
    {{-- HEADER PUBLIK --}}
    <header class="bg-base-100 border-b border-base-300 sticky top-0 z-30">
        <div class="max-w-7xl mx-auto px-4 py-3 flex items-center justify-between gap-3">
            <div class="min-w-0">
                <h1 class="font-bold text-base md:text-lg truncate">{{ __('Ulaman Purchase Log') }}</h1>
                <p class="text-xs text-gray-500 hidden sm:block">{{ __('Renovation project purchase records') }}</p>
            </div>
            <div class="flex items-center gap-2 shrink-0">
                <livewire:language-switcher />
                <x-button label="{{ __('Admin Login') }}" icon="o-lock-closed" link="{{ route('login') }}"
                    class="btn-sm btn-ghost" responsive />
            </div>
        </div>
    </header>

    <div class="max-w-7xl mx-auto px-4 py-6">
        {{-- KARTU RINGKASAN (lazy: tabel tampil dulu) --}}
        <livewire:guest.summary-cards :filter="$filterArray" :key="'sum-' . md5(json_encode($filterArray))" />

        {{-- PANEL FILTER --}}
        <x-card class="bg-base-100 shadow-sm mb-6">
            {{-- Preset periode --}}
            <div class="flex flex-wrap gap-2 mb-4">
                <x-button label="{{ __('This Month') }}" wire:click="applyPreset('this_month')" class="btn-xs" />
                <x-button label="{{ __('Last Month') }}" wire:click="applyPreset('last_month')" class="btn-xs" />
                <x-button label="{{ __('Last 3 Months') }}" wire:click="applyPreset('last_3')" class="btn-xs" />
                <x-button label="{{ __('All') }}" wire:click="applyPreset('all')" class="btn-xs" />
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                <x-input type="date" label="{{ __('From') }}" wire:model.live="startDate" />
                <x-input type="date" label="{{ __('To') }}" wire:model.live="endDate" />
                <x-choices-offline label="{{ __('Suppliers') }}" wire:model.live="supplierIds" :options="$suppliers"
                    searchable placeholder="{{ __('All') }}" />
                <x-choices-offline label="{{ __('Categories') }}" wire:model.live="categoryIds" :options="$categories"
                    searchable placeholder="{{ __('All') }}" />
                <div class="sm:col-span-2">
                    <x-input label="{{ __('Search') }}" placeholder="{{ __('Description, note no., remark') }}..."
                        wire:model.live.debounce.400ms="search" icon="o-magnifying-glass" clearable />
                </div>
                <div class="flex items-end gap-4">
                    <x-toggle label="{{ __('With Photo') }}" wire:model.live="onlyWithPhoto" />
                    <x-button label="{{ __('Clear') }}" wire:click="clearFilters" icon="o-x-mark"
                        class="btn-ghost btn-sm text-gray-500" />
                </div>
                <div class="flex items-end gap-2">
                    <x-select label="{{ __('Per Page') }}" wire:model.live="perPage" :options="[
                        ['id' => 25, 'name' => '25'],
                        ['id' => 50, 'name' => '50'],
                        ['id' => 100, 'name' => '100'],
                    ]" />
                </div>
            </div>
        </x-card>

        {{-- TOGGLE MODE --}}
        <div class="flex items-center justify-between mb-3">
            <div class="join">
                <button wire:click="$set('viewMode', 'nota')"
                    class="btn btn-sm join-item {{ $viewMode === 'nota' ? 'btn-primary' : 'btn-ghost' }}">
                    <x-icon name="o-document-text" class="w-4 h-4" /> {{ __('Note Mode') }}
                </button>
                <button wire:click="$set('viewMode', 'item')"
                    class="btn btn-sm join-item {{ $viewMode === 'item' ? 'btn-primary' : 'btn-ghost' }}">
                    <x-icon name="o-list-bullet" class="w-4 h-4" /> {{ __('Item Mode') }}
                </button>
            </div>
            <span class="text-xs text-gray-500">{{ $rows->total() }} {{ __('results') }}</span>
        </div>

        {{-- TABEL --}}
        <x-card class="bg-base-100 shadow-sm">
            <div class="overflow-x-auto">
                @if ($viewMode === 'nota')
                    @php($grouped = $rows->getCollection()->groupBy(fn($r) => $r->tanggal->format('Y-m')))
                    <table class="table table-sm md:table-md">
                        <thead>
                            <tr>
                                <th>{{ __('Code') }}</th>
                                <th>{{ __('Date') }}</th>
                                <th>{{ __('Supplier') }}</th>
                                <th class="text-center">{{ __('Items') }}</th>
                                <th class="text-right">{{ __('Grand Total') }}</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($grouped as $ym => $group)
                                @php($monthSubtotal = $group->reduce(fn($c, $r) => bcadd($c, $r->grand_total, 2), '0.00'))
                                <tr class="bg-base-200/70">
                                    <td colspan="4" class="font-bold uppercase text-xs tracking-wide">
                                        {{ \Carbon\CarbonImmutable::createFromFormat('Y-m', $ym)->translatedFormat('F Y') }}
                                    </td>
                                    <td class="text-right font-bold">{{ Money::format($monthSubtotal) }}</td>
                                    <td></td>
                                </tr>
                                @foreach ($group as $purchase)
                                    <tr wire:key="nota-{{ $purchase->id }}" class="hover cursor-pointer"
                                        wire:click="$dispatch('open-purchase-detail', { purchaseId: {{ $purchase->id }} })">
                                        <td class="font-mono text-xs">{{ $purchase->kode }}</td>
                                        <td class="whitespace-nowrap">{{ $purchase->tanggal->format('d M Y') }}</td>
                                        <td>{{ $purchase->supplier?->nama ?? __('Lain-lain') }}</td>
                                        <td class="text-center">{{ $purchase->items_count }}</td>
                                        <td class="text-right font-semibold">{{ Money::format($purchase->grand_total) }}</td>
                                        <td class="text-right">
                                            <x-icon name="o-chevron-right" class="w-4 h-4 text-gray-400" />
                                        </td>
                                    </tr>
                                @endforeach
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center py-10 text-gray-500">{{ __('No data found.') }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                @else
                    <table class="table table-sm md:table-md">
                        <thead>
                            <tr>
                                <th>{{ __('Date') }}</th>
                                <th>{{ __('Supplier') }}</th>
                                <th>{{ __('Description') }}</th>
                                <th class="text-right">{{ __('Qty') }}</th>
                                <th class="text-right">{{ __('Unit Price') }}</th>
                                <th class="text-right">{{ __('Total') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($rows as $item)
                                <tr wire:key="item-{{ $item->id }}">
                                    <td class="whitespace-nowrap">{{ $item->purchase->tanggal->format('d M Y') }}</td>
                                    <td>{{ $item->purchase->supplier?->nama ?? __('Lain-lain') }}</td>
                                    <td>{{ $item->deskripsi }}</td>
                                    <td class="text-right">{{ rtrim(rtrim(number_format((float) $item->qty, 2, ',', '.'), '0'), ',') }}</td>
                                    <td class="text-right">{{ $item->harga_satuan !== null ? Money::format($item->harga_satuan) : '—' }}</td>
                                    <td class="text-right font-semibold">{{ Money::format($item->total) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center py-10 text-gray-500">{{ __('No data found.') }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                @endif
            </div>
            <div class="mt-4">{{ $rows->links() }}</div>
        </x-card>
    </div>

    {{-- DETAIL & LIGHTBOX --}}
    <livewire:guest.purchase-detail-modal />
    <x-photo-lightbox />
</div>
