<?php

use App\Support\Rupiah;
use Carbon\CarbonInterface;

if (! function_exists('rupiah')) {
    function rupiah(float|int|string|null $nominal): string
    {
        return Rupiah::format($nominal);
    }
}

if (! function_exists('tanggal')) {
    function tanggal(?CarbonInterface $date, string $format = 'j F Y'): string
    {
        return $date ? $date->copy()->locale('id')->translatedFormat($format) : '-';
    }
}
