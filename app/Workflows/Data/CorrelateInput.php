<?php

namespace App\Workflows\Data;

use Spatie\LaravelData\Data;

class CorrelateInput extends Data
{
    public function __construct(
        public int $incidentId
    ) {}
}
