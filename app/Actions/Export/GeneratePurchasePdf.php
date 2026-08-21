<?php

namespace App\Actions\Export;

use App\Actions\Report\Concerns\AppliesPurchaseFilters;
use App\DTOs\Purchase\PurchaseFilterData;
use App\Models\Purchase;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;

/**
 * §F-08 — Ekspor nota final ke PDF via barryvdh/laravel-dompdf. Hanya nota
 * final, eager load supplier + jumlah item (anti N+1).
 */
class GeneratePurchasePdf
{
    use AppliesPurchaseFilters;

    public function execute(PurchaseFilterData $f): Response
    {
        $query = Purchase::query()
            ->final()
            ->with('supplier')
            ->withCount('items');

        $this->applyPurchaseFilters($query, $f);

        $purchases = $query->orderBy('tanggal')->orderBy('id')->get();

        return Pdf::loadView('exports.purchases-pdf', [
            'purchases' => $purchases,
        ])->download('nota-pembelian.pdf');
    }
}
