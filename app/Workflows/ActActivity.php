<?php

namespace App\Workflows;

use App\Contracts\LlmClient;
use App\Models\Incident;
use App\Workflows\Data\ActInput;
use App\Workflows\Data\ActResult;
use Workflow\Activity;

class ActActivity extends Activity
{
    public function execute(ActInput $input): ActResult
    {
        $response = app(LlmClient::class)::complete('act', $input->suggestion);

        $actionRef = json_decode($response, true, 512, JSON_THROW_ON_ERROR)['actionRef'];

        Incident::query()->whereKey($input->incidentId)->update(['status' => 'acted']);

        return new ActResult($actionRef);
    }
}
