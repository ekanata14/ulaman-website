<?php

namespace App\Models;

use App\Enums\DiscountType;
use App\Enums\PaymentMethod;
use App\Enums\PurchaseStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Purchase extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'kode',
        'tanggal',
        'supplier_id',
        'nomor_nota',
        'category_id',
        'metode_bayar',
        'remark',
        'status',
        'subtotal',
        'total_diskon_item',
        'total_diskon_bundle',
        'diskon_nota_tipe',
        'diskon_nota_nilai',
        'diskon_nota_amount',
        'grand_total',
        'needs_review',
        'project_id',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'tanggal' => 'date',
        'status' => PurchaseStatus::class,
        'metode_bayar' => PaymentMethod::class,
        'diskon_nota_tipe' => DiscountType::class,
        'needs_review' => 'boolean',
        'subtotal' => 'decimal:2',
        'total_diskon_item' => 'decimal:2',
        'total_diskon_bundle' => 'decimal:2',
        'diskon_nota_nilai' => 'decimal:2',
        'diskon_nota_amount' => 'decimal:2',
        'grand_total' => 'decimal:2',
    ];

    /** @return BelongsTo<Supplier, $this> */
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    /** @return BelongsTo<Category, $this> */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /** @return BelongsTo<User, $this> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** @return BelongsTo<User, $this> */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /** @return HasMany<PurchaseItem, $this> */
    public function items(): HasMany
    {
        return $this->hasMany(PurchaseItem::class);
    }

    /** @return HasMany<PurchaseBundle, $this> */
    public function bundles(): HasMany
    {
        return $this->hasMany(PurchaseBundle::class);
    }

    /** @return HasMany<PurchasePhoto, $this> */
    public function photos(): HasMany
    {
        return $this->hasMany(PurchasePhoto::class);
    }

    /**
     * @param  Builder<Purchase>  $query
     * @return Builder<Purchase>
     */
    public function scopeFinal(Builder $query): Builder
    {
        return $query->where('status', PurchaseStatus::FINAL->value);
    }

    /**
     * @param  Builder<Purchase>  $query
     * @return Builder<Purchase>
     */
    public function scopePeriod(Builder $query, string $from, string $to): Builder
    {
        return $query->whereBetween('tanggal', [$from, $to]);
    }

    /**
     * @param  Builder<Purchase>  $query
     * @param  array<int, int>  $supplierIds
     * @return Builder<Purchase>
     */
    public function scopeForSupplier(Builder $query, array $supplierIds): Builder
    {
        return $query->whereIn('supplier_id', $supplierIds);
    }

    /**
     * @param  Builder<Purchase>  $query
     * @return Builder<Purchase>
     */
    public function scopeSearch(Builder $query, string $term): Builder
    {
        return $query->where(function (Builder $q) use ($term): void {
            $q->where('kode', 'like', "%{$term}%")
                ->orWhere('nomor_nota', 'like', "%{$term}%")
                ->orWhere('remark', 'like', "%{$term}%");
        });
    }
}
