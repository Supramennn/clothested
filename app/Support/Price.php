<?php

namespace App\Support;

/**
 * Renders IDR amounts for the storefront; input columns are decimal(12,2).
 */
class Price
{
    /**
     * Format an amount as plain Indonesian IDR text, e.g. "Rp 129.000".
     */
    public static function format(int|float|string $amount): string
    {
        return 'Rp '.number_format((float) $amount, 0, ',', '.');
    }
}
