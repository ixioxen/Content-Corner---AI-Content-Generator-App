<?php

namespace App\Services;

use InvalidArgumentException;

class StripeCurrency
{
    private const ZERO_DECIMAL = [
        'BIF', 'CLP', 'DJF', 'GNF', 'JPY', 'KMF', 'KRW',
        'MGA', 'PYG', 'RWF', 'UGX', 'VND', 'VUV', 'XAF',
        'XOF', 'XPF',
    ];

    private const THREE_DECIMAL = ['BHD', 'JOD', 'KWD', 'OMR', 'TND'];

    public static function toMinorUnits(string $amount, string $currency): int
    {
        if (! preg_match('/^\d+(?:\.\d{1,2})?$/', $amount)) {
            throw new InvalidArgumentException('Price must be a positive decimal amount.');
        }

        $precision = in_array($currency, self::ZERO_DECIMAL, true)
            ? 0
            : (in_array($currency, self::THREE_DECIMAL, true) ? 3 : 2);
        [$whole, $fraction] = array_pad(explode('.', $amount, 2), 2, '');

        if ($precision === 0 && trim($fraction, '0') !== '') {
            throw new InvalidArgumentException('This currency does not support fractional prices.');
        }

        $minorUnits = $whole.str_pad(substr($fraction, 0, $precision), $precision, '0');
        $normalized = ltrim($minorUnits, '0');

        return (int) ($normalized === '' ? '0' : $normalized);
    }
}
