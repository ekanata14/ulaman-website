<?php

namespace App\Exports;

use App\Actions\Report\Concerns\AppliesPurchaseFilters;
use App\DTOs\Purchase\PurchaseFilterData;
use App\Models\Purchase;
use App\Support\Money;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

/**
 * §F-08 — Sumber baris untuk ekspor Excel nota final. Kolom identik dengan CSV.
 */
class PurchasesExport implements FromCollection, WithHeadings
{
    use AppliesPurchaseFilters;

    public function __construct(private PurchaseFilterData $filter) {}

    /**
     * @return Collection<int, array<int, string>>
     */
    public function collection(): Collection
    {
        $query = Purchase::query()
            ->final()
            ->with('supplier')
            ->withCount('items');

        $this->applyPurchaseFilters($query, $this->filter);

        $purchases = $query
            ->orderBy('tanggal')
            ->orderBy('id')
            ->get();

        /** @var array<int, array<int, string>> $rows */
        $rows = [];

        foreach ($purchases as $purchase) {
            $supplier = $purchase->supplier;
            $rows[] = [
                (string) $purchase->kode,
                $purchase->tanggal->format('Y-m-d'),
                $supplier !== null ? (string) $supplier->nama : 'Lain-lain',
                (string) (int) $purchase->getAttribute('items_count'),
                Money::format((string) $purchase->subtotal),
                Money::format((string) $purchase->total_diskon_item),
                Money::format((string) $purchase->total_diskon_bundle),
                Money::format((string) $purchase->grand_total),
                $purchase->status->label(),
            ];
        }

        return new Collection($rows);
    }

    /**
     * @return array<int, string>
     */
    public function headings(): array
    {
        return [
            'Kode', 'Tanggal', 'Supplier', 'Jumlah Item',
            'Subtotal', 'Diskon Item', 'Diskon Bundle', 'Grand Total', 'Status',
        ];
    }
}
