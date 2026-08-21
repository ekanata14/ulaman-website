<?php

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
