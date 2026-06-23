<?php

namespace App\Workflows\Data;

use Spatie\LaravelData\Data;

class SynthesiseResult extends Data
{
    public function __construct(public readonly string $suggestion) {}
}
