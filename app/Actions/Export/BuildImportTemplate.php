<?php

namespace App\Actions\Export;

use App\Actions\Import\ParsePurchaseExcel;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * §F-09 — Menghasilkan contoh template Excel impor yang FORMATNYA cocok persis
 * dengan {@see ParsePurchaseExcel}: sheet contoh bernama salah satu dari 12 nama
 * bulanan (tahun dari nama sheet), header kolom B–K, tanggal Excel asli, kolom
 * diskon item/nota, dan baris TOTAL. Ditambah sheet "Petunjuk" (diabaikan importer
 * karena namanya bukan bagian dari 12 nama bulanan). Nilai contoh adalah literal
 * integer — bukan uang yang mengalir di aplikasi.
 */
class BuildImportTemplate
{
    /** Sheet contoh yang diisi; harus salah satu dari ParsePurchaseExcel::MONTHLY_SHEETS. */
    private const CONTOH_SHEET = 'September 2025';

    /** @var array<string, string> Header kolom data (kolom A sengaja kosong). */
    private const HEADERS = [
        'B' => 'Date',
        'C' => 'Supplier',
        'D' => 'Description',
        'E' => 'Qty',
        'F' => 'Unit Price',
        'G' => 'Total',
        'H' => 'Item Disc Type',
        'I' => 'Item Disc Value',
        'J' => 'Nota Disc Type',
        'K' => 'Nota Disc Value',
    ];

    public function execute(): BinaryFileResponse
    {
        $spreadsheet = new Spreadsheet;

        $petunjuk = $spreadsheet->getSheet(0);
        $petunjuk->setTitle('Petunjuk');
        $this->writePetunjuk($petunjuk);

        $contoh = $spreadsheet->createSheet(1);
        $contoh->setTitle(self::CONTOH_SHEET);
        $this->writeHeader($contoh);
        $this->writeExampleRows($contoh);
        $this->applyColumnWidths($contoh);

        $spreadsheet->setActiveSheetIndex(0);

        $path = tempnam(sys_get_temp_dir(), 'upl_tpl_').'.xlsx';
        (new Xlsx($spreadsheet))->save($path);
        $spreadsheet->disconnectWorksheets();

        return response()
            ->download($path, 'template-import-ulaman.xlsx')
            ->deleteFileAfterSend(true);
    }

    private function writePetunjuk(Worksheet $sheet): void
    {
        $daftarSheet = implode(', ', ParsePurchaseExcel::MONTHLY_SHEETS);

        $lines = [
            ['PETUNJUK PENGISIAN TEMPLATE IMPOR — ULAMAN PURCHASE LOG', true],
            ['', false],
            ['ALUR: Admin → menu Impor → Upload file ini → Preview & Peringatan → Import.', false],
            ['', false],
            ['1. NAMA SHEET (WAJIB TEPAT)', true],
            ['   - Sistem HANYA membaca sheet yang namanya persis salah satu dari 12 nama bulan berikut:', false],
            ['     '.$daftarSheet, false],
            ['   - Sheet "Petunjuk" ini diabaikan oleh importer (aman untuk dokumentasi).', false],
            ['   - Untuk bulan lain: duplikat sheet contoh "'.self::CONTOH_SHEET.'" lalu ganti namanya ke salah satu nama di atas.', false],
            ['   - TAHUN pada nama sheet HARUS sama dengan tahun tanggal isian (mis. sheet "Januari 2026" berisi tanggal tahun 2026).', false],
            ['', false],
            ['2. KOLOM (mulai kolom B; kolom A dibiarkan kosong)', true],
            ['   B Date          : Tanggal nota. GUNAKAN TANGGAL EXCEL ASLI (format yyyy-mm-dd), JANGAN teks.', false],
            ['   C Supplier      : Nama supplier (akan dinormalisasi ke nama baku bila dikenal).', false],
            ['   D Description   : Nama/uraian barang. Baris tanpa deskripsi diabaikan.', false],
            ['   E Qty           : Jumlah.', false],
            ['   F Unit Price    : Harga satuan (informatif; sistem tetap merekonstruksi dari Total ÷ Qty).', false],
            ['   G Total         : Total baris KOTOR = Qty × Unit Price. Kolom ini otoritatif.', false],
            ['   H Item Disc Type: Diskon per item — kosongkan, atau isi PERSEN / NOMINAL.', false],
            ['   I Item Disc Value: Nilai diskon item — angka persen (bila PERSEN) atau rupiah (bila NOMINAL).', false],
            ['   J Nota Disc Type: Diskon tingkat nota — isi HANYA di baris pertama nota; PERSEN / NOMINAL / kosong.', false],
            ['   K Nota Disc Value: Nilai diskon nota — angka persen atau rupiah.', false],
            ['', false],
            ['3. POLA SATU NOTA BEBERAPA BARIS', true],
            ['   - Baris PERTAMA sebuah nota WAJIB berisi Date (B) dan Supplier (C).', false],
            ['   - Baris berikutnya untuk nota yang sama: KOSONGKAN Date & Supplier (cukup isi item).', false],
            ['   - Nota baru dimulai lagi saat kolom Date (B) diisi kembali.', false],
            ['', false],
            ['4. BARIS TOTAL (opsional tapi disarankan, untuk rekonsiliasi)', true],
            ['   - Di akhir tiap sheet, buat satu baris: kolom B = TOTAL, kolom G = jumlah semua Total kotor pada sheet itu.', false],
            ['   - Saat impor, TOTAL Excel (kotor) dibandingkan dengan Subtotal sistem (kotor) — seharusnya SAMA (delta 0).', false],
            ['   - "Grand total setelah diskon" ditampilkan terpisah; wajar lebih kecil dari TOTAL bila ada diskon.', false],
            ['', false],
            ['5. DISKON & BUNDLE', true],
            ['   - Diskon item (H/I) dan diskon nota (J/K) diterapkan otomatis saat impor.', false],
            ['   - Bundle/paket TIDAK diisi di Excel: nota UD. Harta Ayu otomatis jadi bundle; bundle lain diatur di aplikasi setelah impor.', false],
            ['', false],
            ['Lihat sheet "'.self::CONTOH_SHEET.'" untuk contoh yang sudah terisi.', false],
        ];

        $row = 1;
        foreach ($lines as [$text, $bold]) {
            $sheet->setCellValue('A'.$row, $text);
            if ($bold === true) {
                $sheet->getStyle('A'.$row)->getFont()->setBold(true);
            }
            $row++;
        }

        $sheet->getColumnDimension('A')->setWidth(110);
    }

