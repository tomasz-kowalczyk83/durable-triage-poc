<?php

namespace App\Workflows;

use App\Workflows\Data\ActInput;
use App\Workflows\Data\ClassifyInput;
use App\Workflows\Data\CorrelateInput;
use App\Workflows\Data\RunbookInput;
use App\Workflows\Data\SynthesiseInput;
use Workflow\SignalMethod;
use Workflow\Workflow;

use function Workflow\activity;
use function Workflow\all;
use function Workflow\awaitWithTimeout;

class TriageWorkflow extends Workflow
{
    public const DEFAULT_APPROVAL_TIMEOUT_SECONDS = 60;

    public bool $approved = false;

    #[SignalMethod]
    public function approve(bool $value = true): void
    {
        $this->approved = $value;
    }

    public function execute(int $incidentId, int $approvalTimeoutSeconds = self::DEFAULT_APPROVAL_TIMEOUT_SECONDS)
    {
        [$class, $corr, $runbook] = yield all([
            activity(ClassifyActivity::class, new ClassifyInput($incidentId)),
            activity(CorrelateActivity::class, new CorrelateInput($incidentId)),
            activity(RunbookActivity::class, new RunbookInput($incidentId)),
        ]);

        $synthesiseResult = yield activity(SynthesiseActivity::class, new SynthesiseInput(
            incidentId: $incidentId,
            severity: $class->severity,
            correlations: $corr->correlationIds,
            runbooks: $runbook->runbooks,
        ));

        if ($class->severity->requiresApproval()) {
            $approved = yield awaitWithTimeout($approvalTimeoutSeconds, fn (): bool => $this->approved);
        } else {
            $approved = true;
        }

        if ($approved) {
            return yield activity(ActActivity::class, new ActInput($incidentId, $synthesiseResult->suggestion));
        }

        return yield activity(EscalateActivity::class, $incidentId);
    }
}
