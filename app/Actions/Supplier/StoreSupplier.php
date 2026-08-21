<?php

namespace App\Actions\Supplier;

use App\DTOs\Supplier\SupplierData;
use App\Models\Supplier;

class StoreSupplier
{
    public function execute(SupplierData $data): Supplier
    {
        return Supplier::create([
            'nama' => trim($data->nama),
            'alamat' => $data->alamat,
            'telepon' => $data->telepon,
            'pic' => $data->pic,
            'catatan' => $data->catatan,
            'is_active' => $data->isActive,
        ]);
    }
}
