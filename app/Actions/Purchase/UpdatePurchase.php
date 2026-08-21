<?php

namespace App\Actions\Purchase;

use App\Actions\Calculation\CalculatePurchaseTotals;
use App\Actions\Concerns\InteractsWithAuditLog;
use App\Actions\Supplier\ResolveSupplier;
use App\DTOs\Purchase\PurchaseData;
use App\Events\PurchaseSaved;
use App\Models\Purchase;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/**
 * §10.2 — Orchestrator pembaruan nota dengan diffing item (create/update/delete
 * by id). Kode nota dipertahankan; total dihitung ulang penuh.
 */
class UpdatePurchase
{
    use InteractsWithAuditLog;

    public function __construct(
        private readonly CalculatePurchaseTotals $calculate,
        private readonly ResolveSupplier $resolveSupplier,
        private readonly SyncPurchaseItems $syncItems,
        private readonly SyncPurchaseBundles $syncBundles,
        private readonly PersistPurchaseTotals $persistTotals,
    ) {}

    public function execute(Purchase $purchase, PurchaseData $data, User $actor): Purchase
    {
        return DB::transaction(function () use ($purchase, $data, $actor): Purchase {
            $old = ['grand_total' => (string) $purchase->grand_total];
            $calc = $this->calculate->execute($data);
            $supplier = $this->resolveSupplier->execute($data->supplier);
            $tanggal = CarbonImmutable::parse($data->tanggal);

            $purchase->forceFill([
                'tanggal' => $tanggal,
                'supplier_id' => $supplier?->getKey(),
                'nomor_nota' => $data->nomorNota,
                'category_id' => $data->categoryId,
                'metode_bayar' => $data->metodeBayar,
                'remark' => $data->remark,
                'status' => $data->status,
                'diskon_nota_tipe' => $data->diskonNotaTipe,
                'diskon_nota_nilai' => $data->diskonNotaNilai,
                'updated_by' => $actor->getKey(),
            ])->save();

            $map = $this->syncItems->execute($purchase, $data, $calc);
            $this->syncBundles->execute($purchase, $calc, $map);
            $this->persistTotals->execute($purchase, $calc);

            $this->recordAudit($actor, 'update', $purchase, $old, ['grand_total' => $calc->grandTotal]);

            PurchaseSaved::dispatch($purchase->refresh());

            return $purchase;
        });
    }
}
