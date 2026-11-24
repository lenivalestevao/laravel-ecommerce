<?php

namespace App\Support;

/**
 * All prices are stored as integer cents. This class converts them for display and for forms.
 */
final class Money
{
    public static function format(int $cents): string
    {
        return config('shop.currency').number_format($cents / 100, 2, '.', ',');
    }

    /** "19.90", "19,90" or 19.9 => 1990 */
    public static function toCents(string|int|float|null $value): int
    {
        if ($value === null || $value === '') {
            return 0;
        }

        $normalized = is_string($value) ? str_replace(',', '.', trim($value)) : $value;

        return (int) round(((float) $normalized) * 100);
    }

    /** 1990 => "19.90" (value for an <input>) */
    public static function toInput(?int $cents): string
    {
        return number_format(($cents ?? 0) / 100, 2, '.', '');
    }
}
