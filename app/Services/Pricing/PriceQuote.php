<?php

namespace App\Services\Pricing;

final class PriceQuote
{
    /**
     * @param  list<array<string, mixed>>  $lines  Baris siap disimpan ke booking_items_snapshot
     */
    public function __construct(public readonly array $lines) {}

    public function totalKlien(): float
    {
        return round(array_sum(array_column($this->lines, 'subtotal_klien')), 2);
    }

    public function totalVendor(): float
    {
        return round(array_sum(array_column($this->lines, 'subtotal_vendor')), 2);
    }

    public function totalMargin(): float
    {
        return round(array_sum(array_column($this->lines, 'margin')), 2);
    }
}
