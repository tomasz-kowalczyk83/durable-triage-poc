<?php

namespace App\Workflows\Data;

use Spatie\LaravelData\Data;

class ActResult extends Data
{
    public function __construct(public readonly string $actionRef) {}
}
