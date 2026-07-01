<?php

namespace App\Workflows;

use App\Contracts\LlmClient;
use App\Models\Incident;
use App\Workflows\Data\SynthesiseInput;
use App\Workflows\Data\SynthesiseResult;
use Workflow\Activity;

class SynthesiseActivity extends Activity
{
    public function execute(SynthesiseInput $input): SynthesiseResult
    {
        $prompt = json_encode([
            'incidentId' => $input->incidentId,
            'severity' => $input->severity->value,
            'correlations' => $input->correlations,
            'runbooks' => $input->runbooks,
        ], JSON_THROW_ON_ERROR);

        $response = app(LlmClient::class)::complete('synthesise', $prompt);

        $suggestion = json_decode($response, true, 512, JSON_THROW_ON_ERROR)['suggestion'];

        Incident::query()->whereKey($input->incidentId)->update(['suggestion' => $suggestion]);

        return new SynthesiseResult($suggestion);
    }
}
