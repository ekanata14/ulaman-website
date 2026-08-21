<?php

namespace App\Actions\Supplier;

use App\Models\Supplier;
use Illuminate\Support\Collection;

/**
 * §F-02.1 — Deteksi supplier dengan nama mirip (≥ threshold%) via similar_text.
 * Dipakai untuk peringatan quick-add & import.
 */
class DetectSimilarSupplier
{
    /**
     * @return Collection<int, Supplier>
     */
    public function execute(string $nama, int $threshold = 85): Collection
    {
        $target = mb_strtolower(trim($nama));

        if ($target === '') {
            return collect();
        }

        return Supplier::query()
            ->get()
            ->filter(function (Supplier $supplier) use ($target, $threshold): bool {
                $percent = 0.0;
                similar_text($target, mb_strtolower($supplier->nama), $percent);

                return $percent >= $threshold;
            })
            ->values();
    }
}
