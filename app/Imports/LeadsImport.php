<?php

namespace App\Imports;

use App\Models\Lead;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
class LeadsImport implements ToCollection, WithHeadingRow, SkipsEmptyRows
{
    public function collection(Collection $rows)
	{
		foreach ($rows as $index => $row) {

			$name = trim((string)($row['name'] ?? ''));
			$phone = trim((string)($row['phone'] ?? ''));

			if ($name === '' && $phone === '') {
				continue;
			}

			// Duplicate sirf phone hone par check karo
			if ($phone !== '') {
				$exists = Lead::where('phone', $phone)->exists();

				if ($exists) {
					throw new \Exception("Duplicate lead found at row " . ($index + 2));
				}
			}

			Lead::create([
				'name'        => $name ?: null,
				'phone'       => $phone ?: null,
				'email'       => trim((string)($row['email'] ?? '')),
				'status'      => strtolower(trim((string)($row['status'] ?? 'active'))),
				'type'        => strtolower(trim((string)($row['type'] ?? 'hot'))),
				'lead_source' => $row['lead_source'] ?? null,
				'inventory'   => $row['inventory'] ?? null,
				'budget_min'  => $row['budget_min'] ?? null,
				'budget_max'  => $row['budget_max'] ?? null,
				'remarks'     => $row['remarks'] ?? null, 
			]);
		}
	}
}