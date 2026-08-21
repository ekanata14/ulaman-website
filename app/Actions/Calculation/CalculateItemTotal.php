<?php

namespace App\Actions\Calculation;

use App\DTOs\Purchase\CalculatedItemData;
use App\DTOs\Purchase\PurchaseItemData;
use App\Enums\DiscountType;
use App\Exceptions\CalculationException;
use App\Support\Money;

/**
 * §7 Langkah 1 — Per Item. Murni, tanpa DB.
 *   subtotal = qty × harga_satuan
 *   diskon   = NONE→0 · PERSEN→ROUND(subtotal×nilai/100) · NOMINAL→nilai
 *   total    = subtotal − diskon
 */
class CalculateItemTotal
{
    public function execute(PurchaseItemData $item): CalculatedItemData
    {
        $harga = $item->hargaSatuan ?? '0';
        $subtotal = Money::mul($item->qty, $harga);

        $diskon = $this->hitungDiskon($item->diskonTipe, $item->diskonNilai, $subtotal);

        if (Money::compare($diskon, $subtotal) > 0) {
            throw CalculationException::discountExceedsSubtotal($diskon, $subtotal);
        }

        $total = Money::sub($subtotal, $diskon);

        return new CalculatedItemData(
            subtotal: $subtotal,
            diskonAmount: $diskon,
            total: $total,
            uid: $item->uid,
        );
    }

    private function hitungDiskon(DiscountType $tipe, string $nilai, string $subtotal): string
    {
        return match ($tipe) {
            DiscountType::NONE => Money::zero(),
            DiscountType::PERSEN => $this->diskonPersen($nilai, $subtotal),
            DiscountType::NOMINAL => Money::add(Money::roundHalfUp($nilai), '0'),
        };
    }

    private function diskonPersen(string $nilai, string $subtotal): string
    {
        if (Money::compare($nilai, '0') < 0 || Money::compare($nilai, '100') > 0) {
            throw CalculationException::percentOutOfRange($nilai);
        }

        // ROUND(subtotal × nilai / 100) half-up ke rupiah bulat, direpresentasikan skala 2.
        $raw = bcdiv(bcmul($subtotal, $nilai, 6), '100', 6);

        return Money::add(Money::roundHalfUp($raw), '0');
    }
}
