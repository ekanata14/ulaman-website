<?php

namespace Database\Seeders;

use App\Models\Unit;
use Illuminate\Database\Seeder;

class UnitSeeder extends Seeder
{
    /**
     * 19 satuan sesuai §F-02.3 PRD.
     */
    public function run(): void
    {
        $units = [
            'sak', 'batang', 'lembar', 'm3', 'm2', 'm', 'kg', 'liter', 'buah',
            'set', 'roll', 'dus', 'drum', 'unit', 'rit', 'paket', 'kaleng', 'pcs', 'ls',
        ];

        foreach ($units as $nama) {
            Unit::firstOrCreate(['nama' => $nama]);
        }
    }
}
