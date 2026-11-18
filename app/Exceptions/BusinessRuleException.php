<?php

namespace App\Exceptions;

use RuntimeException;

/** Pelanggaran aturan bisnis; pesan selalu berbahasa Indonesia dan aman ditampilkan ke pengguna. */
class BusinessRuleException extends RuntimeException
{
    public function __construct(string $message, public readonly string $rule = '')
    {
        parent::__construct($message);
    }
}
