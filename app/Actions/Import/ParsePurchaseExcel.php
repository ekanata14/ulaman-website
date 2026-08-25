<?php

namespace App\Actions\Import;

use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * §F-09 — Parsing murni sumber Excel "Ulaman Renovation" menjadi struktur nota.
 * TIDAK menulis DB dan TIDAK menghitung total (kalkulasi milik StorePurchase).
 * Nilai formula dibaca dari CACHE (getOldCalculatedValue), bukan direkalkulasi.
 */
class ParsePurchaseExcel
{
    /** Ke-12 sheet bulanan yang diimpor (urutan kronologis). */
    public const MONTHLY_SHEETS = [
        'September 2025', 'Oktober 2025', 'November 2025', 'Desember 2025',
        'Januari 2026', 'Februari 2026', 'Maret 2026', 'April 2026',
        'May 2026', 'June 2026', 'July 2026', 'August 2026',
    ];

    /** Peta nama bulan Indonesia/Inggris → tahun yang tercantum di judul sheet. */
    private const COL_DATE = 2;

    private const COL_SUPPLIER = 3;

    private const COL_DESC = 4;

    private const COL_QTY = 5;

    private const COL_PRICE = 6;

    private const COL_TOTAL = 7;

    private const COL_DISKON_ITEM_TIPE = 8;

    private const COL_DISKON_ITEM_NILAI = 9;

    private const COL_DISKON_NOTA_TIPE = 10;

    private const COL_DISKON_NOTA_NILAI = 11;

    public function __construct(
        private readonly NormalizeSupplierName $normalizeSupplier,
    ) {}

