<?php

namespace App\Workflows\Data;

use App\Enums\Severity;
use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Data;

class SynthesiseInput extends Data
{
    public function __construct(
        public readonly int $incidentId,
        public readonly Severity $severity,
        #[DataCollectionOf(CorrelationRef::class)] 
        public readonly array $correlations,
        #[DataCollectionOf(RunbookRef::class)]     
        public readonly array $runbooks,
    ) {}
}
