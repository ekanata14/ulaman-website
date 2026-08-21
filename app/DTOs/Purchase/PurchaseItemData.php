<?php

namespace App\DTOs\Purchase;

use App\Enums\DiscountType;

class PurchaseItemData
{
    public function __construct(
        public ?string $uid,
        public ?int $id,
        public ?int $itemId,
        public string $deskripsi,
        public string $qty,
        public ?int $unitId,
        public ?string $hargaSatuan,
        public DiscountType $diskonTipe,
        public string $diskonNilai,
        public ?string $remark,
        public int $urutan,
    ) {}
}
