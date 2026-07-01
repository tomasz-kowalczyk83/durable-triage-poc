<?php

namespace Tests\Unit\Workflows;

use App\Contracts\LlmClient;
use App\Enums\Severity;
use App\Models\Incident;
use App\Support\FakeLlmClient;
use App\Workflows\ClassifyActivity;
use App\Workflows\Data\ClassifyInput;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClassifyActivityTest extends TestCase
{
    use RefreshDatabase;

    public function test_classifies_incident_and_updates_database(): void
    {
        $this->app->bind(LlmClient::class, fn () => new class implements LlmClient
        {
            public static function complete(string $system, string $prompt): string
            {
                return json_encode(['severity' => 'SEV1', 'confidence' => 0.95]);
            }
        });

        $incident = Incident::create([
            'raw_payload' => 'API gateway returning 503',
            'correlation_ids' => [],
            'runbook_refs' => [],
            'status' => 'open',
        ]);

        $activity = $this->makeActivity();
        $result = $activity->execute(new ClassifyInput($incident->id));

        $this->assertEquals(Severity::Sev1, $result->severity);
        $this->assertSame(0.95, $result->confidence);

        $incident->refresh();
        $this->assertSame('SEV1', $incident->severity);
    }

    public function test_uses_deterministic_fake_llm_client_response(): void
    {
        $this->app->bind(LlmClient::class, FakeLlmClient::class);

        $incident = Incident::create([
            'raw_payload' => 'Database connection pool exhausted',
            'correlation_ids' => [],
            'runbook_refs' => [],
            'status' => 'open',
        ]);

        $activity = $this->makeActivity();
        $result = $activity->execute(new ClassifyInput($incident->id));

        $this->assertEquals(Severity::Sev2, $result->severity);
        $this->assertSame(0.92, $result->confidence);

        $incident->refresh();
        $this->assertSame('SEV2', $incident->severity);
    }

    public function test_is_idempotent(): void
    {
        $this->app->bind(LlmClient::class, fn () => new class implements LlmClient
        {
            public static function complete(string $system, string $prompt): string
            {
                return json_encode(['severity' => 'SEV2', 'confidence' => 0.88]);
            }
        });

        $incident = Incident::create([
            'raw_payload' => 'Elevated error rate on checkout',
            'correlation_ids' => [],
            'runbook_refs' => [],
            'status' => 'open',
        ]);

        $activity = $this->makeActivity();
        $input = new ClassifyInput($incident->id);

        $first = $activity->execute($input);
        $second = $activity->execute($input);

        $this->assertEquals($first->severity, $second->severity);
        $this->assertSame($first->confidence, $second->confidence);

        $incident->refresh();
        $this->assertSame('SEV2', $incident->severity);
    }

    private function makeActivity(): ClassifyActivity
    {
        return new class extends ClassifyActivity
        {
            public function __construct() {}
        };
    }
}
