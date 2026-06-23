<?php

namespace App\Workflows\Data;

use Spatie\LaravelData\Data;

class CorrelationRef extends Data
{
    public function __construct(
        public readonly int $incidentId,          // the *correlated* incident
        public readonly string $title,
        public readonly float $similarity,
    ) {}
}
