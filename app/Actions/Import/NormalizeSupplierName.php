<?php

namespace App\Actions\Import;

/**
 * §F-09 — Normalisasi nama supplier dari sumber Excel yang bervariasi ejaan.
 * Trim; kosong → "Lain-lain". Pemetaan case-insensitive (kunci lowercase-trim).
 * Jika tak ada di peta, kembalikan nama asli yang di-trim (mempertahankan case).
 */
class NormalizeSupplierName
{
    /** @var array<string, string> */
    private const MAP = [
        'murda jaya' => 'Murda Jaya',
        'murday jaya' => 'Murda Jaya',
        'muda jaya' => 'Murda Jaya',
        'mitra 10' => 'Mitra 10',
        'mitra10' => 'Mitra 10',
        'harco' => 'CV. Harco Bali Lampu',
        'harco bali lampu' => 'CV. Harco Bali Lampu',
        'cv. harco bali lampu' => 'CV. Harco Bali Lampu',
        'ud. sari arta' => 'UD. Sari Arta',
        'sari arta' => 'UD. Sari Arta',
        'arta sari' => 'UD. Sari Arta',
        'pat warna' => 'Pat Warna Lamp Production',
        'pat warna lamp production' => 'Pat Warna Lamp Production',
        'dewata bali safety' => 'Dewata Bali Safety',
        'wisnu pickup transport' => 'Wisnu Transport',
        'wisnu transport' => 'Wisnu Transport',
    ];

    public function execute(?string $raw): string
    {
        $trimmed = trim((string) $raw);

        if ($trimmed === '') {
            return 'Lain-lain';
        }

        $key = mb_strtolower($trimmed);

        return self::MAP[$key] ?? $trimmed;
    }
}
