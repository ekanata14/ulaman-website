<?php

namespace App\Livewire\Guest;

use App\Actions\Report\GetPurchaseSummary;
use App\DTOs\Purchase\PurchaseFilterData;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Lazy;
use Livewire\Component;

/**
 * §F-06.2 — Kartu ringkasan. #[Lazy] agar tabel tampil lebih dulu; menerima
 * filter dari PurchaseBrowser. Read-only (tanpa method mutasi).
 */
#[Lazy]
class SummaryCards extends Component
{
    /** @var array<string, mixed> */
    public array $filter = [];

    public function placeholder(): string
    {
        return <<<'HTML'
        <div class="grid grid-cols-2 md:grid-cols-5 gap-3 mb-6">
            <div class="h-20 rounded-lg bg-base-200 animate-pulse"></div>
            <div class="h-20 rounded-lg bg-base-200 animate-pulse"></div>
            <div class="h-20 rounded-lg bg-base-200 animate-pulse"></div>
            <div class="h-20 rounded-lg bg-base-200 animate-pulse"></div>
            <div class="h-20 rounded-lg bg-base-200 animate-pulse"></div>
        </div>
        HTML;
    }

    public function render(): View
    {
        $f = new PurchaseFilterData(
            dari: $this->filter['dari'] ?? null,
            sampai: $this->filter['sampai'] ?? null,
            supplierIds: $this->filter['supplierIds'] ?? [],
            categoryIds: $this->filter['categoryIds'] ?? [],
            search: $this->filter['search'] ?? null,
            onlyWithPhoto: (bool) ($this->filter['onlyWithPhoto'] ?? false),
        );

        return view('livewire.guest.summary-cards', [
            'summary' => app(GetPurchaseSummary::class)->execute($f),
        ]);
    }
}
