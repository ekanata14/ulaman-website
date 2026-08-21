<?php

namespace App\DTOs\Purchase;

use App\Enums\BundleType;

class PurchaseBundleData
{
    /**
     * @param  array<int, string>  $itemUids
     */
    public function __construct(
        public string $nama,
        public BundleType $tipe,
        public string $nilai,
        public array $itemUids,
    ) {}
}
