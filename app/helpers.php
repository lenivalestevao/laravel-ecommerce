<?php

use App\Support\Money;

if (! function_exists('money')) {
    /** Formats an amount in cents with the store currency, e.g. $19.90. */
    function money(int|null $cents): string
    {
        return Money::format($cents ?? 0);
    }
}
