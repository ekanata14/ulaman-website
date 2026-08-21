<?php

namespace App\Models;

use App\Enums\BundleType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PurchaseBundle extends Model
{
    protected $fillable = [
        'purchase_id',
        'nama',
        'tipe_diskon',
        'nilai_diskon',
        'basis_amount',
        'diskon_amount',
        'catatan',
    ];

    protected $casts = [
        'tipe_diskon' => BundleType::class,
        'nilai_diskon' => 'decimal:2',
        'basis_amount' => 'decimal:2',
        'diskon_amount' => 'decimal:2',
    ];

    /** @return BelongsTo<Purchase, $this> */
    public function purchase(): BelongsTo
    {
        return $this->belongsTo(Purchase::class);
    }

    /** @return HasMany<BundleItem, $this> */
    public function bundleItems(): HasMany
    {
        return $this->hasMany(BundleItem::class, 'bundle_id');
    }

    /** @return BelongsToMany<PurchaseItem, $this> */
    public function purchaseItems(): BelongsToMany
    {
        return $this->belongsToMany(
            PurchaseItem::class,
            'bundle_items',
            'bundle_id',
            'purchase_item_id',
        );
    }
}
