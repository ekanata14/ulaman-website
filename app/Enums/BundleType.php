<?php

namespace App\Enums;

enum BundleType: string
{
    case PERSEN = 'PERSEN';
    case NOMINAL = 'NOMINAL';
    case HARGA_PAKET = 'HARGA_PAKET';

    public function label(): string
    {
        return match ($this) {
            self::PERSEN => 'Persen',
            self::NOMINAL => 'Nominal',
            self::HARGA_PAKET => 'Harga Paket',
        };
    }
}
