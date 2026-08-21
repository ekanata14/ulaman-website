<?php

namespace App\Enums;

enum PaymentMethod: string
{
    case TUNAI = 'Tunai';
    case TRANSFER = 'Transfer';
    case QRIS = 'QRIS';
    case TEMPO = 'Tempo';
    case LAINNYA = 'Lainnya';

    public function label(): string
    {
        return $this->value;
    }
}
