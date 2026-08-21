@php use App\Support\Money; @endphp

<div class="space-y-8 pb-10">
    {{-- HEADER --}}
    <x-header title="{{ __('Dashboard') }}" subtitle="{{ __('Purchase overview') }}" separator progress-indicator>
        <x-slot:actions>
            <x-button label="{{ __('Purchase Notes') }}" icon="o-document-text" link="{{ route('admin.purchases') }}"
                class="btn-outline btn-sm" />
        </x-slot:actions>
    </x-header>

    {{-- SUMMARY STAT CARDS --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <x-stat title="{{ __('Total Spending') }}" value="{{ Money::format($summary->total) }}" icon="o-banknotes"
            class="bg-base-100 shadow-sm border-l-4 border-primary" color="text-primary" />
        <x-stat title="{{ __('Total Notes') }}" value="{{ number_format($summary->notaCount, 0, ',', '.') }}"
            icon="o-document-text" class="bg-base-100 shadow-sm border-l-4 border-info" color="text-info" />
        <x-stat title="{{ __('Total Items') }}" value="{{ number_format($summary->itemCount, 0, ',', '.') }}"
            icon="o-cube" class="bg-base-100 shadow-sm border-l-4 border-success" color="text-success" />
        <x-stat title="{{ __('Suppliers') }}" value="{{ number_format($summary->supplierCount, 0, ',', '.') }}"
            icon="o-building-storefront" class="bg-base-100 shadow-sm border-l-4 border-secondary"
            color="text-secondary" />
    </div>

    {{-- NEEDS REVIEW ALERT --}}
    @if ($needsReviewCount > 0)
        <x-card class="bg-warning/10 border border-warning/40 shadow-sm">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <div class="flex items-center gap-3">
                    <x-icon name="o-exclamation-triangle" class="w-8 h-8 text-warning" />
                    <div>
                        <div class="font-bold text-base-content">
                            {{ trans_choice('{1} :count note needs review|[2,*] :count notes need review', $needsReviewCount, ['count' => number_format($needsReviewCount, 0, ',', '.')]) }}
                        </div>
                        <div class="text-sm text-gray-500">
                            {{ __('Please review notes flagged during import or correction.') }}
                        </div>
                    </div>
                </div>
                <x-button label="{{ __('Review Now') }}" icon="o-arrow-right" link="{{ route('admin.purchases') }}"
                    class="btn-warning btn-sm" />
            </div>
        </x-card>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
        {{-- MONTHLY TREND CHART --}}
        <x-card title="{{ __('Monthly Trend') }}" class="bg-base-100 shadow-sm lg:col-span-2">
            @if (count($trend) === 0)
                <div class="py-10 text-center text-gray-500">{{ __('No data available yet.') }}</div>
            @else
                <div class="overflow-x-auto">
                    <div wire:ignore class="min-w-[480px]" x-data="{
                        chart: null,
                        labels: @js(array_map(fn($t) => $t['label'], $trend)),
                        totals: @js(array_map(fn($t) => (float) $t['total'], $trend)),
                        currency: @js(__('Total')),
                    }">
                        <canvas x-ref="canvas" height="120"></canvas>
                    </div>
                </div>

                @script
                    <script type="module">
                        import Chart from 'chart.js/auto';

                        const build = (el) => {
                            const state = Alpine.$data(el);
                            if (state.chart) {
                                state.chart.destroy();
                            }
                            const ctx = el.querySelector('canvas');
                            if (!ctx) return;
                            state.chart = new Chart(ctx, {
                                type: 'bar',
                                data: {
                                    labels: state.labels,
                                    datasets: [{
                                        label: state.currency,
                                        data: state.totals,
                                        backgroundColor: 'oklch(0.55 0.2 260 / 0.7)',
                                        borderRadius: 4,
                                    }],
                                },
                                options: {
                                    responsive: true,
                                    maintainAspectRatio: false,
                                    plugins: {
                                        legend: { display: false },
                                        tooltip: {
                                            callbacks: {
                                                label: (c) => 'Rp ' + new Intl.NumberFormat('id-ID').format(c.parsed.y),
                                            },
                                        },
                                    },
                                    scales: {
                                        y: {
                                            ticks: {
                                                callback: (v) => 'Rp ' + new Intl.NumberFormat('id-ID', {
                                                    notation: 'compact',
                                                }).format(v),
                                            },
                                        },
                                    },
                                },
                            });
                        };

                        const el = $wire.el.querySelector('[x-ref="canvas"]')?.closest('[x-data]');
                        if (el) build(el);
                    </script>
                @endscript
            @endif
        </x-card>

        {{-- TOP 5 SUPPLIER RANKING --}}
        <x-card title="{{ __('Top Suppliers') }}" class="bg-base-100 shadow-sm">
            @if (count($ranking) === 0)
                <div class="py-10 text-center text-gray-500">{{ __('No data available yet.') }}</div>
            @else
                @php $max = max(array_map(fn($r) => (float) $r['total'], $ranking)) ?: 1; @endphp
                <div class="space-y-4">
                    @foreach ($ranking as $rank)
                        <div wire:key="rank-{{ $loop->index }}">
                            <div class="flex items-center justify-between text-sm mb-1">
                                <span class="font-semibold truncate max-w-[60%]">
                                    {{ $loop->iteration }}. {{ $rank['nama'] }}
                                </span>
                                <span class="text-gray-500">{{ Money::format($rank['total']) }}</span>
                            </div>
                            <div class="w-full bg-base-200 rounded-full h-2">
                                <div class="bg-primary h-2 rounded-full"
                                    style="width: {{ min(100, ((float) $rank['total'] / $max) * 100) }}%"></div>
                            </div>
                            <div class="text-xs text-gray-400 mt-0.5">
                                {{ trans_choice('{1} :count note|[2,*] :count notes', $rank['notaCount'], ['count' => $rank['notaCount']]) }}
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </x-card>
    </div>

    {{-- RECENT NOTES --}}
    <x-card title="{{ __('Recent Notes') }}" class="bg-base-100 shadow-sm">
        <div class="overflow-x-auto">
            <table class="table table-zebra">
                <thead>
                    <tr>
                        <th>{{ __('Code') }}</th>
                        <th>{{ __('Date') }}</th>
                        <th>{{ __('Supplier') }}</th>
                        <th class="text-right">{{ __('Grand Total') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($recent as $note)
                        <tr wire:key="recent-{{ $note->id }}">
                            <td class="font-mono text-sm">{{ $note->kode }}</td>
                            <td>{{ $note->tanggal?->format('d M Y') ?? '-' }}</td>
                            <td>{{ $note->supplier?->nama ?? __('Others') }}</td>
                            <td class="text-right font-semibold">{{ Money::format($note->grand_total) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center py-10 text-gray-500">
                                {{ __('No notes recorded yet.') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-card>
</div>
