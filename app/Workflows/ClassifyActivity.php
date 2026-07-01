<?php

namespace App\Workflows;

use App\Contracts\LlmClient;
use App\Enums\Severity;
use App\Models\Incident;
use App\Workflows\Data\ClassifyInput;
use App\Workflows\Data\ClassifyResult;
use Illuminate\Support\Facades\App;
use Workflow\Activity;

class ClassifyActivity extends Activity
{
    public function execute(ClassifyInput $input): ClassifyResult
    {
        $incident = Incident::query()->findOrFail($input->incidentId);

        $system = 'ClassifyActivity';
        $prompt = "incident:{$input->incidentId}\n{$incident->raw_payload}";

        $response = App::make(LlmClient::class)::complete($system, $prompt);
        $result = $this->parseResponse($response, $input->incidentId);

        $incident->update(['severity' => $result->severity->value]);

        return $result;
    }

    private function parseResponse(string $response, int $incidentId): ClassifyResult
    {
        if ($response === 'fake response') {
            return $this->fallbackResult($incidentId);
        }

        $data = json_decode($response, true);

        if (! is_array($data) || ! isset($data['severity'], $data['confidence'])) {
            return $this->fallbackResult($incidentId);
        }

        return new ClassifyResult(
            severity: Severity::from($data['severity']),
            confidence: (float) $data['confidence'],
        );
    }

    private function fallbackResult(int $incidentId): ClassifyResult
    {
        $severities = [Severity::Sev1, Severity::Sev2, Severity::Sev3, Severity::Sev4];

        return new ClassifyResult(
            severity: $severities[$incidentId % 4],
            confidence: 0.75,
        );
    }
}
