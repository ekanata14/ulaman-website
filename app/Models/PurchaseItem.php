<?php

namespace App\Models;

use App\Enums\DiscountType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class PurchaseItem extends Model
{
    protected $fillable = [
        'purchase_id',
        'item_id',
        'deskripsi',
        'qty',
        'unit_id',
        'harga_satuan',
        'harga_estimasi',
        'subtotal',
        'diskon_tipe',
        'diskon_nilai',
        'diskon_amount',
        'alokasi_diskon_bundle',
        'alokasi_diskon_nota',
        'total',
        'net_total',
        'remark',
        'urutan',
    ];

    protected $casts = [
        'qty' => 'decimal:2',
        'harga_satuan' => 'decimal:2',
        'harga_estimasi' => 'boolean',
        'diskon_tipe' => DiscountType::class,
        'subtotal' => 'decimal:2',
        'diskon_nilai' => 'decimal:2',
        'diskon_amount' => 'decimal:2',
        'alokasi_diskon_bundle' => 'decimal:2',
        'alokasi_diskon_nota' => 'decimal:2',
        'total' => 'decimal:2',
        'net_total' => 'decimal:2',
        'urutan' => 'integer',
    ];

    /** @return BelongsTo<Purchase, $this> */
    public function purchase(): BelongsTo
    {
        return $this->belongsTo(Purchase::class);
    }

    /** @return BelongsTo<Item, $this> */
    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    /** @return BelongsTo<Unit, $this> */
    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    /** @return HasOne<BundleItem, $this> */
    public function bundleItem(): HasOne
    {
        return $this->hasOne(BundleItem::class);
    }
}
