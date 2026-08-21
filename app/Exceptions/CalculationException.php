<?php

namespace App\Exceptions;

use DomainException;

/**
 * Pelanggaran aturan perhitungan §7 / §F-04 (mis. diskon melebihi basis).
 */
class CalculationException extends DomainException
{
    public static function discountExceedsSubtotal(string $diskon, string $subtotal): self
    {
        return new self("Diskon item ({$diskon}) melebihi subtotal ({$subtotal}).");
    }

    public static function percentOutOfRange(string $nilai): self
    {
        return new self("Nilai persen diskon harus 0–100, diberikan: {$nilai}.");
    }

    public static function discountExceedsBasis(string $diskon, string $basis): self
    {
        return new self("Diskon bundle ({$diskon}) melebihi basis ({$basis}).");
    }

    public static function invariantMismatch(string $sumNet, string $grandTotal): self
    {
        return new self("Invariant §7 gagal: Σ net_item ({$sumNet}) ≠ grand_total ({$grandTotal}).");
    }
}
