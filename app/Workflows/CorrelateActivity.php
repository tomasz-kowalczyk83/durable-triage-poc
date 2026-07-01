<?php

namespace App\Workflows;

use App\Contracts\LlmClient;
use App\Models\Incident;
use App\Workflows\Data\CorrelateInput;
use App\Workflows\Data\CorrelateResult;
use App\Workflows\Data\CorrelationRef;
use Illuminate\Support\Facades\App;
use Workflow\Activity;

class CorrelateActivity extends Activity
{
    public function execute(CorrelateInput $input): CorrelateResult
    {
        $incident = Incident::query()->findOrFail($input->incidentId);

        $system = 'CorrelateActivity';
        $prompt = "incident:{$input->incidentId}\n{$incident->raw_payload}";

        $response = App::make(LlmClient::class)::complete($system, $prompt);
        $result = $this->parseResponse($response, $input->incidentId);

        $incident->update([
            'correlation_ids' => array_map(
                fn (CorrelationRef $ref) => [
                    'incidentId' => $ref->incidentId,
                    'title' => $ref->title,
                    'similarity' => $ref->similarity,
                ],
                $result->correlationIds,
            ),
        ]);

        return $result;
    }

    private function parseResponse(string $response, int $incidentId): CorrelateResult
    {
        if ($response === 'fake response') {
            return $this->fallbackResult($incidentId);
        }

        $data = json_decode($response, true);

        if (! is_array($data) || ! isset($data['correlationIds']) || ! is_array($data['correlationIds'])) {
            return $this->fallbackResult($incidentId);
        }

        $correlationIds = array_map(
            fn (array $item) => new CorrelationRef(
                incidentId: (int) $item['incidentId'],
                title: (string) $item['title'],
                similarity: (float) $item['similarity'],
            ),
            $data['correlationIds'],
        );

        return new CorrelateResult(correlationIds: $correlationIds);
    }

    private function fallbackResult(int $incidentId): CorrelateResult
    {
        if ($incidentId <= 1) {
            return new CorrelateResult(correlationIds: []);
        }

        return new CorrelateResult(correlationIds: [
            new CorrelationRef(
                incidentId: $incidentId - 1,
                title: "Related incident #{$incidentId}",
                similarity: 0.8,
            ),
        ]);
    }
}
