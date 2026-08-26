@php use App\Support\Money; @endphp

<div>
    <x-modal wire:model="open" title="{{ __('Search') }}" separator box-class="max-w-lg"
        @keydown.escape.window="$wire.open = false">

        {{-- INPUT --}}
        <x-input wire:model.live.debounce.300ms="q" icon="o-magnifying-glass" autofocus
            placeholder="{{ __('Search suppliers, items') }}..." clearable />

        <div class="mt-4 max-h-[60vh] overflow-y-auto -mx-1 px-1 space-y-5">
            @if (! $hasQuery)
                <div class="py-10 text-center text-gray-400 text-sm">
                    {{ __('Type at least 2 characters to search.') }}
                </div>
            @elseif ($results['suppliers']->isEmpty() && $results['items']->isEmpty() && $results['notes']->isEmpty())
                <div class="py-10 text-center text-gray-400 text-sm">
                    {{ __('No data found.') }}
                </div>
            @else
                {{-- SUPPLIERS --}}
                @if ($results['suppliers']->isNotEmpty())
                    <div>
                        <div class="text-[11px] font-bold uppercase tracking-wide text-gray-400 mb-1 px-2">
                            {{ __('Suppliers') }}</div>
                        @foreach ($results['suppliers'] as $supplier)
                            <button type="button" wire:key="s-{{ $supplier->id }}"
                                wire:click="pickSupplier({{ $supplier->id }})"
                                class="w-full flex items-center gap-3 p-2 rounded-lg hover:bg-base-200 text-left">
                                <x-icon name="o-building-storefront" class="w-5 h-5 text-secondary shrink-0" />
                                <div class="min-w-0">
                                    <div class="font-medium truncate">{{ $supplier->nama }}</div>
                                    @if ($supplier->pic || $supplier->telepon)
                                        <div class="text-xs text-gray-500 truncate">
                                            {{ collect([$supplier->pic, $supplier->telepon])->filter()->implode(' · ') }}
                                        </div>
                                    @endif
                                </div>
                            </button>
                        @endforeach
                    </div>
                @endif

                {{-- ITEMS --}}
                @if ($results['items']->isNotEmpty())
                    <div>
                        <div class="text-[11px] font-bold uppercase tracking-wide text-gray-400 mb-1 px-2">
                            {{ __('Items') }}</div>
                        @foreach ($results['items'] as $item)
                            <button type="button" wire:key="i-{{ $item->id }}"
                                wire:click="pickItem(@js($item->nama))"
                                class="w-full flex items-center gap-3 p-2 rounded-lg hover:bg-base-200 text-left">
                                <x-icon name="o-cube" class="w-5 h-5 text-success shrink-0" />
                                <div class="min-w-0">
                                    <div class="font-medium truncate">{{ $item->nama }}</div>
                                    <div class="text-xs text-gray-500 truncate">
                                        {{ $item->unit?->nama ?: __('Item') }}
                                    </div>
                                </div>
                            </button>
                        @endforeach
                    </div>
                @endif

                {{-- NOTES --}}
                @if ($results['notes']->isNotEmpty())
                    <div>
                        <div class="text-[11px] font-bold uppercase tracking-wide text-gray-400 mb-1 px-2">
                            {{ __('Purchase Items') }}</div>
                        @foreach ($results['notes'] as $note)
                            <button type="button" wire:key="n-{{ $note->id }}"
                                wire:click="pickNote({{ $note->id }})"
                                class="w-full flex items-center gap-3 p-2 rounded-lg hover:bg-base-200 text-left">
                                <x-icon name="o-document-text" class="w-5 h-5 text-info shrink-0" />
                                <div class="min-w-0 flex-1">
                                    <div class="font-mono text-sm font-semibold truncate">{{ $note->kode }}</div>
                                    <div class="text-xs text-gray-500 truncate">
                                        {{ $note->tanggal->format('d M Y') }} ·
                                        {{ $note->supplier?->nama ?? __('Lain-lain') }}
                                    </div>
                                </div>
                                <span class="text-sm font-semibold shrink-0">{{ Money::format($note->grand_total) }}</span>
                            </button>
                        @endforeach
                    </div>
                @endif
            @endif
        </div>
    </x-modal>
</div>
