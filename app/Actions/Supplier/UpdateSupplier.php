<?php

namespace App\Actions\Supplier;

use App\DTOs\Supplier\SupplierData;
use App\Models\Supplier;

class UpdateSupplier
{
    public function execute(Supplier $supplier, SupplierData $data): Supplier
    {
        $supplier->update([
            'nama' => trim($data->nama),
            'alamat' => $data->alamat,
            'telepon' => $data->telepon,
            'pic' => $data->pic,
            'catatan' => $data->catatan,
            'is_active' => $data->isActive,
        ]);

        return $supplier;
    }
}
