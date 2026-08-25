<?php

use App\Actions\Export\BuildImportTemplate;
use App\Actions\Import\ParsePurchaseExcel;
use App\Support\Money;

function ulamanXlsxPath(): string
{
    return base_path('../docs/Ulaman Renovation.xlsx');
}

it('parses the real xlsx into 730 item rows with matching Excel sheet totals', function () {
    $result = app(ParsePurchaseExcel::class)->execute(ulamanXlsxPath());

    expect($result['stats']['items'])->toBe(730);

    $sum = Money::zero();
    foreach ($result['sheetTotals'] as $total) {
        $sum = Money::add($sum, $total);
    }

    expect($sum)->toBe('1163620562.00');
    expect($result['sheetTotals']['Oktober 2025'])->toBe('207704047.00');
    expect($result['sheetTotals']['Maret 2026'])->toBe('102297375.00');
})->skip(! file_exists(dirname(__DIR__, 4).'/docs/Ulaman Renovation.xlsx'), 'xlsx missing');

it('generates an example template that ParsePurchaseExcel can read back', function () {
    $response = app(BuildImportTemplate::class)->execute();
    $path = $response->getFile()->getPathname();

    $result = app(ParsePurchaseExcel::class)->execute($path);

    // Header terbaca di sheet contoh (tak ada peringatan "header 'Date' tidak ditemukan").
    $headerWarnings = array_filter(
        $result['warnings'],
        fn (string $w): bool => str_contains($w, "header 'Date'"),
    );
    expect($headerWarnings)->toBe([]);

    // Contoh: 2 nota, 3 item (nota 1 dua item, nota 2 satu item).
    expect($result['stats']['notas'])->toBe(2);
    expect($result['stats']['items'])->toBe(3);

    // Baris TOTAL sheet contoh terbaca (kotor).
    expect($result['sheetTotals']['September 2025'])->toBe('2275000.00');

    // Diskon item & nota ikut terparse dari kolom H–K.
    $notaSatu = $result['notas'][0];
    expect($notaSatu['diskonNotaTipe'])->toBe('NOMINAL');
    expect($notaSatu['diskonNotaNilai'])->toBe('50000');
    expect($notaSatu['items'][0]['diskonTipe'])->toBe('PERSEN');
    expect($notaSatu['items'][0]['diskonNilai'])->toBe('10');
    // Baris lanjutan (item kedua) tanpa diskon.
    expect($notaSatu['items'][1]['diskonTipe'])->toBe('NONE');
});

it('imports the template applying item & nota discounts, gross reconciliation stays balanced', function () {
    $admin = App\Models\User::factory()->create(['role' => 'admin', 'is_active' => true]);

    $path = app(BuildImportTemplate::class)->execute()->getFile()->getPathname();

    App\Actions\Import\ImportPurchaseExcel::dispatchSync($path, $admin->id);

    // Nota 1 (Murda Jaya): diskon item 65.000 + diskon nota 50.000 → grand 2.035.000.
    $notaSatu = App\Models\Purchase::query()
        ->whereRelation('supplier', 'nama', 'Murda Jaya')
        ->firstOrFail();

    expect($notaSatu->subtotal)->toBe('2150000.00');
    expect($notaSatu->total_diskon_item)->toBe('65000.00');
    expect($notaSatu->diskon_nota_amount)->toBe('50000.00');
    expect($notaSatu->grand_total)->toBe('2035000.00');
    expect(bccomp((string) $notaSatu->grand_total, (string) $notaSatu->subtotal, 2))->toBe(-1);

    // Rekonsiliasi: subtotal kotor per sheet == TOTAL Excel (delta 0) meski ada diskon.
    $recon = Illuminate\Support\Facades\Cache::get('import:reconciliation');
    expect($recon)->not->toBeNull();
    expect($recon['totalSystem'])->toBe('2275000.00');
    expect($recon['totalExcel'])->toBe('2275000.00');
    expect($recon['totalDelta'])->toBe('0.00');
    // Total setelah diskon lebih kecil dari TOTAL Excel.
    expect(bccomp($recon['netGrandTotal'], $recon['totalExcel'], 2))->toBe(-1);
    expect($recon['netGrandTotal'])->toBe('2160000.00');
});
