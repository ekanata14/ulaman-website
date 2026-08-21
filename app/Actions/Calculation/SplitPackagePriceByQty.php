<?php

namespace App\Actions\Calculation;

use App\Support\Money;

/**
 * §F-04 — Pada bundle HARGA_PAKET dengan harga satuan kosong, harga paket
 * dipecah proporsional terhadap qty; hasilnya harga satuan estimasi per item
 * (item ditandai harga_estimasi = true oleh pemanggil). Murni, tanpa DB.
 */
class SplitPackagePriceByQty
{
    public function __construct(
        private readonly AllocateDiscountProportionally $allocate,
    ) {}

    /**
     * @param  array<array-key, string>  $qty  qty per item, urutan dipertahankan
     * @return array<array-key, string> harga satuan estimasi per item, skala 2
     */
    public function execute(array $qty, string $hargaPaket): array
    {
        // Alokasikan harga paket proporsional terhadap qty (jumlah persis via largest remainder).
        $alokasiSubtotal = $this->allocate->execute($qty, $hargaPaket);

        $hargaSatuan = [];
        foreach ($qty as $k => $q) {
            $hargaSatuan[$k] = Money::div($alokasiSubtotal[$k], $q);
        }

        return $hargaSatuan;
    }
}
