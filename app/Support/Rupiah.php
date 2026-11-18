<?php

namespace App\Support;

class Rupiah
{
    public static function format(float|int|string|null $nominal): string
    {
        return 'Rp'.number_format((float) $nominal, 0, ',', '.');
    }
}
