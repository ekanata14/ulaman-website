<?php

namespace App\Actions\Supplier;

use App\DTOs\Supplier\SupplierData;
use App\Models\Supplier;

/**
 * Menyelesaikan supplier dari DTO: pakai id bila ada, jika tidak cari/buat
 * berdasarkan nama. Supplier kosong → fallback "Lain-lain" (§F-02.1).
 */
class ResolveSupplier
{
    public function execute(?SupplierData $data): ?Supplier
    {
        if ($data === null) {
            return null;
        }

        if ($data->id !== null) {
            return Supplier::find($data->id);
        }

        $nama = trim($data->nama);
        if ($nama === '') {
            $nama = 'Lain-lain';
        }

        return Supplier::firstOrCreate(
            ['nama' => $nama],
            [
                'alamat' => $data->alamat,
                'telepon' => $data->telepon,
                'pic' => $data->pic,
                'catatan' => $data->catatan,
                'is_active' => true,
            ],
        );
    }
}
