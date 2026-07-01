<?php

namespace Tests\Feature\Naive;

use App\Contracts\LlmClient;
use App\Jobs\Naive\ClassifyJob;
use App\Jobs\Naive\CorrelateJob;
use App\Jobs\Naive\EscalateCheckJob;
use App\Jobs\Naive\ReCheckApprovalJob;
use App\Jobs\Naive\RunbookJob;
use App\Jobs\Naive\SynthesiseJob;
use App\Jobs\Naive\TriageStepRunner;
use App\Models\Incident;
use App\Services\NaiveTriagePipeline;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class NaiveTriagePipelineTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['triage.llm_client' => CountingLlmClient::class]);
        CountingLlmClient::reset();
        CountingLlmClient::$classifySeverity = 'SEV3';
    }

    public function test_start_dispatches_classify_job(): void
    {
        Bus::fake([ClassifyJob::class]);

        $incident = $this->createIncident();

        app(NaiveTriagePipeline::class)->start($incident->id);

        $this->assertDatabaseHas('incidents', [
            'id' => $incident->id,
            'status' => 'triaging',
        ]);

        Bus::assertDispatched(ClassifyJob::class, fn (ClassifyJob $job) => $job->incidentId === $incident->id);
    }

    public function test_chain_progresses_sequentially_to_acted_without_approval(): void
    {
        $incident = $this->createIncident();

        app(NaiveTriagePipeline::class)->start($incident->id);

        $incident->refresh();

        $this->assertSame('acted', $incident->status);
        $this->assertSame('SEV3', $incident->severity);
        $this->assertNotNull($incident->suggestion);
        $this->assertSame(
            ['classify', 'correlate', 'runbook', 'synthesise', 'act'],
            CountingLlmClient::$systems,
        );
    }

    public function test_enrichment_steps_run_in_sequence_not_parallel(): void
    {
        $incident = $this->createIncident();

        app(NaiveTriagePipeline::class)->start($incident->id);

        $classifyIndex = array_search('classify', CountingLlmClient::$systems, true);
        $correlateIndex = array_search('correlate', CountingLlmClient::$systems, true);
        $runbookIndex = array_search('runbook', CountingLlmClient::$systems, true);

        $this->assertNotFalse($classifyIndex);
        $this->assertNotFalse($correlateIndex);
        $this->assertNotFalse($runbookIndex);
        $this->assertLessThan($correlateIndex, $classifyIndex);
        $this->assertLessThan($runbookIndex, $correlateIndex);
    }

    public function test_synthesise_waits_for_approval_on_high_severity(): void
    {
        Queue::fake([
            ReCheckApprovalJob::class,
            EscalateCheckJob::class,
        ]);

        $incident = $this->createIncident([
            'severity' => 'SEV2',
            'status' => 'enriched',
        ]);

        SynthesiseJob::dispatchSync($incident->id);

        $incident->refresh();

        $this->assertSame('awaiting_approval', $incident->status);
        $this->assertNotNull($incident->suggestion);

        Queue::assertPushed(ReCheckApprovalJob::class);
        Queue::assertPushed(EscalateCheckJob::class);
        $this->assertSame(['synthesise'], CountingLlmClient::$systems);
    }

    public function test_approval_recheck_proceeds_to_act_when_approved(): void
    {
        $incident = $this->createIncident([
            'severity' => 'SEV2',
            'suggestion' => 'Restart the cache layer.',
            'status' => 'awaiting_approval',
        ]);

        $incident->update(['status' => 'approved']);

        ReCheckApprovalJob::dispatchSync($incident->id);

        $incident->refresh();

        $this->assertSame('acted', $incident->status);
        $this->assertContains('act', CountingLlmClient::$systems);
    }

    public function test_escalate_check_job_escalates_on_timeout(): void
    {
        $incident = $this->createIncident([
            'severity' => 'SEV2',
            'suggestion' => 'Restart the cache layer.',
            'status' => 'awaiting_approval',
        ]);

        EscalateCheckJob::dispatchSync($incident->id);

        $incident->refresh();

        $this->assertSame('escalated', $incident->status);
        $this->assertContains('escalate', CountingLlmClient::$systems);
    }

    public function test_recheck_rejects_to_escalate(): void
    {
        $incident = $this->createIncident([
            'severity' => 'SEV2',
            'suggestion' => 'Restart the cache layer.',
            'status' => 'rejected',
        ]);

        ReCheckApprovalJob::dispatchSync($incident->id);

        $incident->refresh();

        $this->assertSame('escalated', $incident->status);
    }

    public function test_retry_duplicates_llm_calls_without_idempotency_guard(): void
    {
        Bus::fake([CorrelateJob::class]);

        $incident = $this->createIncident();

        $job = new ClassifyJob($incident->id);
        $runner = app(TriageStepRunner::class);

        $job->handle($runner);
        $firstPassCalls = CountingLlmClient::$callCount;

        $job->handle($runner);

        $this->assertSame(1, $firstPassCalls);
        $this->assertSame(2, CountingLlmClient::$callCount);
        $this->assertSame(2, CountingLlmClient::countFor('classify'));
    }

    public function test_later_step_retry_re_runs_entire_upstream_chain_when_restarted(): void
    {
        $incident = $this->createIncident();

        ClassifyJob::dispatchSync($incident->id);
        CorrelateJob::dispatchSync($incident->id);
        RunbookJob::dispatchSync($incident->id);

        CountingLlmClient::reset();
        CountingLlmClient::$classifySeverity = 'SEV3';

        SynthesiseJob::dispatchSync($incident->id);
        SynthesiseJob::dispatchSync($incident->id);

        $this->assertSame(2, CountingLlmClient::countFor('synthesise'));
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function createIncident(array $overrides = []): Incident
    {
        return Incident::create(array_merge([
            'raw_payload' => 'payment-api latency spike',
            'correlation_ids' => [],
            'runbook_refs' => [],
            'status' => 'pending',
        ], $overrides));
    }
}

class CountingLlmClient implements LlmClient
{
    public static int $callCount = 0;

    /** @var list<string> */
    public static array $systems = [];

    public static string $classifySeverity = 'SEV3';

    public static function reset(): void
    {
        self::$callCount = 0;
        self::$systems = [];
        self::$classifySeverity = 'SEV3';
    }

    public static function countFor(string $system): int
    {
        return count(array_filter(self::$systems, fn (string $value) => $value === $system));
    }

    public static function complete(string $system, string $prompt): string
    {
        self::$callCount++;
        self::$systems[] = $system;

        return match ($system) {
            'classify' => json_encode([
                'severity' => self::$classifySeverity,
                'confidence' => 0.91,
            ]),
            'correlate' => json_encode(['correlationIds' => []]),
            'runbook' => json_encode(['runbooks' => []]),
            'synthesise' => json_encode(['suggestion' => 'Scale read replicas and flush CDN cache.']),
            'act' => json_encode(['actionRef' => 'pagerduty-INC-42']),
            'escalate' => json_encode(['escalated' => true]),
            default => 'fake response',
        };
    }
}
