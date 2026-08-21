<?php

namespace App\Actions\Calculation;

use App\Support\Money;

/**
 * §10.1 — Alokasi diskon proporsional dengan koreksi *largest remainder*.
 * Dijamin: Σ hasil == $diskon (persis), tanpa selisih 1 rupiah.
 *
 * Langkah:
 *   1. floor(diskon × bobot_i / basis) per item.
 *   2. sisa = diskon − Σ floor.
 *   3. bagikan sisa 1 rupiah per item, mulai dari sisa pecahan terbesar;
 *      seri → bobot terbesar; seri lagi → indeks terkecil.
 *   4. basis == 0 → kembalikan nol (penjaga pembagian nol).
 */
class AllocateDiscountProportionally
{
    /**
     * @param  array<array-key, string>  $bobot  bobot per item (mis. total item), urutan dipertahankan
     * @return array<array-key, string> alokasi per item pada kunci yang sama, skala 2
     */
    public function execute(array $bobot, string $diskon): array
    {
        $hasil = [];
        foreach ($bobot as $k => $_) {
            $hasil[$k] = Money::zero();
        }

        if ($bobot === [] || Money::compare($diskon, '0') === 0) {
            return $hasil;
        }

        $basis = Money::zero();
        foreach ($bobot as $b) {
            $basis = Money::add($basis, $b);
        }

        if (Money::compare($basis, '0') === 0) {
            return $hasil;
        }

        $floors = [];
        $fracs = [];
        $sumFloor = '0';
        $index = 0;
        foreach ($bobot as $k => $b) {
            // exact = diskon × b / basis (presisi tinggi untuk tie-break)
            $exact = bcdiv(bcmul($diskon, $b, 8), $basis, 8);
            $floor = $this->floor0($exact);
            $floors[$k] = $floor;
            $fracs[$k] = ['frac' => bcsub($exact, $floor, 8), 'bobot' => $b, 'idx' => $index++];
            $sumFloor = bcadd($sumFloor, $floor, 0);
            $hasil[$k] = Money::add($floor, '0');
        }

        // Sisa dalam satuan rupiah bulat.
        $sisa = (int) bcsub($this->floor0($diskon), $sumFloor, 0);
        if ($sisa <= 0) {
            return $hasil;
        }

        $urutan = array_keys($fracs);
        usort($urutan, function ($a, $b) use ($fracs): int {
            $cmp = bccomp($fracs[$b]['frac'], $fracs[$a]['frac'], 8);
            if ($cmp !== 0) {
                return $cmp;
            }
            $cmpBobot = Money::compare($fracs[$b]['bobot'], $fracs[$a]['bobot']);
            if ($cmpBobot !== 0) {
                return $cmpBobot;
            }

            return $fracs[$a]['idx'] <=> $fracs[$b]['idx'];
        });

        foreach (array_slice($urutan, 0, $sisa) as $k) {
            $hasil[$k] = Money::add($hasil[$k], '1');
        }

        return $hasil;
    }

    /** Pembulatan ke bawah ke rupiah bulat (nilai non-negatif), hasil skala 2. */
    private function floor0(string $value): string
    {
        return Money::add(bcadd($value, '0', 0), '0');
    }
}
