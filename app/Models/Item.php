<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Item extends Model
{
    protected $fillable = [
        'nama',
        'unit_id',
        'harga_terakhir',
        'supplier_terakhir_id',
    ];

    protected $casts = [
        'harga_terakhir' => 'decimal:2',
    ];

    /** @return BelongsTo<Unit, $this> */
    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    /** @return BelongsTo<Supplier, $this> */
    public function supplierTerakhir(): BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'supplier_terakhir_id');
    }

    /** @return HasMany<PurchaseItem, $this> */
    public function purchaseItems(): HasMany
    {
        return $this->hasMany(PurchaseItem::class);
    }
}
