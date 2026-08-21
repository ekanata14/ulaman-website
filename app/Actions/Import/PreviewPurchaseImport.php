<?php

namespace App\Actions\Import;

use App\DTOs\Import\ImportPreviewData;
use App\Support\Money;

/**
 * §10.5 / §F-09 — Pratinjau impor: parse (tanpa DB), agregasi statistik,
 * peringatan, dan total resmi Excel per sheet. totalNilai = Σ total sheet Excel.
 */
class PreviewPurchaseImport
{
    public function __construct(
        private readonly ParsePurchaseExcel $parse,
    ) {}

    public function execute(string $path): ImportPreviewData
    {
        $result = $this->parse->execute($path);

        $totalNilai = Money::zero();
        $perSheet = [];
        foreach ($result['sheetTotals'] as $sheet => $excelTotal) {
            $totalNilai = Money::add($totalNilai, $excelTotal);
            $perSheet[] = ['sheet' => $sheet, 'excelTotal' => $excelTotal];
        }

        return new ImportPreviewData(
            totalNota: $result['stats']['notas'],
            totalItem: $result['stats']['items'],
            totalNilai: $totalNilai,
            warnings: $result['warnings'],
            perSheet: $perSheet,
        );
    }
}
