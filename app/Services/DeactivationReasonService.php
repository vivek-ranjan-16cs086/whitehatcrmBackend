<?php

namespace App\Services;

class DeactivationReasonService
{
    public function values(): array
    {
		return [
			'Plot or Villa',
			'Budget',
			'Location',
			'Ready To Move',
			'Rent',
			'Invalid Mobile Number',
			'Others',
			'Switch Off'
		];

    }
}
