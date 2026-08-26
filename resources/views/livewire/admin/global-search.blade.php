@php use App\Support\Money; @endphp

<div>
    <x-header :title="__('Global Search')" separator>
        <x-slot:subtitle>
            @if ($hasQuery)
                <span class="flex items-center gap-2 text-gray-500">
                    <x-icon name="o-magnifying-glass" class="w-4 h-4" />
                    {{ __('Results for') }}: <span class="font-bold text-base-content">"{{ trim($search) }}"</span>
                    <span class="text-gray-400">· {{ $total }}</span>
                </span>
            @endif
        </x-slot:subtitle>
    </x-header>

    {{-- KOTAK PENCARIAN --}}
    <x-card class="bg-base-100 shadow-sm mb-6">
        <x-input icon="o-magnifying-glass" wire:model.live.debounce.300ms="search" autofocus clearable
            placeholder="{{ __('Search suppliers, items, purchases, users') }}..." />
    </x-card>

    @if (! $hasQuery)
        <div class="text-center py-20 text-gray-400">
            <x-icon name="o-magnifying-glass" class="w-16 h-16 mx-auto mb-4 opacity-20" />
            <p>{{ __('Type at least 2 characters to search.') }}</p>
        </div>
    @elseif ($total === 0)
        <div class="text-center py-20 text-gray-400">
            <x-icon name="o-face-frown" class="w-16 h-16 mx-auto mb-4 opacity-20" />
            <p>{{ __('No data found.') }}</p>
        </div>
    @else
        <div class="space-y-6">
            {{-- ITEM PEMBELIAN --}}
            @if ($results['purchases']->isNotEmpty())
                <x-card class="bg-base-100 shadow-sm">
                    <h2 class="font-bold flex items-center gap-2 border-b border-base-200 pb-2 mb-3">
                        <x-icon name="o-document-text" class="w-5 h-5 text-primary" />
                        {{ __('Purchase Items') }} ({{ $results['purchases']->count() }})
                    </h2>
                    <div class="divide-y divide-base-200">
                        @foreach ($results['purchases'] as $p)
                            <a href="{{ route('admin.purchases.edit', $p->id) }}" wire:navigate wire:key="p-{{ $p->id }}"
                                class="flex items-center gap-3 p-2 rounded-lg hover:bg-base-200 transition">
                                <x-icon name="o-document-text" class="w-5 h-5 text-info shrink-0" />
                                <div class="min-w-0 flex-1">
                                    <div class="font-mono text-sm font-semibold truncate">
                                        {{ $p->kode }}
                                        @if ($p->nomor_nota)
                                            <span class="text-gray-400">· {{ $p->nomor_nota }}</span>
                                        @endif
                                        @if ($p->status->value !== 'final')
                                            <span class="badge badge-xs badge-ghost ml-1">{{ $p->status->label() }}</span>
                                        @endif
                                    </div>
                                    <div class="text-xs text-gray-500 truncate">
                                        {{ $p->tanggal->format('d M Y') }} · {{ $p->supplier?->nama ?? __('Lain-lain') }}
                                    </div>
                                </div>
                                <span class="text-sm font-semibold shrink-0">{{ Money::format($p->grand_total) }}</span>
                            </a>
                        @endforeach
                    </div>
                </x-card>
            @endif

            {{-- SUPPLIER --}}
            @if ($results['suppliers']->isNotEmpty())
                <x-card class="bg-base-100 shadow-sm">
                    <h2 class="font-bold flex items-center gap-2 border-b border-base-200 pb-2 mb-3">
                        <x-icon name="o-building-storefront" class="w-5 h-5 text-primary" />
                        {{ __('Suppliers') }} ({{ $results['suppliers']->count() }})
                    </h2>
                    <div class="divide-y divide-base-200">
                        @foreach ($results['suppliers'] as $s)
                            <a href="{{ route('admin.suppliers', ['search' => $s->nama]) }}" wire:navigate
                                wire:key="s-{{ $s->id }}"
                                class="flex items-center gap-3 p-2 rounded-lg hover:bg-base-200 transition {{ $s->is_active ? '' : 'opacity-50' }}">
                                <x-icon name="o-building-storefront" class="w-5 h-5 text-secondary shrink-0" />
                                <div class="min-w-0 flex-1">
                                    <div class="font-medium truncate">{{ $s->nama }}
                                        @unless ($s->is_active)
                                            <span class="badge badge-xs badge-ghost ml-1">{{ __('Inactive') }}</span>
                                        @endunless
                                    </div>
                                    @if ($s->pic || $s->telepon)
                                        <div class="text-xs text-gray-500 truncate">
                                            {{ collect([$s->pic, $s->telepon])->filter()->implode(' · ') }}
                                        </div>
                                    @endif
                                </div>
                            </a>
                        @endforeach
                    </div>
                </x-card>
            @endif

            {{-- BARANG --}}
            @if ($results['items']->isNotEmpty())
                <x-card class="bg-base-100 shadow-sm">
                    <h2 class="font-bold flex items-center gap-2 border-b border-base-200 pb-2 mb-3">
                        <x-icon name="o-cube" class="w-5 h-5 text-primary" />
                        {{ __('Items') }} ({{ $results['items']->count() }})
                    </h2>
                    <div class="divide-y divide-base-200">
                        @foreach ($results['items'] as $i)
                            <a href="{{ route('admin.items', ['search' => $i->nama]) }}" wire:navigate
                                wire:key="i-{{ $i->id }}"
                                class="flex items-center gap-3 p-2 rounded-lg hover:bg-base-200 transition">
                                <x-icon name="o-cube" class="w-5 h-5 text-success shrink-0" />
                                <div class="min-w-0 flex-1">
                                    <div class="font-medium truncate">{{ $i->nama }}</div>
                                    <div class="text-xs text-gray-500 truncate">{{ $i->unit?->nama ?: '—' }}</div>
                                </div>
                                @if ($i->harga_terakhir !== null)
                                    <span class="text-xs text-gray-500 shrink-0">{{ Money::format($i->harga_terakhir) }}</span>
                                @endif
                            </a>
                        @endforeach
                    </div>
                </x-card>
            @endif

            {{-- SATUAN --}}
            @if ($results['units']->isNotEmpty())
                <x-card class="bg-base-100 shadow-sm">
                    <h2 class="font-bold flex items-center gap-2 border-b border-base-200 pb-2 mb-3">
                        <x-icon name="o-scale" class="w-5 h-5 text-primary" />
                        {{ __('Units') }} ({{ $results['units']->count() }})
                    </h2>
                    <div class="divide-y divide-base-200">
                        @foreach ($results['units'] as $u)
                            <a href="{{ route('admin.units', ['search' => $u->nama]) }}" wire:navigate
                                wire:key="u-{{ $u->id }}"
                                class="flex items-center gap-3 p-2 rounded-lg hover:bg-base-200 transition">
                                <x-icon name="o-scale" class="w-5 h-5 text-warning shrink-0" />
                                <div class="min-w-0 flex-1">
                                    <div class="font-medium truncate">{{ $u->nama }}</div>
                                    @if ($u->simbol)
                                        <div class="text-xs text-gray-500 truncate">{{ $u->simbol }}</div>
                                    @endif
                                </div>
                            </a>
                        @endforeach
                    </div>
                </x-card>
            @endif

            {{-- USER --}}
            <x-card class="bg-base-100 shadow-sm">
                <div class="flex justify-between items-center border-b border-base-200 pb-2 mb-3">
                    <h2 class="font-bold flex items-center gap-2">
                        <x-icon name="o-users" class="w-5 h-5 text-primary" />
                        {{ __('Users') }} ({{ $results['users']->count() }})
                    </h2>
                    <select wire:model.live="filterUserRole" class="select select-xs select-bordered">
                        <option value="">{{ __('All Roles') }}</option>
                        <option value="super_admin">{{ __('Super Admin') }}</option>
                        <option value="admin">{{ __('Admin') }}</option>
                    </select>
                </div>
                @forelse ($results['users'] as $usr)
                    <a href="{{ route('admin.users', ['search' => $usr->name]) }}" wire:navigate
                        wire:key="usr-{{ $usr->id }}"
                        class="flex items-center gap-3 p-2 rounded-lg hover:bg-base-200 transition">
                        <div class="w-9 h-9 rounded-full bg-base-200 flex items-center justify-center text-xs font-bold shrink-0 uppercase">
                            {{ mb_substr($usr->name, 0, 2) }}
                        </div>
                        <div class="min-w-0 flex-1">
                            <div class="font-medium truncate">{{ $usr->name }}</div>
                            <div class="text-xs text-gray-500 truncate">{{ $usr->email }}</div>
                        </div>
                        <span class="badge badge-sm badge-ghost shrink-0 uppercase">{{ $usr->role }}</span>
                    </a>
                @empty
                    <div class="text-xs text-gray-400 italic py-2">{{ __('No users found.') }}</div>
                @endforelse
            </x-card>
        </div>
    @endif
</div>
