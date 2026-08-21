<?php

namespace App\Actions\Export;

use App\Actions\Report\Concerns\AppliesPurchaseFilters;
use App\DTOs\Purchase\PurchaseFilterData;
use App\Models\Purchase;
use App\Support\Money;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * §F-08 — Ekspor nota final ke CSV (streamed). Hanya nota final, eager load
 * supplier + jumlah item (anti N+1).
 */
class ExportPurchasesToCsv
{
    use AppliesPurchaseFilters;

    public function execute(PurchaseFilterData $f): StreamedResponse
    {
        $query = Purchase::query()
            ->final()
            ->with('supplier')
            ->withCount('items');

        $this->applyPurchaseFilters($query, $f);
        $query->orderBy('tanggal')->orderBy('id');

        $headings = [
            'Kode', 'Tanggal', 'Supplier', 'Jumlah Item',
            'Subtotal', 'Diskon Item', 'Diskon Bundle', 'Grand Total', 'Status',
        ];

        return response()->streamDownload(function () use ($query, $headings): void {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, $headings);

            $query->chunk(500, function ($purchases) use ($handle): void {
                foreach ($purchases as $purchase) {
                    $supplier = $purchase->supplier;
                    fputcsv($handle, [
                        $purchase->kode,
                        $purchase->tanggal->format('Y-m-d'),
                        $supplier !== null ? $supplier->nama : 'Lain-lain',
                        (int) $purchase->getAttribute('items_count'),
                        Money::format((string) $purchase->subtotal),
                        Money::format((string) $purchase->total_diskon_item),
                        Money::format((string) $purchase->total_diskon_bundle),
                        Money::format((string) $purchase->grand_total),
                        $purchase->status->label(),
                    ]);
                }
            });

            fclose($handle);
        }, 'nota-pembelian.csv', [
            'Content-Type' => 'text/csv',
        ]);
    }
}
