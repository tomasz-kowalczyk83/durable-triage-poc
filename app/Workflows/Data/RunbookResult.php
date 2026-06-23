<?php

namespace App\Workflows\Data;

use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Data;

class RunbookResult extends Data
{
    public function __construct(
        #[DataCollectionOf(RunbookRef::class)]
        public readonly array $runbooks,
    ) {}
}
