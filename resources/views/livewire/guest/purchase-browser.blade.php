@php
    use App\Support\Money;
@endphp

<div class="min-h-screen">
    {{-- HEADER PUBLIK --}}
    <header class="bg-base-100 border-b border-base-300 sticky top-0 z-30">
        <div class="max-w-7xl mx-auto px-4 py-3 flex items-center justify-between gap-3">
            <a href="{{ route('home') }}" wire:navigate class="shrink-0">
                <img src="{{ asset('assets/images/ulaman-logo.png') }}" alt="{{ __('Ulaman Purchase Log') }}"
                    class="h-10 md:h-12 w-auto" />
            </a>
            {{-- Di mobile kontrol pindah ke bottom navbar; language-switcher tetap
                 dirender (di-hide) agar listener set-locale-nya aktif. Admin Login
                 diletakkan paling kanan (pojok kanan). --}}
            <div class="items-center gap-2 shrink-0 hidden lg:flex">
                <button wire:click="$dispatch('open-guest-search')"
                    class="btn btn-sm btn-ghost gap-1">
                    <x-icon name="o-magnifying-glass" class="w-4 h-4" /> {{ __('Search') }}
                </button>
                <livewire:language-switcher />
                <x-button label="{{ __('Admin Login') }}" icon="o-lock-closed" link="{{ route('login') }}"
                    class="btn-sm btn-ghost" responsive />
            </div>
        </div>
    </header>

    <div class="max-w-7xl mx-auto px-4 py-6 pb-28 lg:pb-6">
        {{-- KARTU RINGKASAN (lazy: tabel tampil dulu) --}}
        <livewire:guest.summary-cards :filter="$filterArray" :key="'sum-' . md5(json_encode($filterArray))" />

        {{-- TOOLBAR: mode + jumlah hasil + tombol filter (panel dipindah ke modal) --}}
        @php($activeFilters = $this->activeFilterCount())
        <div class="flex items-center justify-between gap-2 mb-3">
            <div class="join">
                <button wire:click="$set('viewMode', 'nota')"
                    class="btn btn-sm join-item {{ $viewMode === 'nota' ? 'btn-primary' : 'btn-ghost' }}">
                    <x-icon name="o-document-text" class="w-4 h-4" />
                    <span class="hidden sm:inline">{{ __('Item Mode') }}</span>
                </button>
                <button wire:click="$set('viewMode', 'item')"
                    class="btn btn-sm join-item {{ $viewMode === 'item' ? 'btn-primary' : 'btn-ghost' }}">
                    <x-icon name="o-list-bullet" class="w-4 h-4" />
                    <span class="hidden sm:inline">{{ __('Item Mode') }}</span>
                </button>
                <button wire:click="$set('viewMode', 'spreadsheet')"
                    class="btn btn-sm join-item {{ $viewMode === 'spreadsheet' ? 'btn-primary' : 'btn-ghost' }}">
                    <x-icon name="o-table-cells" class="w-4 h-4" />
                    <span class="hidden sm:inline">{{ __('Spreadsheet') }}</span>
                </button>
            </div>

            <div class="flex items-center gap-2 shrink-0">
                <span class="text-xs text-gray-500 whitespace-nowrap">
                    {{ number_format($rows->total(), 0, ',', '.') }} {{ __('results') }}
                </span>
                <button wire:click="$set('showFilters', true)" class="btn btn-sm btn-outline gap-1 hidden lg:inline-flex">
                    <x-icon name="o-adjustments-horizontal" class="w-4 h-4" />
                    <span>{{ __('Filters') }}</span>
                    @if ($activeFilters > 0)
                        <span class="badge badge-primary badge-sm text-white">{{ $activeFilters }}</span>
                    @endif
                </button>
            </div>
        </div>

        {{-- MODAL FILTER --}}
        <x-modal wire:model="showFilters" title="{{ __('Filters') }}" separator box-class="max-w-2xl">
            {{-- Preset periode --}}
            <div class="flex flex-wrap gap-2 mb-4">
                <x-button label="{{ __('This Month') }}" wire:click="applyPreset('this_month')" class="btn-xs" />
                <x-button label="{{ __('Last Month') }}" wire:click="applyPreset('last_month')" class="btn-xs" />
                <x-button label="{{ __('Last 3 Months') }}" wire:click="applyPreset('last_3')" class="btn-xs" />
                <x-button label="{{ __('All') }}" wire:click="applyPreset('all')" class="btn-xs" />
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <x-input type="date" label="{{ __('From') }}" wire:model.live="startDate" />
                <x-input type="date" label="{{ __('To') }}" wire:model.live="endDate" />
                <x-choices-offline label="{{ __('Suppliers') }}" wire:model.live="supplierIds" :options="$suppliers"
                    searchable placeholder="{{ __('All') }}" />
                <div class="sm:col-span-2">
                    <x-input label="{{ __('Search') }}" placeholder="{{ __('Description, item no., remark') }}..."
                        wire:model.live.debounce.400ms="search" icon="o-magnifying-glass" clearable />
                </div>
                <x-toggle label="{{ __('With Photo') }}" wire:model.live="onlyWithPhoto" class="mt-2" />
                <x-select label="{{ __('Per Page') }}" wire:model.live="perPage" :options="[
                    ['id' => 25, 'name' => '25'],
                    ['id' => 50, 'name' => '50'],
                    ['id' => 100, 'name' => '100'],
                ]" />
            </div>

            <x-slot:actions>
                <x-button label="{{ __('Reset Filter') }}" wire:click="clearFilters" icon="o-x-mark"
                    class="btn-ghost text-gray-500" />
                <x-button label="{{ __('Apply') }}" icon="o-check" class="btn-primary"
                    @click="$wire.showFilters = false" />
            </x-slot:actions>
        </x-modal>

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
                @elseif ($viewMode === 'spreadsheet')
                    {{-- MODE SPREADSHEET (read-only): baris per item, grup per nota --}}
                    <table class="table table-sm md:table-md table-zebra">
                        <thead>
                            <tr>
                                <th>{{ __('Date') }}</th>
                                <th>{{ __('Supplier') }}</th>
                                <th>{{ __('Description') }}</th>
                                <th class="text-right">{{ __('Qty') }}</th>
                                <th class="text-right">{{ __('Item Price') }}</th>
                                <th class="text-right">{{ __('Total Amount') }}</th>
                                <th>{{ __('Remark') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($rows as $nota)
                                @foreach ($nota->items as $item)
                                    <tr wire:key="ss-{{ $item->id }}"
                                        class="{{ $loop->first ? 'border-t-2 border-base-300' : '' }}">
                                        <td class="whitespace-nowrap">{{ $loop->first ? $nota->tanggal->format('d M Y') : '' }}</td>
                                        <td>{{ $loop->first ? ($nota->supplier?->nama ?? __('Lain-lain')) : '' }}</td>
                                        <td>{{ $item->deskripsi }}</td>
                                        <td class="text-right">{{ rtrim(rtrim(number_format((float) $item->qty, 2, ',', '.'), '0'), ',') }}</td>
                                        <td class="text-right">{{ $item->harga_satuan !== null ? Money::format($item->harga_satuan) : '—' }}</td>
                                        <td class="text-right font-semibold">{{ Money::format($item->net_total) }}</td>
                                        <td class="text-gray-500">{{ $item->remark }}</td>
                                    </tr>
                                @endforeach
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center py-10 text-gray-500">{{ __('No data found.') }}</td>
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

    {{-- OVERLAY PENCARIAN GLOBAL --}}
    <livewire:guest.global-search />

    {{-- DETAIL & LIGHTBOX --}}
    <livewire:guest.purchase-detail-modal />
    <x-photo-lightbox />

    {{-- MOBILE BOTTOM NAVBAR (hanya mobile/tablet) --}}
    <nav class="lg:hidden fixed inset-x-0 bottom-0 z-40 bg-base-100 border-t border-base-300 shadow-[0_-2px_10px_rgba(0,0,0,0.05)]"
        style="padding-bottom: env(safe-area-inset-bottom)">
        <div class="grid grid-cols-5 items-end">
            {{-- HOME --}}
            <button type="button" @click="window.scrollTo({ top: 0, behavior: 'smooth' })"
                class="flex flex-col items-center gap-0.5 py-2 text-gray-500 active:text-primary">
                <x-icon name="o-home" class="w-5 h-5" />
                <span class="text-[10px]">{{ __('Home') }}</span>
            </button>

            {{-- FILTER --}}
            <button type="button" wire:click="$set('showFilters', true)"
                class="relative flex flex-col items-center gap-0.5 py-2 text-gray-500 active:text-primary">
                <x-icon name="o-adjustments-horizontal" class="w-5 h-5" />
                <span class="text-[10px]">{{ __('Filters') }}</span>
                @if ($activeFilters > 0)
                    <span class="absolute top-1 right-1/4 badge badge-primary badge-xs text-white">{{ $activeFilters }}</span>
                @endif
            </button>

            {{-- SEARCH (tombol tengah menonjol) --}}
            <div class="flex justify-center">
                <button type="button" wire:click="$dispatch('open-guest-search')"
                    class="-mt-6 w-14 h-14 rounded-full bg-primary text-primary-content shadow-lg flex items-center justify-center active:scale-95 transition">
                    <x-icon name="o-magnifying-glass" class="w-6 h-6" />
                </button>
            </div>

            {{-- BAHASA (dropdown ke atas; men-dispatch set-locale ke LanguageSwitcher) --}}
            <div class="dropdown dropdown-top dropdown-end flex justify-center">
                <button type="button" tabindex="0"
                    class="flex flex-col items-center gap-0.5 py-2 text-gray-500 active:text-primary">
                    <span class="text-lg leading-none">{{ app()->getLocale() === 'id' ? '🇮🇩' : '🇺🇸' }}</span>
                    <span class="text-[10px]">{{ __('Language') }}</span>
                </button>
                <ul tabindex="0"
                    class="dropdown-content menu bg-base-100 rounded-box z-50 w-40 p-2 shadow mb-2 border border-base-300">
                    <li><a wire:click="$dispatch('set-locale', { locale: 'en' })"
                            class="{{ app()->getLocale() === 'en' ? 'active font-bold' : '' }}">English 🇺🇸</a></li>
                    <li><a wire:click="$dispatch('set-locale', { locale: 'id' })"
                            class="{{ app()->getLocale() === 'id' ? 'active font-bold' : '' }}">Indonesia 🇮🇩</a></li>
                </ul>
            </div>

            {{-- LOGIN --}}
            <a href="{{ route('login') }}" wire:navigate
                class="flex flex-col items-center gap-0.5 py-2 text-gray-500 active:text-primary">
                <x-icon name="o-lock-closed" class="w-5 h-5" />
                <span class="text-[10px]">{{ __('Login') }}</span>
            </a>
        </div>
    </nav>
</div>