    /**
     * @return array{
     *   notas: array<int, array<string, mixed>>,
     *   sheetTotals: array<string, string>,
     *   warnings: array<int, string>,
     *   stats: array{notas:int, items:int}
     * }
     */
    public function execute(string $path): array
    {
        $reader = IOFactory::createReaderForFile($path);
        $reader->setReadDataOnly(false);
        $spreadsheet = $reader->load($path);

        $notas = [];
        $sheetTotals = [];
        $warnings = [];
        $uidCounter = 0;

        foreach (self::MONTHLY_SHEETS as $sheetName) {
            $sheet = $spreadsheet->getSheetByName($sheetName);
            if ($sheet === null) {
                $warnings[] = "Sheet '{$sheetName}' tidak ditemukan; dilewati.";

                continue;
            }

            $sheetYear = $this->sheetYear($sheetName);
            $this->parseSheet(
                $sheet,
                $sheetName,
                $sheetYear,
                $notas,
                $sheetTotals,
                $warnings,
                $uidCounter,
            );
        }

        $itemCount = 0;
        foreach ($notas as $nota) {
            $itemCount += count($nota['items']);
        }

        return [
            'notas' => $notas,
            'sheetTotals' => $sheetTotals,
            'warnings' => $warnings,
            'stats' => ['notas' => count($notas), 'items' => $itemCount],
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $notas
     * @param  array<string, string>  $sheetTotals
     * @param  array<int, string>  $warnings
     */
    private function parseSheet(
        Worksheet $sheet,
        string $sheetName,
        ?int $sheetYear,
        array &$notas,
        array &$sheetTotals,
        array &$warnings,
        int &$uidCounter,
    ): void {
        $highestRow = $sheet->getHighestDataRow();

        $headerRow = $this->findHeaderRow($sheet);
        if ($headerRow === null) {
            $warnings[] = "Sheet '{$sheetName}': baris header 'Date' tidak ditemukan; dilewati.";

            return;
        }

        // Temukan baris TOTAL terakhir (col B trimmed upper == 'TOTAL').
        $totalRow = null;
        for ($r = $headerRow + 1; $r <= $highestRow; $r++) {
            $b = strtoupper(trim((string) $this->rawValue($sheet, self::COL_DATE, $r)));
            if ($b === 'TOTAL') {
                $totalRow = $r;
            }
        }

        if ($totalRow !== null) {
            $sheetTotals[$sheetName] = $this->scale2(
                $this->cellNumeric($sheet, self::COL_TOTAL, $totalRow) ?? '0',
            );
        }

        $currentIndex = null; // indeks nota aktif di $notas

        for ($r = $headerRow + 1; $r <= $highestRow; $r++) {
            if ($totalRow !== null && $r === $totalRow) {
                continue; // baris TOTAL bukan data
            }

            $deskripsi = trim((string) $this->cellString($sheet, self::COL_DESC, $r));
            if ($deskripsi === '') {
                continue; // baris filler =E*F tanpa deskripsi
            }

            $dateRaw = $this->rawValue($sheet, self::COL_DATE, $r);
            $hasDate = $dateRaw !== null && trim((string) $dateRaw) !== '';

            $qty = $this->parseQty($this->cellNumeric($sheet, self::COL_QTY, $r));
            $price = $this->cellNumeric($sheet, self::COL_PRICE, $r);
            $total = $this->cellNumeric($sheet, self::COL_TOTAL, $r);

            // §F-09.7 — deteksi mismatch qty×harga vs Total (pakai qty & price asli).
            $rowMismatch = false;
            if ($price !== null && $total !== null && bccomp($qty, '0', 2) !== 0) {
                $rowMismatch = bccomp($this->scale2(bcmul($qty, $price, 4)), $this->scale2($total), 2) !== 0;
            }

            [$storeQty, $hargaSatuan] = $this->computeQtyAndHarga($qty, $price, $total);

            if ($hasDate || $currentIndex === null) {
                // Baris ini MEMULAI nota baru.
                $tanggal = $this->parseDate($sheet, self::COL_DATE, $r);
                $supplier = $this->normalizeSupplier->execute(
                    $this->cellString($sheet, self::COL_SUPPLIER, $r),
                );

                if ($tanggal === null) {
                    $tanggal = $sheetYear !== null ? sprintf('%04d-01-01', $sheetYear) : '1970-01-01';
                    $warnings[] = "Sheet '{$sheetName}' baris {$r}: tanggal nota tidak terbaca; memakai fallback.";
                }

                $notas[] = [
                    'sheet' => $sheetName,
                    'tanggal' => $tanggal,
                    'supplier' => $supplier,
                    'nomorNota' => null,
                    'needsReview' => false,
                    'isBundle' => false,
                    'diskonNotaTipe' => $this->normalizeDiscountType(
                        $this->cellString($sheet, self::COL_DISKON_NOTA_TIPE, $r),
                    ),
                    'diskonNotaNilai' => $this->parseDiscountValue(
                        $this->cellNumeric($sheet, self::COL_DISKON_NOTA_NILAI, $r),
                    ),
                    'items' => [],
                ];
                $currentIndex = count($notas) - 1;
            }

            $uid = $sheetName.'#'.$r.'#'.(++$uidCounter);

            $notas[$currentIndex]['items'][] = [
                'uid' => $uid,
                'deskripsi' => $deskripsi,
                'qty' => $storeQty,
                'hargaSatuan' => $hargaSatuan,
                'diskonTipe' => $this->normalizeDiscountType(
                    $this->cellString($sheet, self::COL_DISKON_ITEM_TIPE, $r),
                ),
                'diskonNilai' => $this->parseDiscountValue(
                    $this->cellNumeric($sheet, self::COL_DISKON_ITEM_NILAI, $r),
                ),
            ];

            // §F-09.7 — mismatch qty*price vs total.
            if ($rowMismatch) {
                $notas[$currentIndex]['needsReview'] = true;
                $warnings[] = "Sheet '{$sheetName}' baris {$r}: qty×harga ("
                    .$this->scale2(bcmul($qty, (string) $price, 4)).') ≠ Total ('
                    .$this->scale2((string) $total).') pada "'.$deskripsi.'".';
            }

            // §F-09.7 — tahun tanggal ≠ tahun sheet.
            if ($sheetYear !== null) {
                $notaYear = (int) substr((string) $notas[$currentIndex]['tanggal'], 0, 4);
                if ($notaYear !== $sheetYear) {
                    $notas[$currentIndex]['needsReview'] = true;
                    $warnings[] = "Sheet '{$sheetName}' baris {$r}: tahun tanggal ({$notaYear}) "
                        ."≠ tahun sheet ({$sheetYear}); ditandai untuk ditinjau.";
                }
            }
        }

        $this->applyHartaAyuBundle($notas, $warnings);
    }

    /**
     * §F-09.10 — UD. Harta Ayu → bundle HARGA_PAKET Rp 26.000.000.
     *
     * @param  array<int, array<string, mixed>>  $notas
     * @param  array<int, string>  $warnings
     */
    private function applyHartaAyuBundle(array &$notas, array &$warnings): void
    {
        foreach ($notas as $index => $nota) {
            if ($nota['supplier'] !== 'UD. Harta Ayu' || $nota['isBundle'] === true) {
                continue;
            }

            $qtyByUid = [];
            $uids = [];
            foreach ($nota['items'] as $item) {
                $qtyByUid[$item['uid']] = $item['qty'];
                $uids[] = $item['uid'];
            }

            if (count($uids) < 2) {
                continue;
            }

            $split = app(\App\Actions\Calculation\SplitPackagePriceByQty::class)
                ->execute($qtyByUid, '26000000');

            foreach ($notas[$index]['items'] as $i => $item) {
                $notas[$index]['items'][$i]['hargaSatuan'] = $split[$item['uid']];
            }

            $notas[$index]['isBundle'] = true;
            $notas[$index]['bundle'] = [
                'nama' => 'Paket Marmer',
                'tipe' => \App\Enums\BundleType::HARGA_PAKET,
                'nilai' => '26000000',
                'itemUids' => $uids,
            ];

            $warnings[] = 'Nota UD. Harta Ayu dikonversi menjadi bundle HARGA_PAKET Rp 26.000.000 ('
                .count($uids).' item).';
        }
    }

    /**
     * §F-09.6 — qty & hargaSatuan impor. Total kolom G bersifat OTORITATIF:
     * StorePurchase (total = qty × harga @ skala 2) HARUS menghasilkan angka
     * yang sama persis dengan kolom Total agar penjumlahan sheet cocok.
     *
     *   - total ada & qty≠0 & (total/qty) habis di 2 desimal → qty, harga=total/qty.
     *   - total ada tapi tak habis dibagi (baris mismatch) → qty=1, harga=total
     *     (mempertahankan total baris persis; qty asli sudah ditandai needs_review).
     *   - total null → qty apa adanya, harga=price (bila ada) atau null.
     *
     * @return array{0:string, 1:?string} [qty, hargaSatuan]
     */
    private function computeQtyAndHarga(string $qty, ?string $price, ?string $total): array
    {
        if ($total !== null && bccomp($qty, '0', 2) !== 0) {
            $harga = bcdiv($total, $qty, 2);

            if (bccomp(bcmul($qty, $harga, 2), $this->scale2($total), 2) === 0) {
                return [$qty, $harga];
            }

            // Total tak terbagi rata di 2 desimal: pertahankan total baris otoritatif.
            return ['1', $this->scale2($total)];
        }

        return [$qty, $price !== null ? (string) $price : null];
    }

    private function parseQty(?string $qty): string
    {
        if ($qty === null || trim($qty) === '') {
            return '1';
        }

        return $qty;
    }

    /**
     * §F-09 — Normalisasi teks tipe diskon Excel → nilai enum DiscountType.
     * Nilai kosong/tak dikenal dianggap 'NONE' (impor tetap aman).
     */
    private function normalizeDiscountType(?string $raw): string
    {
        return match (strtoupper(trim((string) $raw))) {
            'PERSEN', 'PERCENT', '%' => 'PERSEN',
            'NOMINAL', 'RP', 'RUPIAH' => 'NOMINAL',
            default => 'NONE',
        };
    }

    /** Nilai diskon sebagai string bcmath; kosong → '0'. */
    private function parseDiscountValue(?string $value): string
    {
        if ($value === null || trim($value) === '') {
            return '0';
        }

        return $value;
    }

    private function findHeaderRow(Worksheet $sheet): ?int
    {
        for ($r = 1; $r <= 7; $r++) {
            $b = trim((string) $this->rawValue($sheet, self::COL_DATE, $r));
            if (strcasecmp($b, 'Date') === 0) {
                return $r;
            }
        }

        return null;
    }

    private function sheetYear(string $sheetName): ?int
    {
        if (preg_match('/(\d{4})/', $sheetName, $m) === 1) {
            return (int) $m[1];
        }

        return null;
    }

    private function cell(Worksheet $sheet, int $col, int $row): Cell
    {
        return $sheet->getCell([$col, $row]);
    }

    private function rawValue(Worksheet $sheet, int $col, int $row): mixed
    {
        return $this->cell($sheet, $col, $row)->getValue();
    }

    /** Nilai terhitung dari cache untuk formula; nilai literal untuk sel biasa. */
    private function cachedValue(Worksheet $sheet, int $col, int $row): mixed
    {
        $cell = $this->cell($sheet, $col, $row);

        return $cell->isFormula() ? $cell->getOldCalculatedValue() : $cell->getValue();
    }

    private function cellString(Worksheet $sheet, int $col, int $row): ?string
    {
        $v = $this->cachedValue($sheet, $col, $row);

        if ($v === null) {
            return null;
        }

        return (string) $v;
    }

    /** Nilai numerik sebagai string bcmath, atau null bila kosong/non-numerik. */
    private function cellNumeric(Worksheet $sheet, int $col, int $row): ?string
    {
        $v = $this->cachedValue($sheet, $col, $row);

        if ($v === null || $v === '') {
            return null;
        }

        if (is_int($v) || is_float($v)) {
            return $this->numberToString($v);
        }

        if (is_string($v) && is_numeric(trim($v))) {
            return $this->numberToString(trim($v) + 0);
        }

        return null;
    }

    private function numberToString(int|float|string $n): string
    {
        if (is_int($n)) {
            return (string) $n;
        }

        // Hindari notasi ilmiah; pertahankan presisi wajar.
        $s = number_format((float) $n, 6, '.', '');

        // Rapikan trailing zero.
        if (str_contains($s, '.')) {
            $s = rtrim(rtrim($s, '0'), '.');
        }

        return $s === '' ? '0' : $s;
    }

    private function scale2(string $value): string
    {
        return bcadd($value, '0', 2);
    }

    private function parseDate(Worksheet $sheet, int $col, int $row): ?string
    {
        $cell = $this->cell($sheet, $col, $row);
        $value = $cell->getValue();

        if ($value === null || trim((string) $value) === '') {
            return null;
        }

        if (ExcelDate::isDateTime($cell) && is_numeric($value)) {
            return ExcelDate::excelToDateTimeObject((float) $value)->format('Y-m-d');
        }

        if (is_numeric($value)) {
            return ExcelDate::excelToDateTimeObject((float) $value)->format('Y-m-d');
        }

        $ts = strtotime((string) $value);
        if ($ts !== false) {
            return date('Y-m-d', $ts);
        }

        return null;
    }
}
