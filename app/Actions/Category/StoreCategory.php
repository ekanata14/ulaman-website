<?php

namespace App\Actions\Category;

use App\DTOs\Category\CategoryData;
use App\Models\Category;

/**
 * Membuat kategori baru. Dipakai oleh CRUD Kategori dan quick-add di form nota.
 */
class StoreCategory
{
    public function execute(CategoryData $data): Category
    {
        return Category::create([
            'nama' => trim($data->nama),
            'warna' => $data->warna,
        ]);
    }
}
