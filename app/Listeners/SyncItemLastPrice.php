<?php

namespace App\Listeners;

use App\Actions\Item\UpdateItemLastPrice;
use App\Events\PurchaseSaved;

class SyncItemLastPrice
{
    public function __construct(
        private readonly UpdateItemLastPrice $action,
    ) {}

    public function handle(PurchaseSaved $event): void
    {
        $this->action->execute($event->purchase);
    }
}
