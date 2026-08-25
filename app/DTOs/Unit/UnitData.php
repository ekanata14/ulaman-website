<?php

namespace App\DTOs\Unit;

class UnitData
{
    public function __construct(
        public ?int $id,
        public string $nama,
        public ?string $simbol = null,
    ) {}
}
