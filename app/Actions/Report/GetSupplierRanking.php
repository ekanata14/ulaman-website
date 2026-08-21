<?php

namespace App\Actions\Report;

use App\Actions\Report\Concerns\AppliesPurchaseFilters;
use App\DTOs\Purchase\PurchaseFilterData;
use App\Models\Purchase;
use App\Models\Supplier;

/**
 * §F-07 — Peringkat supplier berdasarkan total belanja (hanya nota final).
 * Agregasi di DB (group by supplier_id, sum grand_total, count), nama supplier
 * dipetakan lewat lookup batch (anti N+1). Supplier null → "Lain-lain".
 */
class GetSupplierRanking
{
    use AppliesPurchaseFilters;

    /**
     * @return array<int, array{nama: string, total: string, notaCount: int}>
     */
    public function execute(PurchaseFilterData $f, int $limit = 10): array
    {
        $query = Purchase::query()->final();
        $this->applyPurchaseFilters($query, $f);

        $rows = $query
            ->selectRaw('supplier_id, SUM(grand_total) as total_belanja, COUNT(*) as nota_count')
            ->groupBy('supplier_id')
            ->orderByDesc('total_belanja')
            ->limit($limit)
            ->get();

        $supplierIds = $rows->pluck('supplier_id')->filter()->all();

        /** @var array<int, string> $names */
        $names = Supplier::query()
            ->whereIn('id', $supplierIds)
            ->pluck('nama', 'id')
            ->all();

        $result = [];
        foreach ($rows as $row) {
            $supplierId = $row->supplier_id;
            $result[] = [
                'nama' => $supplierId !== null ? ($names[$supplierId] ?? 'Lain-lain') : 'Lain-lain',
                'total' => number_format((float) $row->getAttribute('total_belanja'), 2, '.', ''),
                'notaCount' => (int) $row->getAttribute('nota_count'),
            ];
        }

        return $result;
    }
}
