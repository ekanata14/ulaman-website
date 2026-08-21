<?php

namespace App\DTOs\Purchase;

/**
 * Keluaran kalkulator per item (§7). Semua nilai string DECIMAL(18,2).
 * Field alokasi & net diisi oleh CalculatePurchaseTotals (§7 Langkah 2–4);
 * CalculateItemTotal hanya mengisi subtotal, diskonAmount, total.
 */
class CalculatedItemData
{
    public function __construct(
        public string $subtotal,
        public string $diskonAmount,
        public string $total,
        public string $alokasiDiskonBundle = '0.00',
        public string $alokasiDiskonNota = '0.00',
        public ?string $netTotal = null,
        public ?string $uid = null,
    ) {
        $this->netTotal ??= $total;
    }
}
