<?php

namespace App\Services;

use App\Support\FakeLlmClient;
use App\Workflows\TriageWorkflow;
use Workflow\WorkflowStub;

class IncidentTriageLauncher
{
    public function start(int $incidentId, ?int $failAt = null): WorkflowStub
    {
        FakeLlmClient::resetCounts();

        if ($failAt !== null) {
            FakeLlmClient::setFailAt($failAt);
        }

        $workflow = WorkflowStub::make(TriageWorkflow::class);
        $workflow->start($incidentId);

        return $workflow;
    }
}
