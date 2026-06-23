<?php

namespace App\Console\Commands;

use App\Workflows\MyWorkflow;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Workflow\WorkflowStub;

#[Signature('app:simple-workflow')]
#[Description('Command description')]
class SimpleWorkflow extends Command
{
    /**
     * Execute the console command.
     */
    public function handle()
    {
        $workflow = WorkflowStub::make(MyWorkflow::class);
        $workflow->start();
        // while ($workflow->refresh()->running()) {
        //     usleep(100_000);
        // }
        $this->info($workflow->output());
    }
}
