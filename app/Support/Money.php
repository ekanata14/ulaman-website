<?php

namespace App\Support;

/**
 * Helper aritmetika & format uang berbasis bcmath.
 *
 * Semua nilai uang mengalir sebagai string DECIMAL(18,2). DILARANG float.
 * Pembulatan default half-up (menjauhi nol) sesuai §7 PRD.
 */
final class Money
{
    /** Skala penyimpanan uang: DECIMAL(18,2). */
    public const SCALE = 2;

    public static function add(string $a, string $b, int $scale = self::SCALE): string
    {
        return bcadd($a, $b, $scale);
    }

    public static function sub(string $a, string $b, int $scale = self::SCALE): string
    {
        return bcsub($a, $b, $scale);
    }

    public static function mul(string $a, string $b, int $scale = self::SCALE): string
    {
        return bcmul($a, $b, $scale);
    }

    public static function div(string $a, string $b, int $scale = self::SCALE): string
    {
        if (bccomp($b, '0', self::SCALE) === 0) {
            return self::zero($scale);
        }

        return bcdiv($a, $b, $scale);
    }

    /** -1 jika a<b, 0 jika sama, 1 jika a>b (pada skala uang). */
    public static function compare(string $a, string $b, int $scale = self::SCALE): int
    {
        return bccomp($a, $b, $scale);
    }

    /**
     * Pembulatan half-up (menjauhi nol) ke jumlah desimal tertentu.
     * Diskon dibulatkan ke rupiah bulat (scale 0) sesuai §7.
     */
    public static function roundHalfUp(string $value, int $scale = 0): string
    {
        $factor = '0.'.str_repeat('0', $scale).'5';

        if (str_starts_with(trim($value), '-')) {
            return bcsub($value, $factor, $scale);
        }

        return bcadd($value, $factor, $scale);
    }

    /** Representasi nol pada skala tertentu, mis. "0.00". */
    public static function zero(int $scale = self::SCALE): string
    {
        return bcadd('0', '0', $scale);
    }

    /** Format tampilan Rupiah, mis. "Rp 1.159.000". Hanya untuk presentasi. */
    public static function format(string $value): string
    {
        return 'Rp '.number_format((float) $value, 0, ',', '.');
    }
}
