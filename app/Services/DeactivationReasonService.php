<?php

namespace App\Services;

class DeactivationReasonService
{
    public function values(): array
    {
        return app(LeadEnumOptions::class)->values('deactivate_reason');
    }
}
