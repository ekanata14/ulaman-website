<?php

namespace App\Actions\Report;

use App\Models\Item;
use App\Models\Purchase;
use App\Models\Supplier;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

/**
 * Pencarian global lintas-entitas untuk admin: nota pembelian (semua status),
 * supplier (aktif + nonaktif), barang, satuan, dan user. Read-only, eager load
 * (anti N+1), hasil dibatasi per grup. Berbeda dari SearchCatalog (guest) yang
 * hanya menampilkan supplier aktif & nota final.
 */
class AdminGlobalSearch
{
    /**
     * @return array{
     *     purchases: Collection<int, Purchase>,
     *     suppliers: Collection<int, Supplier>,
     *     items: Collection<int, Item>,
     *     units: Collection<int, Unit>,
     *     users: Collection<int, User>,
     * }
     */
    public function execute(string $term, ?string $userRole = null): array
    {
        $term = trim($term);
        $like = '%'.$term.'%';

        return [
            'purchases' => Purchase::query()
                ->search($term)
                ->with('supplier')
                ->orderByDesc('tanggal')
                ->orderByDesc('id')
                ->limit(8)
                ->get(),

            'suppliers' => Supplier::query()
                ->where(function (Builder $q) use ($like): void {
                    $q->where('nama', 'like', $like)
                        ->orWhere('pic', 'like', $like)
                        ->orWhere('telepon', 'like', $like)
                        ->orWhere('alamat', 'like', $like);
                })
                ->orderBy('nama')
                ->limit(8)
                ->get(),

            'items' => Item::query()
                ->with('unit')
                ->where('nama', 'like', $like)
                ->orderBy('nama')
                ->limit(8)
                ->get(),

            'units' => Unit::query()
                ->where(function (Builder $q) use ($like): void {
                    $q->where('nama', 'like', $like)
                        ->orWhere('simbol', 'like', $like);
                })
                ->orderBy('nama')
                ->limit(8)
                ->get(),

            'users' => User::query()
                ->where(function (Builder $q) use ($like): void {
                    $q->where('name', 'like', $like)
                        ->orWhere('email', 'like', $like);
                })
                ->when($userRole !== null && $userRole !== '', fn (Builder $q): Builder => $q->where('role', $userRole))
                ->orderBy('name')
                ->limit(8)
                ->get(),
        ];
    }
}
