<?php

namespace App\Jobs\Naive;

use App\Jobs\Naive\Concerns\ChainsNaiveTriage;
use App\Services\NaiveTriagePipeline;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ReCheckApprovalJob implements ShouldQueue
{
    use ChainsNaiveTriage;
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(
        public int $incidentId,
        public int $attempt = 1,
    ) {}

    public function handle(): void
    {
        $incident = $this->incident();

        match ($incident->status) {
            'approved' => ActJob::dispatch($this->incidentId),
            'rejected' => EscalateJob::dispatch($this->incidentId),
            'awaiting_approval' => $this->scheduleAnotherPoll(),
            default => null,
        };
    }

    private function scheduleAnotherPoll(): void
    {
        self::dispatch($this->incidentId, $this->attempt + 1)
            ->delay(now()->addSeconds(NaiveTriagePipeline::APPROVAL_POLL_SECONDS));
    }
}
