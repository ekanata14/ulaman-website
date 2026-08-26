<?php

namespace App\Actions\Item;

use App\DTOs\Item\ItemData;
use App\Models\Item;

class UpdateItem
{
    public function execute(Item $item, ItemData $data): Item
    {
        $item->update([
            'nama' => trim($data->nama),
            'unit_id' => $data->unitId,
        ]);

        return $item;
    }
}
