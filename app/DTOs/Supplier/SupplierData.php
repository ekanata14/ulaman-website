<?php

namespace App\DTOs\Supplier;

class SupplierData
{
    public function __construct(
        public ?int $id,
        public string $nama,
        public ?string $alamat = null,
        public ?string $telepon = null,
        public ?string $pic = null,
        public ?string $catatan = null,
        public bool $isActive = true,
    ) {}
}
