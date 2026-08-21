<?php

namespace App\Actions\Import;

use App\Support\Money;

/**
 * §F-09 — Rekonsiliasi total sistem (hasil StorePurchase) vs total resmi Excel,
 * per sheet. Murni, tanpa DB. delta = excel − system (skala 2).
 */
class ReconcileImportTotals
{
    /**
     * @param  array<string, string>  $sheetTotals  total resmi Excel per sheet
     * @param  array<string, string>  $systemBySheet  total sistem per sheet
     * @return array{
     *   rows: array<int, array{sheet:string, system:string, excel:string, delta:string}>,
     *   totalSystem:string, totalExcel:string, totalDelta:string
     * }
     */
    public function execute(array $sheetTotals, array $systemBySheet): array
    {
        $sheets = array_keys($sheetTotals + $systemBySheet);

        $rows = [];
        $totalSystem = Money::zero();
        $totalExcel = Money::zero();

        foreach ($sheets as $sheet) {
            $system = Money::add($systemBySheet[$sheet] ?? '0', '0');
            $excel = Money::add($sheetTotals[$sheet] ?? '0', '0');
            $delta = Money::sub($excel, $system);

            $rows[] = [
                'sheet' => $sheet,
                'system' => $system,
                'excel' => $excel,
                'delta' => $delta,
            ];

            $totalSystem = Money::add($totalSystem, $system);
            $totalExcel = Money::add($totalExcel, $excel);
        }

        return [
            'rows' => $rows,
            'totalSystem' => $totalSystem,
            'totalExcel' => $totalExcel,
            'totalDelta' => Money::sub($totalExcel, $totalSystem),
        ];
    }
}
