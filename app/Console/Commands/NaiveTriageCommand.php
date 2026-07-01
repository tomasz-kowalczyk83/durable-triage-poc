<?php

namespace App\Console\Commands;

use App\Models\Incident;
use App\Services\NaiveTriagePipeline;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('triage:naive {incidentId : The incident to triage}')]
#[Description('Start the naive queued-job triage pipeline for an incident')]
class NaiveTriageCommand extends Command
{
    public function handle(): int
    {
        $incidentId = (int) $this->argument('incidentId');

        if (! Incident::query()->whereKey($incidentId)->exists()) {
            $this->error("Incident {$incidentId} not found. Run `php artisan triage:seed` first.");

            return self::FAILURE;
        }

        app(NaiveTriagePipeline::class)->start($incidentId);

        $this->info("Dispatched naive triage pipeline for incident {$incidentId}.");

        return self::SUCCESS;
    }
}
