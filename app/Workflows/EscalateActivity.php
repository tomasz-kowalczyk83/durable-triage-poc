<?php

namespace App\Workflows;

use App\Models\Incident;
use Workflow\Activity;

class EscalateActivity extends Activity
{
    public function execute(int $incidentId): array
    {
        Incident::query()->whereKey($incidentId)->update(['status' => 'escalated']);

        return ['escalated' => true];
    }
}
