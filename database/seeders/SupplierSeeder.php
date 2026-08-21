<?php

namespace Database\Seeders;

use App\Models\Supplier;
use Illuminate\Database\Seeder;

class SupplierSeeder extends Seeder
{
    /**
     * Nama supplier kanonik hasil normalisasi §F-09 + fallback "Lain-lain".
     * Universe supplier selebihnya dibuat saat import Excel (Fase 5).
     */
    public function run(): void
    {
        $suppliers = [
            'Murda Jaya',
            'Mitra 10',
            'CV. Harco Bali Lampu',
            'UD. Sari Arta',
            'Pat Warna Lamp Production',
            'Dewata Bali Safety',
            'Wisnu Transport',
            'Lain-lain',
        ];

        foreach ($suppliers as $nama) {
            Supplier::firstOrCreate(['nama' => $nama], ['is_active' => true]);
        }
    }
}
