<?php

namespace App\Events;

use App\Models\Purchase;
use Illuminate\Foundation\Events\Dispatchable;

class PurchaseSaved
{
    use Dispatchable;

    public function __construct(
        public readonly Purchase $purchase,
    ) {}
}
