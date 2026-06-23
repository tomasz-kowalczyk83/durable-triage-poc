<?php

namespace App\Workflows;

use App\Workflows\ClassifyActivity;
use App\Workflows\CorrelateActivity;
use App\Workflows\RunbookActivity;
use Workflow\Workflow;
use function Workflow\activity;


class TriageWorkflow extends Workflow
{
    public function execute()
    {
        // fan-out enrichment
        [$class, $corr, $runbook] = yield all([
            activity(ClassifyActivity::class, $incidentId),
            activity(CorrelateActivity::class, $incidentId),
            activity(RunbookActivity::class, $incidentId),
        ]);

        $suggestion = yield activity(SynthesiseActivity::class, /* … */);

        // human gate: signal vs timer race  (confirm exact signal/timer API in docs)
        // approved = yield Workflow::awaitWithTimeout(<duration>, fn () => $this->approved)
        // on timeout -> escalate; on approve -> act

        if ($approved) {
            yield activity(ActActivity::class, $incidentId, $suggestion);
        } else {
            yield activity(EscalateActivity::class, $incidentId);
        }
    }
}
