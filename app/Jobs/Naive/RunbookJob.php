<?php

namespace App\Jobs\Naive;

use App\Jobs\Naive\Concerns\ChainsNaiveTriage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class RunbookJob implements ShouldQueue
{
    use ChainsNaiveTriage;
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(public int $incidentId) {}

    public function handle(TriageStepRunner $runner): void
    {
        $incident = $this->incident();
        $result = $runner->runbook($incident);

        $incident->update([
            'runbook_refs' => array_map(fn ($ref) => $ref->toArray(), $result->runbooks),
            'status' => 'enriched',
        ]);

        SynthesiseJob::dispatch($this->incidentId);
    }
}
