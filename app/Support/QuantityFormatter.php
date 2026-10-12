<?php

namespace App\Support;

class QuantityFormatter
{
    public static function format(float|string|null $quantity, int $precision = 3): string
    {
        $formatted = number_format((float) $quantity, $precision, ',', '.');
        [$whole, $fraction] = array_pad(explode(',', $formatted, 2), 2, '');
        $fraction = rtrim($fraction, '0');

        return $whole.($fraction === '' ? '' : ','.$fraction);
    }
}
