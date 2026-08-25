<?php

namespace App\Actions\Unit;

use App\DTOs\Unit\UnitData;
use App\Models\Unit;

/**
 * Membuat satuan baru. Dipakai oleh CRUD Satuan dan quick-add di form nota.
 */
class StoreUnit
{
    public function execute(UnitData $data): Unit
    {
        return Unit::create([
            'nama' => trim($data->nama),
            'simbol' => $data->simbol,
        ]);
    }
}
