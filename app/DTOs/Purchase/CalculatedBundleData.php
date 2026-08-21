<?php

namespace App\DTOs\Purchase;

use App\Enums\BundleType;

/**
 * Hasil perhitungan satu bundle (§7 Langkah 2), untuk persistensi.
 */
class CalculatedBundleData
{
    /**
     * @param  array<int, string>  $itemUids
     */
    public function __construct(
        public string $nama,
        public BundleType $tipe,
        public string $nilai,
        public string $basis,
        public string $diskon,
        public array $itemUids,
    ) {}
}
