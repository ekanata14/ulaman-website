<?php

namespace App\Actions\Import;

use App\Actions\Purchase\StorePurchase;
use App\DTOs\Purchase\PurchaseBundleData;
use App\DTOs\Purchase\PurchaseData;
use App\DTOs\Purchase\PurchaseItemData;
use App\DTOs\Supplier\SupplierData;
use App\Enums\BundleType;
use App\Enums\DiscountType;
use App\Enums\PurchaseStatus;
use App\Models\User;
use App\Support\Money;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * §F-09 — Job impor Excel ter-queue. TIDAK menghitung total sendiri: tiap nota
 * dibangun sebagai PurchaseData lalu dipersist via StorePurchase (kalkulator
 * yang sama dengan entri manual), menjamin angka migrasi == entri manual.
 */
class ImportPurchaseExcel implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public string $path,
        public int $actorId,
    ) {}

    public function handle(
        ParsePurchaseExcel $parse,
        StorePurchase $store,
    ): void {
        $actor = User::findOrFail($this->actorId);
        $parsed = $parse->execute($this->path);
        $notas = $parsed['notas'];
        $totalNotas = count($notas);

        /** @var array<string, string> $systemBySheet */
        $systemBySheet = [];
        $netGrandTotal = Money::zero();

        DB::transaction(function () use ($notas, $totalNotas, $actor, $store, &$systemBySheet, &$netGrandTotal): void {
            foreach ($notas as $i => $nota) {
                $items = [];
                foreach ($nota['items'] as $urutan => $item) {
                    $items[] = new PurchaseItemData(
                        uid: $item['uid'],
                        id: null,
                        itemId: null,
                        deskripsi: $item['deskripsi'],
                        qty: $item['qty'],
                        unitId: null,
                        hargaSatuan: $item['hargaSatuan'],
                        diskonTipe: DiscountType::from((string) $item['diskonTipe']),
                        diskonNilai: (string) $item['diskonNilai'],
                        remark: null,
                        urutan: $urutan,
                    );
                }

                $bundles = [];
                if ($nota['isBundle'] === true && isset($nota['bundle'])) {
                    /** @var array{nama:string, tipe:BundleType, nilai:string, itemUids:array<int,string>} $b */
                    $b = $nota['bundle'];
                    $bundles[] = new PurchaseBundleData(
                        nama: $b['nama'],
                        tipe: $b['tipe'],
                        nilai: $b['nilai'],
                        itemUids: $b['itemUids'],
                    );
                }

                $data = new PurchaseData(
                    id: null,
                    tanggal: $nota['tanggal'],
                    supplier: new SupplierData(id: null, nama: $nota['supplier']),
                    nomorNota: $nota['nomorNota'],
                    remark: null,
                    status: PurchaseStatus::FINAL,
                    diskonNotaTipe: DiscountType::from((string) $nota['diskonNotaTipe']),
                    diskonNotaNilai: (string) $nota['diskonNotaNilai'],
                    items: $items,
                    bundles: $bundles,
                );

                $purchase = $store->execute($data, $actor);

                if ($nota['needsReview'] === true) {
                    $purchase->forceFill(['needs_review' => true])->save();
                }

                // Rekonsiliasi memakai subtotal (kotor, sebelum diskon) agar cocok
                // dengan Σ kolom Total Excel walau nota memiliki diskon. Grand total
                // (setelah diskon) diakumulasi terpisah sebagai info.
                $sheet = $nota['sheet'];
                $systemBySheet[$sheet] = Money::add(
                    $systemBySheet[$sheet] ?? '0',
                    (string) $purchase->subtotal,
                );
                $netGrandTotal = Money::add($netGrandTotal, (string) $purchase->grand_total);

                Cache::put('import:progress', [
                    'done' => $i + 1,
                    'total' => $totalNotas,
                    'finished' => false,
                ], 3600);
            }
        });

        $reconcile = app(ReconcileImportTotals::class)->execute(
            $parsed['sheetTotals'],
            $systemBySheet,
        );

        Cache::put('import:progress', [
            'done' => $totalNotas,
            'total' => $totalNotas,
            'finished' => true,
        ], 3600);

        Cache::put('import:reconciliation', [
            'rows' => $reconcile['rows'],
            'totalSystem' => $reconcile['totalSystem'],
            'totalExcel' => $reconcile['totalExcel'],
            'totalDelta' => $reconcile['totalDelta'],
            'netGrandTotal' => $netGrandTotal,
            'warnings' => $parsed['warnings'],
            'stats' => $parsed['stats'],
        ], 3600);
    }
}
