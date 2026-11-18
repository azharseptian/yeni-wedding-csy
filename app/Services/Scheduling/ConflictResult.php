<?php

namespace App\Services\Scheduling;

final class ConflictResult
{
    /** @param  list<Conflict>  $conflicts */
    public function __construct(public readonly array $conflicts = []) {}

    public function hasConflict(): bool
    {
        return $this->conflicts !== [];
    }

    public function ofType(string $type): array
    {
        return array_values(array_filter($this->conflicts, fn (Conflict $c) => $c->type === $type));
    }

    /** @return list<string> */
    public function messages(): array
    {
        return array_map(fn (Conflict $c) => $c->message, $this->conflicts);
    }

    /** @return list<string> */
    public function suggestions(): array
    {
        return array_values(array_unique(array_merge([], ...array_map(fn (Conflict $c) => $c->suggestions, $this->conflicts))));
    }

    public function toArray(): array
    {
        return array_map(fn (Conflict $c) => $c->toArray(), $this->conflicts);
    }
}
