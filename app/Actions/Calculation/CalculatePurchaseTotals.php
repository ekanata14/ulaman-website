<?php

namespace App\Actions\Calculation;

use App\DTOs\Purchase\CalculatedBundleData;
use App\DTOs\Purchase\CalculatedPurchaseData;
use App\DTOs\Purchase\PurchaseData;
use App\Enums\DiscountType;
use App\Exceptions\CalculationException;
use App\Support\Money;

/**
 * §7 Langkah 1–4 — Orkestrasi perhitungan satu nota. Murni, tanpa DB.
 * Menjamin invariant §7 Langkah 4: Σ net_item == grand_total.
 */
class CalculatePurchaseTotals
{
    public function __construct(
        private readonly CalculateItemTotal $calculateItemTotal,
        private readonly CalculateBundleDiscount $calculateBundleDiscount,
        private readonly AllocateDiscountProportionally $allocate,
    ) {}

    public function execute(PurchaseData $data): CalculatedPurchaseData
    {
        // --- Langkah 1: per item ---
        $calc = [];
        $keys = [];
        $subtotalNota = Money::zero();
        $totalDiskonItem = Money::zero();

        foreach ($data->items as $i => $item) {
            $key = $item->uid ?? ('idx-'.$i);
            $r = $this->calculateItemTotal->execute($item);
            $r->uid ??= $key;
            $calc[$key] = $r;
            $keys[] = $key;
            $subtotalNota = Money::add($subtotalNota, $r->subtotal);
            $totalDiskonItem = Money::add($totalDiskonItem, $r->diskonAmount);
        }

        // --- Langkah 2: per bundle ---
        $totalDiskonBundle = Money::zero();
        $bundlesCalc = [];

        foreach ($data->bundles as $bundle) {
            $bobot = [];
            foreach ($bundle->itemUids as $uid) {
                $bobot[$uid] = $calc[$uid]->total;
            }

            $basis = Money::zero();
            foreach ($bobot as $b) {
                $basis = Money::add($basis, $b);
            }

            $diskonBundle = $this->calculateBundleDiscount->execute($bundle->tipe, $bundle->nilai, $basis);
            $alokasi = $this->allocate->execute($bobot, $diskonBundle);

            foreach ($alokasi as $uid => $a) {
                $calc[$uid]->alokasiDiskonBundle = Money::add($calc[$uid]->alokasiDiskonBundle, $a);
            }

            $totalDiskonBundle = Money::add($totalDiskonBundle, $diskonBundle);
            $bundlesCalc[] = new CalculatedBundleData(
                nama: $bundle->nama,
                tipe: $bundle->tipe,
                nilai: $bundle->nilai,
                basis: $basis,
                diskon: $diskonBundle,
                itemUids: $bundle->itemUids,
            );
        }

        // --- Langkah 3: diskon nota (opsional) ---
        $valueAfterStep2 = Money::sub(Money::sub($subtotalNota, $totalDiskonItem), $totalDiskonBundle);
        $diskonNotaAmount = Money::zero();

        if ($data->diskonNotaTipe !== null && $data->diskonNotaTipe !== DiscountType::NONE) {
            $diskonNotaAmount = $this->hitungDiskonNota($data->diskonNotaTipe, $data->diskonNotaNilai, $valueAfterStep2);

            $bobotNota = [];
            foreach ($keys as $key) {
                $bobotNota[$key] = Money::sub($calc[$key]->total, $calc[$key]->alokasiDiskonBundle);
            }

            foreach ($this->allocate->execute($bobotNota, $diskonNotaAmount) as $key => $a) {
                $calc[$key]->alokasiDiskonNota = $a;
            }
        }

        $grandTotal = Money::sub($valueAfterStep2, $diskonNotaAmount);

        // --- Langkah 4: net per item + invariant ---
        $sumNet = Money::zero();
        foreach ($keys as $key) {
            $net = Money::sub(
                Money::sub($calc[$key]->total, $calc[$key]->alokasiDiskonBundle),
                $calc[$key]->alokasiDiskonNota,
            );
            $calc[$key]->netTotal = $net;
            $sumNet = Money::add($sumNet, $net);
        }

        if (Money::compare($sumNet, $grandTotal) !== 0) {
            throw CalculationException::invariantMismatch($sumNet, $grandTotal);
        }

        return new CalculatedPurchaseData(
            subtotal: $subtotalNota,
            totalDiskonItem: $totalDiskonItem,
            totalDiskonBundle: $totalDiskonBundle,
            diskonNotaAmount: $diskonNotaAmount,
            grandTotal: $grandTotal,
            items: $calc,
            bundles: $bundlesCalc,
        );
    }

    private function hitungDiskonNota(DiscountType $tipe, string $nilai, string $basis): string
    {
        $diskon = match ($tipe) {
            DiscountType::NONE => Money::zero(),
            DiscountType::PERSEN => $this->diskonPersen($nilai, $basis),
            DiscountType::NOMINAL => Money::add(Money::roundHalfUp($nilai), '0'),
        };

        if (Money::compare($diskon, $basis) > 0) {
            throw CalculationException::discountExceedsBasis($diskon, $basis);
        }

        return $diskon;
    }

    private function diskonPersen(string $nilai, string $basis): string
    {
        if (Money::compare($nilai, '0') < 0 || Money::compare($nilai, '100') > 0) {
            throw CalculationException::percentOutOfRange($nilai);
        }

        $raw = bcdiv(bcmul($basis, $nilai, 6), '100', 6);

        return Money::add(Money::roundHalfUp($raw), '0');
    }
}
