<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchasePhoto extends Model
{
    protected $fillable = [
        'purchase_id',
        'jenis',
        'path',
        'thumbnail_path',
        'nama_file_asli',
        'mime_type',
        'ukuran',
        'urutan',
        'uploaded_by',
    ];

    protected $casts = [
        'ukuran' => 'integer',
        'urutan' => 'integer',
    ];

    /** @return BelongsTo<Purchase, $this> */
    public function purchase(): BelongsTo
    {
        return $this->belongsTo(Purchase::class);
    }

    /** @return BelongsTo<User, $this> */
    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
