<?php

namespace App\Enums;

enum PurchaseStatus: string
{
    case DRAFT = 'draft';
    case FINAL = 'final';

    public function label(): string
    {
        return match ($this) {
            self::DRAFT => 'Draf',
            self::FINAL => 'Final',
        };
    }
}
