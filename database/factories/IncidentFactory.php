<?php

namespace Database\Factories;

use App\Models\Incident;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Incident>
 */
class IncidentFactory extends Factory
{
    protected $model = Incident::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'raw_payload' => 'Payment API returning 503 — elevated error rate on checkout',
            'severity' => null,
            'correlation_ids' => [],
            'runbook_refs' => [],
            'suggestion' => null,
            'status' => 'pending',
        ];
    }
}
