<?php

namespace App\Workflows\Data;

use App\Enums\Severity;
use Spatie\LaravelData\Data;

class ClassifyResult extends Data
{
    public function __construct(
        public readonly Severity $severity,
        public readonly float $confidence
    ) {}
}
