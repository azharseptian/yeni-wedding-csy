<?php

namespace App\Exceptions;

use App\Services\Scheduling\ConflictResult;

class ScheduleConflictException extends BusinessRuleException
{
    public function __construct(public readonly ConflictResult $result)
    {
        parent::__construct(
            'BENTROK: '.implode(' ', $result->messages()).' Isi Alasan Override untuk tetap menyimpan.',
            'bentrok_jadwal',
        );
    }
}
