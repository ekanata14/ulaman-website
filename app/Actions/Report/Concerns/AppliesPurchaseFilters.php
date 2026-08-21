<?php

namespace App\Actions\Report\Concerns;

use App\DTOs\Purchase\PurchaseFilterData;
use Illuminate\Database\Eloquent\Builder;

trait AppliesPurchaseFilters
{
    /**
     * Terapkan filter guest ke query Purchase. Pemanggil bertanggung jawab
     * membatasi ke status final (draft tidak pernah tampil ke guest).
     *
     * @param  Builder<\App\Models\Purchase>  $query
     */
    protected function applyPurchaseFilters(Builder $query, PurchaseFilterData $f): void
    {
        if ($f->dari !== null && $f->dari !== '') {
            $query->whereDate('tanggal', '>=', $f->dari);
        }
        if ($f->sampai !== null && $f->sampai !== '') {
            $query->whereDate('tanggal', '<=', $f->sampai);
        }
        if ($f->supplierIds !== []) {
            $query->whereIn('supplier_id', $f->supplierIds);
        }
        if ($f->categoryIds !== []) {
            $query->whereIn('category_id', $f->categoryIds);
        }
        if ($f->onlyWithPhoto) {
            $query->has('photos');
        }
        if ($f->search !== null && $f->search !== '') {
            $term = $f->search;
            $query->where(function (Builder $q) use ($term): void {
                $q->where('kode', 'like', "%{$term}%")
                    ->orWhere('nomor_nota', 'like', "%{$term}%")
                    ->orWhere('remark', 'like', "%{$term}%")
                    ->orWhereHas('items', fn (Builder $i) => $i->where('deskripsi', 'like', "%{$term}%"));
            });
        }
    }

    protected function safeSort(string $sort): string
    {
        return in_array($sort, ['tanggal', 'grand_total', 'kode'], true) ? $sort : 'tanggal';
    }

    protected function safeOrder(string $order): string
    {
        return $order === 'asc' ? 'asc' : 'desc';
    }
}
