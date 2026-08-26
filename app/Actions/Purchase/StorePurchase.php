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
 * §10.2 — Orchestrator pembuatan nota. Satu-satunya pembuka transaksi.
 * Alur: hitung (§7) → resolve supplier → buat header + kode → sync item &
 * bundle → persist total → audit → event(PurchaseSaved).
 */
class StorePurchase
{
    use InteractsWithAuditLog;

    public function __construct(
        private readonly CalculatePurchaseTotals $calculate,
        private readonly ResolveSupplier $resolveSupplier,
        private readonly GeneratePurchaseCode $generateCode,
        private readonly SyncPurchaseItems $syncItems,
        private readonly SyncPurchaseBundles $syncBundles,
        private readonly PersistPurchaseTotals $persistTotals,
    ) {}

    public function execute(PurchaseData $data, User $actor): Purchase
    {
        return DB::transaction(function () use ($data, $actor): Purchase {
            $calc = $this->calculate->execute($data);
            $supplier = $this->resolveSupplier->execute($data->supplier);
            $tanggal = CarbonImmutable::parse($data->tanggal);

            $purchase = new Purchase;
            $purchase->forceFill([
                'kode' => $this->generateCode->execute($tanggal),
                'tanggal' => $tanggal,
                'supplier_id' => $supplier?->getKey(),
                'nomor_nota' => $data->nomorNota,
                'remark' => $data->remark,
                'status' => $data->status,
                'diskon_nota_tipe' => $data->diskonNotaTipe,
                'diskon_nota_nilai' => $data->diskonNotaNilai,
                'created_by' => $actor->getKey(),
                'updated_by' => $actor->getKey(),
            ]);
            $purchase->save();

            $map = $this->syncItems->execute($purchase, $data, $calc);
            $this->syncBundles->execute($purchase, $calc, $map);
            $this->persistTotals->execute($purchase, $calc);

            $this->recordAudit($actor, 'create', $purchase, null, ['grand_total' => $calc->grandTotal]);

            PurchaseSaved::dispatch($purchase->refresh());

            return $purchase;
        });
    }
}
