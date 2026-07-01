<?php

namespace Tests\Unit\Workflows;

use App\Models\Incident;
use App\Support\FakeLlmClient;
use App\Workflows\ActActivity;
use App\Workflows\Data\ActInput;
use App\Workflows\Data\ActResult;
use App\Workflows\MyWorkflow;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Workflow\Models\StoredWorkflow;
use Workflow\WorkflowStub;

class ActActivityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        FakeLlmClient::resetCounts();
    }

    public function test_acts_on_incident_and_returns_action_ref(): void
    {
        $incident = Incident::query()->create([
            'raw_payload' => 'Payment API returning 503',
            'correlation_ids' => [],
            'runbook_refs' => [],
            'suggestion' => 'Scale up replicas and restart the affected pod.',
            'status' => 'awaiting_approval',
        ]);

        $input = new ActInput(
            incidentId: $incident->id,
            suggestion: $incident->suggestion,
        );

        $result = $this->runActivity(ActActivity::class, $input);

        $this->assertInstanceOf(ActResult::class, $result);
        $this->assertSame('act-001', $result->actionRef);

        $incident->refresh();
        $this->assertSame('acted', $incident->status);
        $this->assertSame(1, FakeLlmClient::getCallCount('act'));
    }

    private function runActivity(string $activityClass, mixed ...$arguments): mixed
    {
        $workflow = WorkflowStub::make(MyWorkflow::class);
        $storedWorkflow = StoredWorkflow::findOrFail($workflow->id());
        $activity = new $activityClass(0, now()->toDateTimeString(), $storedWorkflow, ...$arguments);

        return $activity->handle();
    }
}
