<?php

namespace App\DTOs\Item;

class ItemData
{
    public function __construct(
        public ?int $id,
        public string $nama,
        public ?int $unitId = null,
    ) {}
}
