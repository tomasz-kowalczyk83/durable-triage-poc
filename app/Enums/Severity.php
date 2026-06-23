<?php

namespace App\Enums;

enum Severity: string
{
    case Sev1 = 'SEV1';   // most severe
    case Sev2 = 'SEV2';
    case Sev3 = 'SEV3';
    case Sev4 = 'SEV4';

    public function requiresApproval(): bool   // pure → safe to call in workflow code
    {
        return in_array($this, [self::Sev1, self::Sev2], true);
    }
}
