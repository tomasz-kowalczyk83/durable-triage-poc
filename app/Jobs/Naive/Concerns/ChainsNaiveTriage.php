<?php

namespace App\Jobs\Naive\Concerns;

use App\Models\Incident;

trait ChainsNaiveTriage
{
    protected function incident(): Incident
    {
        return Incident::findOrFail($this->incidentId);
    }
}
