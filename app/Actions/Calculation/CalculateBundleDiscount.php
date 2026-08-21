<?php

namespace App\Actions\Calculation;

use App\Enums\BundleType;
use App\Exceptions\CalculationException;
use App\Support\Money;

/**
 * §7 Langkah 2 (nilai diskon bundle). Murni, tanpa DB.
 *   PERSEN      → ROUND(basis × nilai / 100)
 *   NOMINAL     → nilai
 *   HARGA_PAKET → basis − harga_paket
 * Hasil dijamin 0 ≤ diskon ≤ basis.
 */
class CalculateBundleDiscount
{
    public function execute(BundleType $tipe, string $nilai, string $basis): string
    {
        $diskon = match ($tipe) {
            BundleType::PERSEN => $this->diskonPersen($nilai, $basis),
            BundleType::NOMINAL => Money::add(Money::roundHalfUp($nilai), '0'),
            BundleType::HARGA_PAKET => Money::sub($basis, Money::add(Money::roundHalfUp($nilai), '0')),
        };

        if (Money::compare($diskon, '0') < 0 || Money::compare($diskon, $basis) > 0) {
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
