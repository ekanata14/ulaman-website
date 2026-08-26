<?php

namespace App\Actions\Report;

use App\Models\Item;
use Illuminate\Support\Facades\DB;

/**
 * Ringkasan riwayat pembelian sebuah barang untuk halaman detail (§11.4):
 * total qty, total belanja (SUM net_total), jumlah supplier berbeda, dan
 * jumlah baris nota. Agregasi uang di DB (§22.D#1); string DECIMAL(18,2).
 */
class GetItemPurchaseSummary
{
    /**
     * @return array{totalQty: string, totalSpend: string, supplierCount: int, lineCount: int}
     */
    public function execute(Item $item): array
    {
        $agg = DB::table('purchase_items as pi')
            ->join('purchases as p', 'p.id', '=', 'pi.purchase_id')
            ->where('pi.item_id', $item->id)
            ->whereNull('p.deleted_at')
            ->selectRaw('COALESCE(SUM(pi.qty), 0) as total_qty, COALESCE(SUM(pi.net_total), 0) as total_spend, '
                .'COUNT(DISTINCT p.supplier_id) as supplier_count, COUNT(*) as line_count')
            ->first();

        return [
            'totalQty' => number_format((float) ($agg->total_qty ?? 0), 2, '.', ''),
            'totalSpend' => number_format((float) ($agg->total_spend ?? 0), 2, '.', ''),
            'supplierCount' => (int) ($agg->supplier_count ?? 0),
            'lineCount' => (int) ($agg->line_count ?? 0),
        ];
    }
}
