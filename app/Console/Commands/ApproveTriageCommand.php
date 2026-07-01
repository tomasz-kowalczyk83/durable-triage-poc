<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Workflow\WorkflowStub;

#[Signature('triage:approve {workflowId : The running workflow ID to approve}')]
#[Description('Send an approve signal to a triage workflow awaiting human approval')]
class ApproveTriageCommand extends Command
{
    public function handle(): int
    {
        $workflowId = (int) $this->argument('workflowId');

        $workflow = WorkflowStub::load($workflowId);
        $workflow->approve();

        $this->info("Sent approve signal to workflow #{$workflowId}.");

        return self::SUCCESS;
    }
}
