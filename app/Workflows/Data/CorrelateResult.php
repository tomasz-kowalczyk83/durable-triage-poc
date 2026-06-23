<?php

namespace App\Workflows\Data;

use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Data;

class CorrelateResult extends Data
{
    public function __construct(
        #[DataCollectionOf(CorrelationRef::class)]
        public array $correlationIds,
    ) {}
}
