<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /**
     * 13 kategori biaya sesuai §F-02.4 PRD.
     */
    public function run(): void
    {
        $categories = [
            'Material Struktur',
            'Material Finishing',
            'Kayu',
            'Batu & Pasir',
            'Listrik & Lampu',
            'Sanitasi & Plumbing',
            'Furnitur',
            'Tanaman & Lansekap',
            'Alat & Consumable',
            'Transport & Ongkir',
            'Jasa & Upah',
            'Utilitas',
            'Lain-lain',
        ];

        foreach ($categories as $nama) {
            Category::firstOrCreate(['nama' => $nama]);
        }
    }
}
