<?php

namespace App\Enums;

enum PriceType: string
{
    case Flat = 'flat';
    case PerUnit = 'per_unit';
    case PerCm = 'per_cm';

    public function label(): string
    {
        return match ($this) {
            self::Flat => 'Harga Tetap',
            self::PerUnit => 'Per Satuan',
            self::PerCm => 'Per Sentimeter',
        };
    }
}
