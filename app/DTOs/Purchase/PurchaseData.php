<?php

namespace App\DTOs\Purchase;

use App\DTOs\Supplier\SupplierData;
use App\Enums\DiscountType;
use App\Enums\PaymentMethod;
use App\Enums\PurchaseStatus;

/**
 * Kontrak data nota untuk Action. Nilai uang bertipe string demi presisi bcmath.
 */
class PurchaseData
{
    /**
     * @param  array<int, PurchaseItemData>  $items
     * @param  array<int, PurchaseBundleData>  $bundles
     */
    public function __construct(
        public ?int $id,
        public string $tanggal,
        public ?SupplierData $supplier,
        public ?string $nomorNota,
        public ?int $categoryId,
        public ?PaymentMethod $metodeBayar,
        public ?string $remark,
        public PurchaseStatus $status,
        public ?DiscountType $diskonNotaTipe,
        public string $diskonNotaNilai,
        public array $items,
        public array $bundles,
    ) {}
}
