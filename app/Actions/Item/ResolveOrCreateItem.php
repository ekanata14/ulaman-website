<?php

namespace App\Actions\Item;

use App\Models\Item;

/**
 * §F-02.2 — Cari item master by nama (case-insensitive, trim);
 * buat baru bila belum ada dan $autoCreate aktif.
 */
class ResolveOrCreateItem
{
    public function execute(string $nama, bool $autoCreate = true, ?int $unitId = null, ?int $categoryId = null): ?Item
    {
        $nama = trim($nama);
        if ($nama === '') {
            return null;
        }

        $existing = Item::query()
            ->whereRaw('LOWER(nama) = ?', [mb_strtolower($nama)])
            ->first();

        if ($existing !== null) {
            return $existing;
        }

        if (! $autoCreate) {
            return null;
        }

        return Item::create([
            'nama' => $nama,
            'unit_id' => $unitId,
            'category_id' => $categoryId,
        ]);
    }
}
