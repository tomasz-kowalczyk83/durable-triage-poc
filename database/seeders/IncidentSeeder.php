<?php

namespace Database\Seeders;

use App\Models\Incident;
use Illuminate\Database\Seeder;

class IncidentSeeder extends Seeder
{
    public function run(): void
    {
        Incident::query()->create([
            'raw_payload' => 'Payment API returning 503 — elevated error rate on checkout',
            'severity' => null,
            'correlation_ids' => [],
            'runbook_refs' => [],
            'suggestion' => null,
            'status' => 'pending',
        ]);
    }
}
