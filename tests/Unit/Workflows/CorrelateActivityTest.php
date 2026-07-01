<?php

namespace Tests\Unit\Workflows;

use App\Contracts\LlmClient;
use App\Models\Incident;
use App\Support\FakeLlmClient;
use App\Workflows\CorrelateActivity;
use App\Workflows\Data\CorrelateInput;
use App\Workflows\Data\CorrelationRef;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CorrelateActivityTest extends TestCase
{
    use RefreshDatabase;

    public function test_correlates_incident_and_updates_database(): void
    {
        $this->app->bind(LlmClient::class, fn () => new class implements LlmClient
        {
            public static function complete(string $system, string $prompt): string
            {
                return json_encode([
                    'correlationIds' => [
                        [
                            'incidentId' => 42,
                            'title' => 'Prior checkout outage',
                            'similarity' => 0.91,
                        ],
                    ],
                ]);
            }
        });

        $incident = Incident::create([
            'raw_payload' => 'Checkout service degraded',
            'correlation_ids' => [],
            'runbook_refs' => [],
            'status' => 'open',
        ]);

        $activity = $this->makeActivity();
        $result = $activity->execute(new CorrelateInput($incident->id));

        $this->assertCount(1, $result->correlationIds);
        $this->assertInstanceOf(CorrelationRef::class, $result->correlationIds[0]);
        $this->assertSame(42, $result->correlationIds[0]->incidentId);
        $this->assertSame('Prior checkout outage', $result->correlationIds[0]->title);
        $this->assertSame(0.91, $result->correlationIds[0]->similarity);

        $incident->refresh();
        $this->assertSame([
            [
                'incidentId' => 42,
                'title' => 'Prior checkout outage',
                'similarity' => 0.91,
            ],
        ], $incident->correlation_ids);
    }

    public function test_uses_deterministic_fake_llm_client_response(): void
    {
        $this->app->bind(LlmClient::class, FakeLlmClient::class);

        $incident = Incident::create([
            'raw_payload' => 'Payment processor latency spike',
            'correlation_ids' => [],
            'runbook_refs' => [],
            'status' => 'open',
        ]);

        $activity = $this->makeActivity();
        $result = $activity->execute(new CorrelateInput($incident->id));

        $this->assertCount(1, $result->correlationIds);
        $this->assertSame(42, $result->correlationIds[0]->incidentId);
        $this->assertSame('same service', $result->correlationIds[0]->title);

        $incident->refresh();
        $this->assertCount(1, $incident->correlation_ids);
    }

    public function test_is_idempotent(): void
    {
        $this->app->bind(LlmClient::class, fn () => new class implements LlmClient
        {
            public static function complete(string $system, string $prompt): string
            {
                return json_encode(['correlationIds' => []]);
            }
        });

        $incident = Incident::create([
            'raw_payload' => 'Isolated alert with no matches',
            'correlation_ids' => [],
            'runbook_refs' => [],
            'status' => 'open',
        ]);

        $activity = $this->makeActivity();
        $input = new CorrelateInput($incident->id);

        $first = $activity->execute($input);
        $second = $activity->execute($input);

        $this->assertSame([], $first->correlationIds);
        $this->assertSame([], $second->correlationIds);

        $incident->refresh();
        $this->assertSame([], $incident->correlation_ids);
    }

    private function makeActivity(): CorrelateActivity
    {
        return new class extends CorrelateActivity
        {
            public function __construct() {}
        };
    }
}
