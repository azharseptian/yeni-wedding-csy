<?php

namespace App\Enums;

enum PaymentType: string
{
    case Dp = 'dp';
    case Cicilan = 'cicilan';
    case Pelunasan = 'pelunasan';

    public function label(): string
    {
        return match ($this) {
            self::Dp => 'DP (Uang Muka)',
            self::Cicilan => 'Cicilan',
            self::Pelunasan => 'Pelunasan',
        };
    }
}
