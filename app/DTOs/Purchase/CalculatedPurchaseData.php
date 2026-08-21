<?php

namespace App\DTOs\Purchase;

/**
 * Keluaran kalkulator per nota (§7 Langkah 3–4). Semua nilai string.
 */
class CalculatedPurchaseData
{
    /**
     * @param  array<int|string, CalculatedItemData>  $items
     * @param  array<int, CalculatedBundleData>  $bundles
     */
    public function __construct(
        public string $subtotal,
        public string $totalDiskonItem,
        public string $totalDiskonBundle,
        public string $diskonNotaAmount,
        public string $grandTotal,
        public array $items = [],
        public array $bundles = [],
    ) {}
}
