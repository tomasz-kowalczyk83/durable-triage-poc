<?php

namespace Tests\Feature\Incidents;

use App\Models\Incident;
use App\Models\User;
use App\Services\IncidentTriageLauncher;
use App\Workflows\TriageWorkflow;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;
use Workflow\WorkflowStub;

class FakeIncidentTriageLauncher extends IncidentTriageLauncher
{
    public int $startedFor = 0;

    public function start(int $incidentId, ?int $failAt = null): WorkflowStub
    {
        $this->startedFor = $incidentId;

        return WorkflowStub::make(TriageWorkflow::class);
    }
}

class IncidentPagesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        WorkflowStub::fake();
    }

    public function test_guests_are_redirected_from_incidents_index(): void
    {
        $this->get(route('incidents.index'))
            ->assertRedirect(route('login'));
    }

    public function test_authenticated_users_can_visit_incidents_index(): void
    {
        $user = User::factory()->create();
        $incident = Incident::factory()->create();

        $this->actingAs($user)
            ->get(route('incidents.index'))
            ->assertOk()
            ->assertSee('Incidents')
            ->assertSee((string) $incident->id);
    }

    public function test_authenticated_users_can_visit_create_page(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('incidents.create'))
            ->assertOk()
            ->assertSee('New incident');
    }

    public function test_authenticated_users_can_create_incident_and_start_workflow(): void
    {
        $user = User::factory()->create();
        $launcher = new FakeIncidentTriageLauncher;

        $this->app->instance(IncidentTriageLauncher::class, $launcher);

        $this->actingAs($user);

        Livewire::test('pages::incidents.create')
            ->set('raw_payload', 'Checkout API latency spike on eu-west-1')
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('incidents.show', ['incident' => 1]));

        $this->assertDatabaseHas('incidents', [
            'raw_payload' => 'Checkout API latency spike on eu-west-1',
            'status' => 'pending',
        ]);

        $this->assertSame(1, $launcher->startedFor);
    }

    public function test_authenticated_users_can_view_incident(): void
    {
        $user = User::factory()->create();
        $incident = Incident::factory()->create([
            'raw_payload' => 'Database connection pool exhausted',
            'status' => 'triaging',
            'severity' => 'SEV2',
        ]);

        $this->actingAs($user)
            ->get(route('incidents.show', $incident))
            ->assertOk()
            ->assertSee('Database connection pool exhausted')
            ->assertSee('triaging')
            ->assertSee('SEV2');
    }
}
