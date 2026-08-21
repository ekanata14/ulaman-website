<?php

namespace App\DTOs\Report;

/**
 * Ringkasan pembelian sesuai filter (§F-06.2). Nilai uang string.
 */
class SummaryData
{
    public function __construct(
        public string $total,
        public int $notaCount,
        public int $itemCount,
        public int $supplierCount,
        public string $avgPerMonth,
    ) {}
}
