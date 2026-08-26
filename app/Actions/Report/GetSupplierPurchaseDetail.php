<?php

namespace App\Actions\Report;

use App\Models\Supplier;
use Illuminate\Support\Facades\DB;

/**
 * Rincian belanja per supplier untuk halaman detail (§11.4):
 * total nominal & jumlah nota, plus daftar barang yang pernah dibeli dari
 * supplier ini beserta total qty dan total belanja per barang.
 *
 * Agregasi uang dilakukan di DB (SUM/GROUP BY) — komponen Livewire & Blade
 * tidak menghitung sendiri (§22.D#1). Uang dikembalikan sebagai string
 * DECIMAL(18,2), pola sama seperti GetSupplierRanking.
 */
class GetSupplierPurchaseDetail
{
    /**
     * @return array{
     *     grandTotal: string,
     *     notaCount: int,
     *     items: array<int, array{itemId: ?int, nama: string, unitNama: ?string, totalQty: string, totalSpend: string, times: int}>
     * }
     */
    public function execute(Supplier $supplier): array
    {
        $agg = DB::table('purchases')
            ->where('supplier_id', $supplier->id)
            ->whereNull('deleted_at')
            ->selectRaw('COALESCE(SUM(grand_total), 0) as total_belanja, COUNT(*) as nota_count')
            ->first();

        // Baris barang: gabung baris nota milik supplier ini, kelompokkan per
        // item (item_id + deskripsi agar barang master menyatu & barang lepas
        // terpisah). net_total = nilai final baris setelah semua diskon.
        $rows = DB::table('purchase_items as pi')
            ->join('purchases as p', 'p.id', '=', 'pi.purchase_id')
            ->leftJoin('items as it', 'it.id', '=', 'pi.item_id')
            ->leftJoin('units as u', 'u.id', '=', 'pi.unit_id')
            ->where('p.supplier_id', $supplier->id)
            ->whereNull('p.deleted_at')
            ->groupBy('pi.item_id', 'pi.deskripsi', 'u.nama')
            ->selectRaw('pi.item_id, pi.deskripsi, MAX(it.nama) as item_nama, u.nama as unit_nama, '
                .'SUM(pi.qty) as total_qty, SUM(pi.net_total) as total_spend, COUNT(*) as times')
            ->orderByDesc('total_spend')
            ->get();

        $items = [];
        foreach ($rows as $row) {
            $itemId = $row->item_id !== null ? (int) $row->item_id : null;
            $items[] = [
                'itemId' => $itemId,
                'nama' => $itemId !== null && $row->item_nama !== null ? $row->item_nama : (string) $row->deskripsi,
                'unitNama' => $row->unit_nama,
                'totalQty' => number_format((float) $row->total_qty, 2, '.', ''),
                'totalSpend' => number_format((float) $row->total_spend, 2, '.', ''),
                'times' => (int) $row->times,
            ];
        }

        return [
            'grandTotal' => number_format((float) ($agg->total_belanja ?? 0), 2, '.', ''),
            'notaCount' => (int) ($agg->nota_count ?? 0),
            'items' => $items,
        ];
    }
}
