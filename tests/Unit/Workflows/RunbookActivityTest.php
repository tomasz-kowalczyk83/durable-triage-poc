<?php

namespace Tests\Unit\Workflows;

use App\Contracts\LlmClient;
use App\Models\Incident;
use App\Support\FakeLlmClient;
use App\Workflows\Data\RunbookInput;
use App\Workflows\Data\RunbookRef;
use App\Workflows\RunbookActivity;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RunbookActivityTest extends TestCase
{
    use RefreshDatabase;

    public function test_selects_runbooks_and_updates_database(): void
    {
        $this->app->bind(LlmClient::class, fn () => new class implements LlmClient
        {
            public static function complete(string $system, string $prompt): string
            {
                return json_encode([
                    'runbooks' => [
                        [
                            'slug' => 'restart-checkout',
                            'title' => 'Restart checkout service',
                            'url' => 'https://runbooks.example/restart-checkout',
                        ],
                    ],
                ]);
            }
        });

        $incident = Incident::create([
            'raw_payload' => 'Checkout pods unhealthy',
            'correlation_ids' => [],
            'runbook_refs' => [],
            'status' => 'open',
        ]);

        $activity = $this->makeActivity();
        $result = $activity->execute(new RunbookInput($incident->id));

        $this->assertCount(1, $result->runbooks);
        $this->assertInstanceOf(RunbookRef::class, $result->runbooks[0]);
        $this->assertSame('restart-checkout', $result->runbooks[0]->slug);
        $this->assertSame('Restart checkout service', $result->runbooks[0]->title);
        $this->assertSame('https://runbooks.example/restart-checkout', $result->runbooks[0]->url);

        $incident->refresh();
        $this->assertSame([
            [
                'slug' => 'restart-checkout',
                'title' => 'Restart checkout service',
                'url' => 'https://runbooks.example/restart-checkout',
            ],
        ], $incident->runbook_refs);
    }

    public function test_uses_deterministic_fake_llm_client_response(): void
    {
        $this->app->bind(LlmClient::class, FakeLlmClient::class);

        $incident = Incident::create([
            'raw_payload' => 'Unknown failure mode',
            'correlation_ids' => [],
            'runbook_refs' => [],
            'status' => 'open',
        ]);

        $activity = $this->makeActivity();
        $result = $activity->execute(new RunbookInput($incident->id));

        $this->assertCount(1, $result->runbooks);
        $this->assertSame('rb-001', $result->runbooks[0]->slug);
        $this->assertSame('Restart pod', $result->runbooks[0]->title);

        $incident->refresh();
        $this->assertCount(1, $incident->runbook_refs);
    }

    public function test_is_idempotent(): void
    {
        $this->app->bind(LlmClient::class, fn () => new class implements LlmClient
        {
            public static function complete(string $system, string $prompt): string
            {
                return json_encode([
                    'runbooks' => [
                        [
                            'slug' => 'scale-workers',
                            'title' => 'Scale background workers',
                        ],
                    ],
                ]);
            }
        });

        $incident = Incident::create([
            'raw_payload' => 'Queue depth growing',
            'correlation_ids' => [],
            'runbook_refs' => [],
            'status' => 'open',
        ]);

        $activity = $this->makeActivity();
        $input = new RunbookInput($incident->id);

        $first = $activity->execute($input);
        $second = $activity->execute($input);

        $this->assertSame($first->runbooks[0]->slug, $second->runbooks[0]->slug);

        $incident->refresh();
        $this->assertSame('scale-workers', $incident->runbook_refs[0]['slug']);
    }

    private function makeActivity(): RunbookActivity
    {
        return new class extends RunbookActivity
        {
            public function __construct() {}
        };
    }
}
