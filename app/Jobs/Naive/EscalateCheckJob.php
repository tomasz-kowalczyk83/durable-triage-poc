<?php

namespace App\Jobs\Naive;

use App\Jobs\Naive\Concerns\ChainsNaiveTriage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class EscalateCheckJob implements ShouldQueue
{
    use ChainsNaiveTriage;
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(public int $incidentId) {}

    public function handle(): void
    {
        $incident = $this->incident();

        if ($incident->status === 'awaiting_approval') {
            EscalateJob::dispatch($this->incidentId);
        }
    }
}
