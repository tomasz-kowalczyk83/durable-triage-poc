<?php

namespace Tests\Feature\Workflows;

use App\Enums\Severity;
use App\Models\Incident;
use App\Support\FakeLlmClient;
use App\Workflows\ActActivity;
use App\Workflows\ClassifyActivity;
use App\Workflows\CorrelateActivity;
use App\Workflows\Data\ActInput;
use App\Workflows\Data\ActResult;
use App\Workflows\Data\ClassifyResult;
use App\Workflows\Data\CorrelateResult;
use App\Workflows\Data\CorrelationRef;
use App\Workflows\Data\RunbookRef;
use App\Workflows\Data\RunbookResult;
use App\Workflows\Data\SynthesiseInput;
use App\Workflows\Data\SynthesiseResult;
use App\Workflows\EscalateActivity;
use App\Workflows\RunbookActivity;
use App\Workflows\SynthesiseActivity;
use App\Workflows\TriageWorkflow;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Workflow\States\WorkflowCompletedStatus;
use Workflow\WorkflowStub;

class TriageWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        WorkflowStub::fake();
        FakeLlmClient::resetCounts();
    }

    public function test_approve_path_acts_on_incident(): void
    {
        $incident = $this->createIncident();

        $this->mockEnrichmentActivities(Severity::Sev2);

        $workflow = WorkflowStub::make(TriageWorkflow::class);
        $workflow->start($incident->id, approvalTimeoutSeconds: 60);

        $workflow->approve(true);

        while ($workflow->running());

        $this->assertSame(WorkflowCompletedStatus::class, $workflow->status());

        $output = $workflow->output();
        $this->assertInstanceOf(ActResult::class, $output);
        $this->assertSame('act-001', $output->actionRef);

        $incident->refresh();
        $this->assertSame('acted', $incident->status);
        $this->assertSame(
            'Scale up replicas and restart the affected pod.',
            $incident->suggestion,
        );
        $this->assertSame(0, FakeLlmClient::getCallCount('synthesise'));
        $this->assertSame(0, FakeLlmClient::getCallCount('act'));
    }

    public function test_timeout_path_escalates_incident(): void
    {
        $incident = $this->createIncident();

        $this->mockEnrichmentActivities(Severity::Sev1);

        $workflow = WorkflowStub::make(TriageWorkflow::class);
        $workflow->start($incident->id, approvalTimeoutSeconds: 1);

        $this->travel(2)->seconds();
        $workflow->resume();

        while ($workflow->running());

        $this->assertSame(WorkflowCompletedStatus::class, $workflow->status());
        $this->assertSame(['escalated' => true], $workflow->output());

        $incident->refresh();
        $this->assertSame('escalated', $incident->status);
        $this->assertSame(0, FakeLlmClient::getCallCount('synthesise'));
        $this->assertSame(0, FakeLlmClient::getCallCount('act'));
    }

    public function test_low_severity_auto_approves_without_signal(): void
    {
        $incident = $this->createIncident();

        $this->mockEnrichmentActivities(Severity::Sev4);

        $workflow = WorkflowStub::make(TriageWorkflow::class);
        $workflow->start($incident->id, approvalTimeoutSeconds: 1);

        while ($workflow->running());

        $this->assertSame(WorkflowCompletedStatus::class, $workflow->status());
        $this->assertInstanceOf(ActResult::class, $workflow->output());

        $incident->refresh();
        $this->assertSame('acted', $incident->status);
    }

    private function createIncident(): Incident
    {
        return Incident::query()->create([
            'raw_payload' => 'Payment API returning 503',
            'correlation_ids' => [],
            'runbook_refs' => [],
            'status' => 'pending',
        ]);
    }

    private function mockEnrichmentActivities(Severity $severity): void
    {
        WorkflowStub::mock(ClassifyActivity::class, new ClassifyResult($severity, 0.92));
        WorkflowStub::mock(CorrelateActivity::class, new CorrelateResult([
            new CorrelationRef(incidentId: 42, title: 'Related outage', similarity: 0.88),
        ]));
        WorkflowStub::mock(RunbookActivity::class, new RunbookResult([
            new RunbookRef(slug: 'rb-001', title: 'Restart pod'),
        ]));
        WorkflowStub::mock(SynthesiseActivity::class, function ($context, SynthesiseInput $input) {
            $suggestion = 'Scale up replicas and restart the affected pod.';
            Incident::query()->whereKey($input->incidentId)->update(['suggestion' => $suggestion]);

            return new SynthesiseResult($suggestion);
        });
        WorkflowStub::mock(ActActivity::class, function ($context, ActInput $input) {
            Incident::query()->whereKey($input->incidentId)->update(['status' => 'acted']);

            return new ActResult('act-001');
        });
        WorkflowStub::mock(EscalateActivity::class, function ($context, int $incidentId) {
            Incident::query()->whereKey($incidentId)->update(['status' => 'escalated']);

            return ['escalated' => true];
        });
    }
}
