<?php

namespace App\Workflows;


use Workflow\Workflow;
use function Workflow\activity;

class MyWorkflow extends Workflow
{
    public function execute()
    {
        $result = yield activity(MyActivity::class);

        return $result;
    }
}
