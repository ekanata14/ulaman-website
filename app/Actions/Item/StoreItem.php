<?php

namespace App\Actions\Item;

use App\DTOs\Item\ItemData;
use App\Models\Item;

class StoreItem
{
    public function execute(ItemData $data): Item
    {
        return Item::create([
            'nama' => trim($data->nama),
            'unit_id' => $data->unitId,
            'category_id' => $data->categoryId,
        ]);
    }
}
