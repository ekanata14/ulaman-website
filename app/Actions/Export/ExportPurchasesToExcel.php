<?php

namespace App\Actions\Export;

use App\DTOs\Purchase\PurchaseFilterData;
use App\Exports\PurchasesExport;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * §F-08 — Ekspor nota final ke Excel (.xlsx) via maatwebsite/excel.
 */
class ExportPurchasesToExcel
{
    public function execute(PurchaseFilterData $f): BinaryFileResponse
    {
        return Excel::download(new PurchasesExport($f), 'nota-pembelian.xlsx');
    }
}
