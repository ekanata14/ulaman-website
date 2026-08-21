<div>
    {{-- HEADER --}}
    <x-header title="{{ __('Audit Log') }}" subtitle="{{ __('History of data changes') }}" separator
        progress-indicator />

    {{-- INLINE FILTER BAR --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4 mb-6 items-end">
        <x-input placeholder="{{ __('Search action or entity') }}..." wire:model.live.debounce="search"
            icon="o-magnifying-glass" />

        <x-select wire:model.live="filterAksi" :options="[
            ['id' => '', 'name' => __('All Actions')],
            ['id' => 'create', 'name' => __('Create')],
            ['id' => 'update', 'name' => __('Update')],
            ['id' => 'delete', 'name' => __('Delete')],
        ]" icon="o-bolt" />

        <x-input type="date" wire:model.live="dari" label="{{ __('From') }}" />
        <x-input type="date" wire:model.live="sampai" label="{{ __('To') }}" />

        <div>
            <x-button label="{{ __('Clear') }}" wire:click="clearFilters" icon="o-x-mark"
                class="btn-ghost w-full lg:w-auto text-gray-500" />
        </div>
    </div>

    {{-- CARD TABLE --}}
    <x-card class="bg-base-100 shadow-sm">
        <div class="overflow-x-auto">
            <table class="table table-zebra">
                <thead>
                    <tr>
                        <th>{{ __('Time') }}</th>
                        <th>{{ __('User') }}</th>
                        <th>{{ __('Action') }}</th>
                        <th>{{ __('Entity') }}</th>
                        <th>{{ __('Detail') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($logs as $log)
                        <tr wire:key="log-{{ $log->id }}">
                            <td class="whitespace-nowrap text-sm">
                                {{ $log->created_at?->format('d M Y H:i') ?? '-' }}
                            </td>
                            <td>{{ $log->user?->name ?? __('System') }}</td>
                            <td>
                                <span
                                    class="badge badge-sm {{ match ($log->aksi) {
                                        'create' => 'badge-success text-white',
                                        'update' => 'badge-info text-white',
                                        'delete' => 'badge-error text-white',
                                        default => 'badge-ghost',
                                    } }}">
                                    {{ ucfirst($log->aksi) }}
                                </span>
                            </td>
                            <td class="whitespace-nowrap">
                                <span class="font-medium">{{ class_basename($log->auditable_type) }}</span>
                                <span class="text-gray-400">#{{ $log->auditable_id }}</span>
                            </td>
                            <td class="text-sm text-gray-500">
                                @php $baru = $log->data_baru ?? []; @endphp
                                @if (isset($baru['grand_total']))
                                    {{ __('Grand Total') }}:
                                    {{ \App\Support\Money::format((string) $baru['grand_total']) }}
                                @elseif (isset($baru['kode']))
                                    {{ $baru['kode'] }}
                                @elseif (isset($baru['nama']))
                                    {{ $baru['nama'] }}
                                @else
                                    <span class="text-gray-300">&mdash;</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center py-10 text-gray-500">
                                {{ __('No audit records found.') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $logs->links() }}</div>
    </x-card>
</div>
