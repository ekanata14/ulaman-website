<?php

namespace App\DTOs\Import;

/**
 * Statistik & peringatan hasil pratinjau import Excel (§10.5).
 */
class ImportPreviewData
{
    /**
     * @param  array<int, string>  $warnings
     * @param  array<int, array<string, mixed>>  $perSheet
     */
    public function __construct(
        public int $totalNota,
        public int $totalItem,
        public string $totalNilai,
        public array $warnings = [],
        public array $perSheet = [],
    ) {}
}
