<?php

namespace App\Workflows\Data;

use Spatie\LaravelData\Data;

class RunbookRef extends Data
{
    public function __construct(
        public readonly string $slug,
        public readonly string $title,
        public readonly ?string $url = null,
    ) {}
}
