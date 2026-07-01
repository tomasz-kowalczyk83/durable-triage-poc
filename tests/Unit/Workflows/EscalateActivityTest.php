<?php

namespace Tests\Unit\Workflows;

use App\Models\Incident;
use App\Workflows\EscalateActivity;
use App\Workflows\MyWorkflow;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Workflow\Models\StoredWorkflow;
use Workflow\WorkflowStub;

class EscalateActivityTest extends TestCase
{
    use RefreshDatabase;

    public function test_escalates_incident(): void
    {
        $incident = Incident::query()->create([
            'raw_payload' => 'Payment API returning 503',
            'correlation_ids' => [],
            'runbook_refs' => [],
            'status' => 'awaiting_approval',
        ]);

        $result = $this->runActivity(EscalateActivity::class, $incident->id);

        $this->assertSame(['escalated' => true], $result);

        $incident->refresh();
        $this->assertSame('escalated', $incident->status);
    }

    private function runActivity(string $activityClass, mixed ...$arguments): mixed
    {
        $workflow = WorkflowStub::make(MyWorkflow::class);
        $storedWorkflow = StoredWorkflow::findOrFail($workflow->id());
        $activity = new $activityClass(0, now()->toDateTimeString(), $storedWorkflow, ...$arguments);

        return $activity->handle();
    }
}
