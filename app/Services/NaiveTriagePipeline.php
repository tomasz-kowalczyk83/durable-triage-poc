<?php

namespace App\Services;

use App\Jobs\Naive\ClassifyJob;
use App\Models\Incident;

class NaiveTriagePipeline
{
    public const APPROVAL_TIMEOUT_SECONDS = 300;

    public const APPROVAL_POLL_SECONDS = 5;

    public function start(int $incidentId): void
    {
        Incident::whereKey($incidentId)->update(['status' => 'triaging']);

        ClassifyJob::dispatch($incidentId);
    }

    public static function startPipeline(int $incidentId): void
    {
        app(self::class)->start($incidentId);
    }
}
