<?php

namespace App\Services\Scheduling;

final class Conflict
{
    public const WAKTU = 'waktu';

    public const KAPASITAS = 'kapasitas';

    public const KETERSEDIAAN = 'ketersediaan';

    /**
     * @param  list<string>  $suggestions
     */
    public function __construct(
        public readonly string $type,
        public readonly string $message,
        public readonly array $suggestions = [],
        public readonly ?int $scheduleId = null,
    ) {}

    public function toArray(): array
    {
        return [
            'type' => $this->type,
            'message' => $this->message,
            'suggestions' => $this->suggestions,
            'schedule_id' => $this->scheduleId,
        ];
    }
}
