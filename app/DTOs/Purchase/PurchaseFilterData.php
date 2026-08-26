<?php

namespace App\DTOs\Purchase;

/**
 * Kontrak filter tunggal untuk seluruh Query Action (§10.6).
 */
class PurchaseFilterData
{
    /**
     * @param  array<int, int>  $supplierIds
     */
    public function __construct(
        public ?string $dari = null,
        public ?string $sampai = null,
        public array $supplierIds = [],
        public ?string $search = null,
        public string $viewMode = 'nota',
        public bool $onlyWithPhoto = false,
        public string $sort = 'tanggal',
        public string $order = 'desc',
        public int $perPage = 50,
    ) {}
}
