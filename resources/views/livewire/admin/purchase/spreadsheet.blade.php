@php
    use App\Support\Money;
@endphp

<div>
    @php($periodOptions = [
        ['id' => 'all', 'name' => __('All Data')],
        ['id' => 'month', 'name' => __('By Month')],
        ['id' => 'year', 'name' => __('By Year')],
        ['id' => 'range', 'name' => __('By Date Range')],
    ])
    @php($yearOptions = collect($years)->map(fn ($y) => ['id' => $y, 'name' => (string) $y])->all())
    @php($monthOptions = collect(range(1, 12))->map(fn ($m) => ['id' => $m, 'name' => \Carbon\CarbonImmutable::create(2000, $m, 1)->translatedFormat('F')])->all())

    @if ($fullscreen)
        {{-- NAVBAR FLOATING (fullscreen): menu + filter inline + input + kontrol --}}
        <div class="sticky top-0 z-50 bg-base-100/95 backdrop-blur border-b border-base-300 shadow-sm">
            <div class="flex flex-wrap items-center gap-2 px-3 py-2">
                {{-- Menu dropdown (pengganti sidebar) --}}
                <div class="dropdown">
                    <div tabindex="0" role="button" class="btn btn-sm btn-primary gap-1" title="{{ __('Menu') }}">
                        <x-icon name="o-bars-3" class="w-5 h-5" />
                        <span class="hidden sm:inline">{{ __('Menu') }}</span>
                    </div>
                    <div tabindex="0"
                        class="dropdown-content z-[70] mt-2 w-72 max-h-[80vh] overflow-y-auto bg-base-100 rounded-box shadow-2xl border border-base-300 p-2">
                        <div class="px-3 pt-2 pb-1 flex items-center gap-2">
                            <x-icon name="o-building-storefront" class="w-6 h-6 text-primary" />
                            <span class="font-bold">{{ config('app.name') }}</span>
                        </div>
                        @include('partials.admin-menu')
                        <div class="border-t border-base-300 my-1"></div>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit"
                                class="w-full text-left px-4 py-2 text-sm text-error hover:bg-base-200 rounded flex items-center gap-2">
                                <x-icon name="o-power" class="w-4 h-4" /> {{ __('Logout') }}
                            </button>
                        </form>
                    </div>
                </div>

                {{-- Filter inline ringkas --}}
                <x-input placeholder="{{ __('Search description / code') }}..." wire:model.live.debounce="search"
                    icon="o-magnifying-glass" class="w-56" />
                <x-select placeholder="{{ __('All Suppliers') }}" wire:model.live="supplierId" :options="$suppliers"
                    class="w-44" />
                <x-select placeholder="{{ __('All Categories') }}" wire:model.live="categoryId" :options="$categories"
                    class="w-40" />
                <x-select placeholder="{{ __('All Status') }}" wire:model.live="status" :options="$statusOptions"
                    class="w-36" />
                <x-select wire:model.live="periodMode" :options="$periodOptions" class="w-32" />
                @if ($periodMode === 'month' || $periodMode === 'year')
                    <x-select wire:model.live="year" :options="$yearOptions" class="w-28" />
                @endif
                @if ($periodMode === 'month')
                    <x-select wire:model.live="month" :options="$monthOptions" class="w-32" />
                @endif
                @if ($periodMode === 'range')
                    <x-input type="date" wire:model.live="dari" class="w-40" />
                    <x-input type="date" wire:model.live="sampai" class="w-40" />
                @endif
                <x-button icon="o-x-mark" wire:click="clearFilters" class="btn-ghost btn-sm btn-square"
                    tooltip="{{ __('Clear') }}" />

                {{-- Kanan: info + input + kontrol --}}
                <div class="ml-auto flex items-center gap-2">
                    <span class="text-xs text-gray-500 hidden xl:inline whitespace-nowrap">
                        {{ __('Showing') }} {{ $notas->count() }} / {{ number_format($totalNotas, 0, ',', '.') }}
                        {{ __('notes') }}
                    </span>
                    <x-button label="{{ __('Add Nota') }}" icon="o-plus" class="btn-primary btn-sm"
                        wire:click="openNotaModal" />
                    <livewire:language-switcher :key="'ls-focus'" />
                    <x-theme-toggle class="btn btn-circle btn-ghost btn-sm" />
                    <a href="{{ route('admin.spreadsheet') }}" wire:navigate class="btn btn-sm btn-ghost gap-1"
                        title="{{ __('Exit Fullscreen') }}">
                        <x-icon name="o-arrows-pointing-in" class="w-5 h-5" />
                        <span class="hidden md:inline">{{ __('Exit Fullscreen') }}</span>
                    </a>
                </div>
            </div>
        </div>
    @else
        <x-header title="{{ __('Spreadsheet') }}" subtitle="{{ __('Flat, editable view of all purchase items') }}"
            separator progress-indicator>
            <x-slot:actions>
                <x-button label="{{ __('Fullscreen') }}" icon="o-arrows-pointing-out"
                    link="{{ route('admin.spreadsheet', ['focus' => 1]) }}" wire:navigate class="btn-ghost" />
                <x-button label="{{ __('Add Nota') }}" icon="o-plus" class="btn-primary" wire:click="openNotaModal" />
            </x-slot:actions>
        </x-header>

        {{-- FILTER BAR --}}
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-3 mb-3 items-end">
            <x-input placeholder="{{ __('Search description / code') }}..." wire:model.live.debounce="search"
                icon="o-magnifying-glass" />
            <x-select placeholder="{{ __('All Suppliers') }}" wire:model.live="supplierId" :options="$suppliers"
                icon="o-building-storefront" />
            <x-select placeholder="{{ __('All Categories') }}" wire:model.live="categoryId" :options="$categories"
                icon="o-tag" />
            <x-select placeholder="{{ __('All Status') }}" wire:model.live="status" :options="$statusOptions"
                icon="o-flag" />
        </div>

        {{-- PERIOD MODE --}}
        <div class="flex flex-wrap items-end gap-3 mb-4">
            <x-select label="{{ __('Period') }}" wire:model.live="periodMode" :options="$periodOptions" class="w-40" />

            @if ($periodMode === 'month' || $periodMode === 'year')
                <x-select label="{{ __('Year') }}" wire:model.live="year" :options="$yearOptions" class="w-32" />
            @endif
            @if ($periodMode === 'month')
                <x-select label="{{ __('Month') }}" wire:model.live="month" :options="$monthOptions" class="w-40" />
            @endif
            @if ($periodMode === 'range')
                <x-input type="date" label="{{ __('From') }}" wire:model.live="dari" />
                <x-input type="date" label="{{ __('To') }}" wire:model.live="sampai" />
            @endif

            <x-button label="{{ __('Clear') }}" wire:click="clearFilters" icon="o-x-mark"
                class="btn-ghost text-gray-500" />

            <div class="ml-auto text-sm text-gray-500 self-center">
                {{ __('Showing') }} {{ $notas->count() }} / {{ number_format($totalNotas, 0, ',', '.') }}
                {{ __('notes') }}
            </div>
        </div>
    @endif

    {{-- SPREADSHEET --}}
    <div x-data="{ showTop: false }" class="{{ $fullscreen ? 'px-3 pt-3' : '' }}">
    <x-card class="bg-base-100 shadow-sm p-0">
        <div class="ss-scroll overflow-auto {{ $fullscreen ? 'max-h-[calc(100vh-96px)]' : 'max-h-[70vh]' }}"
            x-ref="scroll" @scroll="showTop = $refs.scroll.scrollTop > 400">
            <table class="table table-sm table-zebra">
                <thead class="sticky top-0 bg-base-200 z-30">
                    <tr>
                        <th class="w-32">{{ __('Date') }}</th>
                        <th class="w-48">{{ __('Supplier') }}</th>
                        <th class="min-w-[220px]">{{ __('Description') }}</th>
                        <th class="w-20 text-right">{{ __('Qty') }}</th>
                        <th class="w-32 text-right">{{ __('Item Price') }}</th>
                        <th class="w-32 text-right">{{ __('Total Amount') }}</th>
                        <th class="w-40">{{ __('Remark') }}</th>
                        <th class="w-24"></th>
                    </tr>
                </thead>
                <tbody>
                    @php($grouped = $notas->groupBy(fn ($n) => $n->tanggal->format('Y-m')))
                    @forelse ($grouped as $ym => $group)
                        {{-- BLOK BULAN (sticky di bawah header kolom) --}}
                        @php($monthSubtotal = $group->reduce(fn ($c, $n) => bcadd($c, (string) $n->grand_total, 2), '0.00'))
                        <tr wire:key="ss-month-{{ $ym }}">
                            <td colspan="8"
                                class="sticky top-[33px] z-20 bg-base-200 border-y border-primary/30 px-3 py-2">
                                <div class="flex items-center justify-between">
                                    <span class="flex items-center gap-2 font-bold uppercase text-xs tracking-wide text-primary">
                                        <x-icon name="o-calendar" class="w-4 h-4" />
                                        {{ \Carbon\CarbonImmutable::createFromFormat('Y-m', $ym)->translatedFormat('F Y') }}
                                        <span class="badge badge-ghost badge-sm normal-case">{{ $group->count() }} {{ __('notes') }}</span>
                                    </span>
                                    <span class="font-mono text-sm font-bold text-primary">{{ Money::format($monthSubtotal) }}</span>
                                </div>
                            </td>
                        </tr>
                        @foreach ($group as $nota)
                            @foreach ($nota->items as $item)
                            @php($isFirst = $loop->first)
                            @php($hasAdj = $item->diskon_tipe->value !== 'NONE'
                                || bccomp((string) $item->alokasi_diskon_bundle, '0', 2) === 1
                                || bccomp((string) $item->alokasi_diskon_nota, '0', 2) === 1)
                            <tr wire:key="ss-item-{{ $item->id }}" class="{{ $isFirst ? 'border-t-2 border-base-300' : '' }}">
                                {{-- DATE (nota-level, first row only) --}}
                                <td class="align-top">
                                    @if ($isFirst)
                                        <input type="date" value="{{ $nota->tanggal->format('Y-m-d') }}"
                                            @change="$wire.updateNotaField({{ $nota->id }}, 'tanggal', $event.target.value)"
                                            class="input input-ghost input-sm w-full px-1" />
                                    @endif
                                </td>
                                {{-- SUPPLIER (nota-level, first row only) --}}
                                <td class="align-top">
                                    @if ($isFirst)
                                        <select @change="$wire.updateNotaField({{ $nota->id }}, 'supplierId', $event.target.value)"
                                            class="select select-ghost select-sm w-full px-1">
                                            <option value="" @selected($nota->supplier_id === null)>{{ __('Lain-lain') }}</option>
                                            @foreach ($suppliers as $s)
                                                <option value="{{ $s['id'] }}" @selected($nota->supplier_id === $s['id'])>{{ $s['name'] }}</option>
                                            @endforeach
                                        </select>
                                    @endif
                                </td>
                                {{-- DESCRIPTION --}}
                                <td class="align-top">
                                    <input type="text" value="{{ $item->deskripsi }}"
                                        @change="$wire.updateItemField({{ $nota->id }}, {{ $item->id }}, 'deskripsi', $event.target.value)"
                                        class="input input-ghost input-sm w-full px-1" />
                                </td>
                                {{-- QTY --}}
                                <td class="align-top">
                                    <input type="number" step="0.01" value="{{ 0 + $item->qty }}"
                                        @change="$wire.updateItemField({{ $nota->id }}, {{ $item->id }}, 'qty', $event.target.value)"
                                        class="input input-ghost input-sm w-full px-1 text-right" />
                                </td>
                                {{-- ITEM PRICE (format ribuan; nilai mentah dikirim ke server) --}}
                                <td class="align-top"
                                    x-data="moneyInput(@js($item->harga_satuan !== null ? (string) (0 + $item->harga_satuan) : ''))">
                                    <input type="text" inputmode="decimal" :value="display"
                                        @input="display = format(unmask($event.target.value))"
                                        @change="$wire.updateItemField({{ $nota->id }}, {{ $item->id }}, 'hargaSatuan', unmask($event.target.value))"
                                        class="input input-ghost input-sm w-full px-1 text-right" />
                                </td>
                                {{-- TOTAL (read-only) --}}
                                <td class="align-top text-right font-mono pt-2 whitespace-nowrap">
                                    {{ Money::format((string) $item->net_total) }}
                                    @if ($hasAdj)
                                        <a href="{{ route('admin.purchases.edit', $nota->id) }}" wire:navigate
                                            title="{{ __('Has discount/bundle — edit in full editor') }}"
                                            class="ml-1 text-amber-500">★</a>
                                    @endif
                                </td>
                                {{-- REMARK --}}
                                <td class="align-top">
                                    <input type="text" value="{{ $item->remark }}"
                                        @change="$wire.updateItemField({{ $nota->id }}, {{ $item->id }}, 'remark', $event.target.value)"
                                        class="input input-ghost input-sm w-full px-1" />
                                </td>
                                {{-- ACTIONS --}}
                                <td class="align-top text-right whitespace-nowrap">
                                    @if ($isFirst)
                                        <x-button icon="o-plus" wire:click="addItemRow({{ $nota->id }})"
                                            class="btn-xs btn-ghost text-primary" tooltip-left="{{ __('Add item') }}" />
                                        <x-button icon="o-pencil-square" link="{{ route('admin.purchases.edit', $nota->id) }}"
                                            class="btn-xs btn-ghost text-blue-500" tooltip-left="{{ __('Full editor') }}" />
                                        <x-button icon="o-trash" wire:click="confirmDeleteNota({{ $nota->id }})"
                                            class="btn-xs btn-ghost text-red-500" tooltip-left="{{ __('Delete note') }}" />
                                    @else
                                        <x-button icon="o-x-mark" wire:click="confirmDeleteItem({{ $nota->id }}, {{ $item->id }})"
                                            class="btn-xs btn-ghost text-red-400" tooltip-left="{{ __('Remove item') }}" />
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        @endforeach
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-10 text-gray-500">
                                {{ __('No purchase notes found.') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            {{-- INFINITE SCROLL (sentinel di dalam kontainer scroll) --}}
            @if ($hasMore)
                <div class="p-4 text-center" x-data
                    x-init="new IntersectionObserver((entries) => entries[0].isIntersecting && $wire.loadMore(), { root: $el.closest('.ss-scroll') }).observe($el)">
                    <x-button label="{{ __('Load more') }}" wire:click="loadMore" class="btn-ghost btn-sm"
                        spinner="loadMore" />
                    <div wire:loading wire:target="loadMore" class="text-sm text-gray-400 mt-1">
                        <x-loading class="loading-sm" /> {{ __('Loading...') }}
                    </div>
                </div>
            @endif
        </div>
    </x-card>

    {{-- TOMBOL KEMBALI KE ATAS (tengah bawah) --}}
    <button type="button" x-show="showTop" x-transition style="display:none"
        @click="$refs.scroll.scrollTo({ top: 0, behavior: 'smooth' })"
        class="fixed bottom-6 left-1/2 -translate-x-1/2 z-[80] btn btn-circle btn-primary shadow-xl"
        title="{{ __('Back to top') }}">
        <x-icon name="o-arrow-up" class="w-5 h-5" />
    </button>
    </div>

    {{-- ADD NOTA MODAL --}}
    <x-modal wire:model="notaModalOpen" title="{{ __('Add Nota') }}" separator>
        <x-form wire:submit="confirmSaveNota">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                <x-input type="date" label="{{ __('Date') }}" wire:model="ndTanggal" />
                <x-select label="{{ __('Supplier') }}" wire:model="ndSupplierId" :options="$suppliers"
                    placeholder="{{ __('Lain-lain') }}" />
            </div>
            <x-input label="{{ __('Description') }}" wire:model="ndDeskripsi" />
            <div class="grid grid-cols-2 gap-3">
                <x-input type="number" step="0.01" label="{{ __('Qty') }}" wire:model="ndQty" />
                <x-money-input label="{{ __('Item Price') }}" prefix="Rp" wire-model="ndHarga" :value="$ndHarga" />
            </div>
            <x-slot:actions>
                <x-button label="{{ __('Batal') }}" @click="$wire.notaModalOpen = false" />
                <x-button label="{{ __('Simpan') }}" type="submit" class="btn-primary" spinner="confirmSaveNota" />
            </x-slot:actions>
        </x-form>
    </x-modal>

    {{-- MODAL KONFIRMASI GENERIK --}}
    <x-modal-confirm wire:model="confirmModalOpen" :title="$confirmTitle" :text="$confirmMessage"
        :confirm-text="$confirmButton" :icon="$confirmIcon" :danger="$confirmDanger" method="confirmProceed" />
</div>
