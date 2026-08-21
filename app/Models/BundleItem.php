<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BundleItem extends Model
{
    protected $fillable = [
        'bundle_id',
        'purchase_item_id',
    ];

    /** @return BelongsTo<PurchaseBundle, $this> */
    public function bundle(): BelongsTo
    {
        return $this->belongsTo(PurchaseBundle::class, 'bundle_id');
    }

    /** @return BelongsTo<PurchaseItem, $this> */
    public function purchaseItem(): BelongsTo
    {
        return $this->belongsTo(PurchaseItem::class);
    }
}
