<?php

namespace App\Workflows\Data;

use Spatie\LaravelData\Data;

class ActInput extends Data
{
    public function __construct(
        public readonly int $incidentId,
        public readonly string $suggestion,
    ) {}
}
