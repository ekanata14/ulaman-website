<?php

namespace App\Actions\Report;

use App\Models\Category;
use App\Models\Item;
use App\Models\Purchase;
use App\Models\Supplier;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

/**
 * Pencarian global read-only untuk halaman guest: supplier, kategori, item, dan
 * nota final. Tanpa tulis DB / transaksi. Eager load relasi (anti N+1).
 */
class SearchCatalog
{
    /**
     * @return array{
     *     suppliers: Collection<int, Supplier>,
     *     categories: Collection<int, Category>,
     *     items: Collection<int, Item>,
     *     notes: Collection<int, Purchase>,
     * }
     */
    public function execute(string $term): array
    {
        $term = trim($term);
        $like = '%'.$term.'%';

        return [
            'suppliers' => Supplier::query()
                ->active()
                ->where(function (Builder $q) use ($like): void {
                    $q->where('nama', 'like', $like)
                        ->orWhere('pic', 'like', $like)
                        ->orWhere('telepon', 'like', $like);
                })
                ->orderBy('nama')
                ->limit(6)
                ->get(),

            'categories' => Category::query()
                ->where('nama', 'like', $like)
                ->orderBy('nama')
                ->limit(6)
                ->get(),

            'items' => Item::query()
                ->with(['unit', 'category'])
                ->where('nama', 'like', $like)
                ->orderBy('nama')
                ->limit(8)
                ->get(),

            'notes' => Purchase::query()
                ->final()
                ->search($term)
                ->with('supplier')
                ->orderByDesc('tanggal')
                ->orderByDesc('id')
                ->limit(8)
                ->get(),
        ];
    }
}