    private function writeHeader(Worksheet $sheet): void
    {
        foreach (self::HEADERS as $col => $label) {
            $sheet->setCellValue($col.'1', $label);
            $sheet->getStyle($col.'1')->getFont()->setBold(true);
        }
    }

    private function writeExampleRows(Worksheet $sheet): void
    {
        // Nota 1: 2 item, diskon item di baris pertama + diskon nota (baris pertama).
        $this->writeItemRow($sheet, 2, '2025-09-05', 'Murda Jaya', 'Semen 40kg', 10, 65000, 650000, 'PERSEN', 10, 'NOMINAL', 50000);
        $this->writeItemRow($sheet, 3, null, null, 'Pasir 1 truk', 1, 1500000, 1500000, null, null, null, null);

        // Nota 2: 1 item tanpa diskon.
        $this->writeItemRow($sheet, 4, '2025-09-08', 'Mitra 10', 'Kuas 3"', 5, 25000, 125000, null, null, null, null);

        // Baris TOTAL: kolom B == "TOTAL", kolom G == Σ Total kotor (650000+1500000+125000).
        $sheet->setCellValue('B5', 'TOTAL');
        $sheet->getStyle('B5')->getFont()->setBold(true);
        $sheet->setCellValue('G5', 2275000);
        $sheet->getStyle('G5')->getFont()->setBold(true);
    }

    private function writeItemRow(
        Worksheet $sheet,
        int $row,
        ?string $date,
        ?string $supplier,
        string $description,
        int $qty,
        int $unitPrice,
        int $total,
        ?string $itemDiscType,
        ?int $itemDiscValue,
        ?string $notaDiscType,
        ?int $notaDiscValue,
    ): void {
        if ($date !== null) {
            $sheet->setCellValue('B'.$row, ExcelDate::PHPToExcel(new \DateTimeImmutable($date)));
            $sheet->getStyle('B'.$row)->getNumberFormat()->setFormatCode('yyyy-mm-dd');
        }

        if ($supplier !== null) {
            $sheet->setCellValue('C'.$row, $supplier);
        }

        $sheet->setCellValueExplicit('D'.$row, $description, DataType::TYPE_STRING);
        $sheet->setCellValue('E'.$row, $qty);
        $sheet->setCellValue('F'.$row, $unitPrice);
        $sheet->setCellValue('G'.$row, $total);

        if ($itemDiscType !== null) {
            $sheet->setCellValue('H'.$row, $itemDiscType);
            $sheet->setCellValue('I'.$row, $itemDiscValue);
        }

        if ($notaDiscType !== null) {
            $sheet->setCellValue('J'.$row, $notaDiscType);
            $sheet->setCellValue('K'.$row, $notaDiscValue);
        }
    }

    private function applyColumnWidths(Worksheet $sheet): void
    {
        $widths = ['B' => 14, 'C' => 22, 'D' => 28, 'E' => 8, 'F' => 14, 'G' => 16, 'H' => 16, 'I' => 16, 'J' => 16, 'K' => 16];
        foreach ($widths as $col => $width) {
            $sheet->getColumnDimension($col)->setWidth($width);
        }
    }
}
