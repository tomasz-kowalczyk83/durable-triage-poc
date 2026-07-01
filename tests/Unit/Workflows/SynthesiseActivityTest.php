<?php

namespace Tests\Unit\Workflows;

use App\Enums\Severity;
use App\Models\Incident;
use App\Support\FakeLlmClient;
use App\Workflows\Data\CorrelationRef;
use App\Workflows\Data\RunbookRef;
use App\Workflows\Data\SynthesiseInput;
use App\Workflows\Data\SynthesiseResult;
use App\Workflows\MyWorkflow;
use App\Workflows\SynthesiseActivity;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Workflow\Models\StoredWorkflow;
use Workflow\WorkflowStub;

class SynthesiseActivityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        FakeLlmClient::resetCounts();
    }

    public function test_synthesises_suggestion_and_persists_to_incident(): void
    {
        $incident = $this->createIncident();

        $input = new SynthesiseInput(
            incidentId: $incident->id,
            severity: Severity::Sev2,
            correlations: [
                new CorrelationRef(incidentId: 42, title: 'Related outage', similarity: 0.88),
            ],
            runbooks: [
                new RunbookRef(slug: 'rb-001', title: 'Restart pod'),
            ],
        );

        $result = $this->runActivity(SynthesiseActivity::class, $input);

        $this->assertInstanceOf(SynthesiseResult::class, $result);
        $this->assertSame(
            'Scale up replicas and restart the affected pod.',
            $result->suggestion,
        );

        $incident->refresh();
        $this->assertSame($result->suggestion, $incident->suggestion);
        $this->assertSame(1, FakeLlmClient::getCallCount('synthesise'));
    }

    private function createIncident(): Incident
    {
        return Incident::query()->create([
            'raw_payload' => 'Payment API returning 503',
            'correlation_ids' => [],
            'runbook_refs' => [],
            'status' => 'triaging',
        ]);
    }

    private function runActivity(string $activityClass, mixed ...$arguments): mixed
    {
        $workflow = WorkflowStub::make(MyWorkflow::class);
        $storedWorkflow = StoredWorkflow::findOrFail($workflow->id());
        $activity = new $activityClass(0, now()->toDateTimeString(), $storedWorkflow, ...$arguments);

        return $activity->handle();
    }
}
