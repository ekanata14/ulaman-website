@php
    use App\Support\Money;

    // Kartu ringkasan — markup ringkas & responsive agar nilai uang panjang tidak
    // meluber di layar mobile (truncate + font mengecil di mobile).
    $cards = [
        ['title' => __('Total Purchases'), 'value' => Money::format($summary->total), 'icon' => 'o-banknotes', 'accent' => 'text-primary bg-primary/10'],
        ['title' => __('Items'), 'value' => number_format($summary->notaCount, 0, ',', '.'), 'icon' => 'o-document-text', 'accent' => 'text-info bg-info/10'],
        ['title' => __('Items'), 'value' => number_format($summary->itemCount, 0, ',', '.'), 'icon' => 'o-cube', 'accent' => 'text-success bg-success/10'],
        ['title' => __('Suppliers'), 'value' => number_format($summary->supplierCount, 0, ',', '.'), 'icon' => 'o-building-storefront', 'accent' => 'text-secondary bg-secondary/10'],
        ['title' => __('Avg / Month'), 'value' => Money::format($summary->avgPerMonth), 'icon' => 'o-chart-bar', 'accent' => 'text-warning bg-warning/10'],
    ];
@endphp

<div class="grid grid-cols-2 lg:grid-cols-5 gap-2 sm:gap-3 mb-6">
    @foreach ($cards as $card)
        <div class="bg-base-100 rounded-lg border border-base-300 shadow-sm p-3 sm:p-4 flex items-center gap-3 min-w-0">
            <div class="shrink-0 w-9 h-9 sm:w-10 sm:h-10 rounded-lg flex items-center justify-center {{ $card['accent'] }}">
                <x-icon name="{{ $card['icon'] }}" class="w-5 h-5" />
            </div>
            <div class="min-w-0">
                <div class="text-[11px] sm:text-xs text-gray-500 truncate">{{ $card['title'] }}</div>
                <div class="font-bold text-sm sm:text-lg leading-tight truncate" title="{{ $card['value'] }}">
                    {{ $card['value'] }}
                </div>
            </div>
        </div>
    @endforeach
</div>
