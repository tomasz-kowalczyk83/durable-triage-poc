<?php

namespace App\Jobs\Naive;

use App\Enums\Severity;
use App\Jobs\Naive\Concerns\ChainsNaiveTriage;
use App\Services\NaiveTriagePipeline;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SynthesiseJob implements ShouldQueue
{
    use ChainsNaiveTriage;
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(public int $incidentId) {}

    public function handle(TriageStepRunner $runner): void
    {
        $incident = $this->incident();
        $result = $runner->synthesise($incident);

        $incident->update([
            'suggestion' => $result->suggestion,
            'status' => 'synthesised',
        ]);

        $severity = Severity::from($incident->fresh()->severity ?? Severity::Sev2->value);

        if ($severity->requiresApproval()) {
            $incident->update(['status' => 'awaiting_approval']);

            ReCheckApprovalJob::dispatch($this->incidentId)
                ->delay(now()->addSeconds(NaiveTriagePipeline::APPROVAL_POLL_SECONDS));

            EscalateCheckJob::dispatch($this->incidentId)
                ->delay(now()->addSeconds(NaiveTriagePipeline::APPROVAL_TIMEOUT_SECONDS));

            return;
        }

        ActJob::dispatch($this->incidentId);
    }
}
