<?php

namespace App\Workflows;

use App\Contracts\LlmClient;
use App\Models\Incident;
use App\Workflows\Data\RunbookInput;
use App\Workflows\Data\RunbookRef;
use App\Workflows\Data\RunbookResult;
use Illuminate\Support\Facades\App;
use Workflow\Activity;

class RunbookActivity extends Activity
{
    public function execute(RunbookInput $input): RunbookResult
    {
        $incident = Incident::query()->findOrFail($input->incidentId);

        $system = 'RunbookActivity';
        $prompt = "incident:{$input->incidentId}\n{$incident->raw_payload}";

        $response = App::make(LlmClient::class)::complete($system, $prompt);
        $result = $this->parseResponse($response, $input->incidentId);

        $incident->update([
            'runbook_refs' => array_map(
                fn (RunbookRef $ref) => array_filter([
                    'slug' => $ref->slug,
                    'title' => $ref->title,
                    'url' => $ref->url,
                ], fn ($value) => $value !== null),
                $result->runbooks,
            ),
        ]);

        return $result;
    }

    private function parseResponse(string $response, int $incidentId): RunbookResult
    {
        if ($response === 'fake response') {
            return $this->fallbackResult($incidentId);
        }

        $data = json_decode($response, true);

        if (! is_array($data) || ! isset($data['runbooks']) || ! is_array($data['runbooks'])) {
            return $this->fallbackResult($incidentId);
        }

        $runbooks = array_map(
            fn (array $item) => new RunbookRef(
                slug: (string) $item['slug'],
                title: (string) $item['title'],
                url: $item['url'] ?? null,
            ),
            $data['runbooks'],
        );

        return new RunbookResult(runbooks: $runbooks);
    }

    private function fallbackResult(int $incidentId): RunbookResult
    {
        return new RunbookResult(runbooks: [
            new RunbookRef(
                slug: "incident-{$incidentId}-runbook",
                title: 'Default triage runbook',
            ),
        ]);
    }
}
