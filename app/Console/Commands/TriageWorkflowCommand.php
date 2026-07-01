<?php

namespace App\Console\Commands;

use App\Models\Incident;
use App\Services\IncidentTriageLauncher;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('triage:durable {incidentId : The incident to triage} {--fail-at= : Force failure on the Nth LLM call (1=classify, 2=correlate, 3=runbook, 4=synthesise, 5=act)}')]
#[Description('Start the durable TriageWorkflow for an incident')]
class TriageWorkflowCommand extends Command
{
    public function handle(IncidentTriageLauncher $launcher): int
    {
        $incidentId = (int) $this->argument('incidentId');

        if (! Incident::query()->whereKey($incidentId)->exists()) {
            $this->error("Incident {$incidentId} not found. Run `php artisan triage:seed` first.");

            return self::FAILURE;
        }

        $workflow = $launcher->start(
            $incidentId,
            $this->option('fail-at') !== null ? (int) $this->option('fail-at') : null,
        );

        $this->info("Started durable triage workflow #{$workflow->id()} for incident {$incidentId}.");

        if ($this->option('fail-at') !== null) {
            $this->warn('Fail-at step '.$this->option('fail-at').' is armed — the worker will retry on failure.');
        }

        $this->line('Approve with: php artisan triage:approve '.$workflow->id());
        $this->line('Monitor at: /waterline');

        return self::SUCCESS;
    }
}
