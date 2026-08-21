<?php

namespace App\Actions\Maintenance;

use App\Models\Purchase;
use App\Support\Money;

/**
 * §7 Langkah 4 / §10.7 — Verifikasi invariant Σ net_item == grand_total pada
 * seluruh nota. Dengan $fix, total header direkonstruksi dari agregat baris item.
 */
class VerifyPurchaseTotals
{
    /**
     * @return array{checked:int, mismatches: array<int, array{kode:string, grand_total:string, sum_net:string}>, fixed:int}
     */
    public function execute(bool $fix = false): array
    {
        $checked = 0;
        $mismatches = [];
        $fixed = 0;

        Purchase::withTrashed()
            ->with(['items', 'bundles'])
            ->chunkById(200, function ($purchases) use (&$checked, &$mismatches, &$fixed, $fix): void {
                foreach ($purchases as $purchase) {
                    $checked++;

                    $sumNet = Money::zero();
                    $sumSubtotal = Money::zero();
                    $sumDiskonItem = Money::zero();
                    foreach ($purchase->items as $item) {
                        $sumNet = Money::add($sumNet, (string) $item->net_total);
                        $sumSubtotal = Money::add($sumSubtotal, (string) $item->subtotal);
                        $sumDiskonItem = Money::add($sumDiskonItem, (string) $item->diskon_amount);
                    }

                    $sumDiskonBundle = Money::zero();
                    foreach ($purchase->bundles as $bundle) {
                        $sumDiskonBundle = Money::add($sumDiskonBundle, (string) $bundle->diskon_amount);
                    }

                    if (Money::compare($sumNet, (string) $purchase->grand_total) === 0) {
                        continue;
                    }

                    $mismatches[] = [
                        'kode' => $purchase->kode,
                        'grand_total' => (string) $purchase->grand_total,
                        'sum_net' => $sumNet,
                    ];

                    if ($fix) {
                        $grand = Money::sub(
                            Money::sub(Money::sub($sumSubtotal, $sumDiskonItem), $sumDiskonBundle),
                            (string) $purchase->diskon_nota_amount,
                        );

                        $purchase->forceFill([
                            'subtotal' => $sumSubtotal,
                            'total_diskon_item' => $sumDiskonItem,
                            'total_diskon_bundle' => $sumDiskonBundle,
                            'grand_total' => $grand,
                        ])->save();

                        $fixed++;
                    }
                }
            });

        return ['checked' => $checked, 'mismatches' => $mismatches, 'fixed' => $fixed];
    }
}
