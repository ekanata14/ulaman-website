<?php

namespace App\Enums;

enum DiscountType: string
{
    case NONE = 'NONE';
    case PERSEN = 'PERSEN';
    case NOMINAL = 'NOMINAL';

    public function label(): string
    {
        return match ($this) {
            self::NONE => 'Tanpa Diskon',
            self::PERSEN => 'Persen',
            self::NOMINAL => 'Nominal',
        };
    }
}
