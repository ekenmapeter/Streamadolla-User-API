<?php

namespace App\Support;

final class Money
{
    public const USD = 'USD';
    public const NGN = 'NGN';

    public static function symbol(string $currency): string
    {
        return strtoupper($currency) === self::USD ? '$' : '₦';
    }

    /**
     * Format a monetary amount for display. USD keeps three decimal places so
     * the vendor per-stream rate ($0.001) is always visible.
     */
    public static function format(int|float|string|null $amount, string $currency = self::NGN, int $decimals = 0): string
    {
        $places = strtoupper($currency) === self::USD ? 3 : $decimals;

        return self::symbol($currency) . number_format((float) ($amount ?? 0), $places);
    }

    /**
     * Return an int when the amount is whole, otherwise a float. This keeps
     * the listener API contract (whole Naira) while allowing vendor cents.
     */
    public static function number(int|float|string|null $amount): int|float
    {
        $value = round((float) ($amount ?? 0), 6);

        return abs($value - round($value)) < 0.0000005 ? (int) round($value) : $value;
    }

    public static function round(int|float|string|null $amount, int $precision = 6): float
    {
        return round((float) ($amount ?? 0), $precision);
    }
}
