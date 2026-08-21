<div class="grid grid-cols-2 md:grid-cols-5 gap-3 mb-6">
    <x-stat title="{{ __('Total Purchases') }}" value="{{ \App\Support\Money::format($summary->total) }}"
        icon="o-banknotes" class="!p-4" color="text-primary" />
    <x-stat title="{{ __('Notes') }}" value="{{ number_format($summary->notaCount, 0, ',', '.') }}"
        icon="o-document-text" class="!p-4" />
    <x-stat title="{{ __('Items') }}" value="{{ number_format($summary->itemCount, 0, ',', '.') }}" icon="o-cube"
        class="!p-4" />
    <x-stat title="{{ __('Suppliers') }}" value="{{ number_format($summary->supplierCount, 0, ',', '.') }}"
        icon="o-building-storefront" class="!p-4" />
    <x-stat title="{{ __('Avg / Month') }}" value="{{ \App\Support\Money::format($summary->avgPerMonth) }}"
        icon="o-chart-bar" class="!p-4" />
</div>
