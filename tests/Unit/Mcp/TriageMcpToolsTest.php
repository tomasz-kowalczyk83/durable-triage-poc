<?php

namespace Tests\Unit\Mcp;

use App\Mcp\Tools\GetIncidentTool;
use App\Mcp\Tools\ListIncidentsTool;
use App\Models\Incident;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Mcp\Request;
use Tests\TestCase;

class TriageMcpToolsTest extends TestCase
{
    use RefreshDatabase;

    public function test_list_incidents_tool_returns_incidents(): void
    {
        $incident = Incident::factory()->create(['status' => 'open']);

        $response = (new ListIncidentsTool)->handle(new Request(['limit' => 10]));

        $this->assertStringContainsString((string) $incident->id, (string) $response->content());
        $this->assertStringContainsString('open', (string) $response->content());
    }

    public function test_get_incident_tool_returns_incident_details(): void
    {
        $incident = Incident::factory()->create();

        $response = (new GetIncidentTool)->handle(new Request(['id' => $incident->id]));

        $this->assertStringContainsString($incident->raw_payload, (string) $response->content());
    }

    public function test_get_incident_tool_handles_missing_incident(): void
    {
        $response = (new GetIncidentTool)->handle(new Request(['id' => 99999]));

        $this->assertSame('Incident not found.', (string) $response->content());
    }
}
