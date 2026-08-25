<?php

namespace App\DTOs\Category;

class CategoryData
{
    public function __construct(
        public ?int $id,
        public string $nama,
        public ?string $warna = null,
    ) {}
}
