<?php

namespace App\Imports;

use App\Models\Lead;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\DefaultValueBinder;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\DataType;

class AdminLeadsImport extends DefaultValueBinder implements SkipsEmptyRows, ToCollection, WithCustomValueBinder, WithHeadingRow
{
    public const HEADINGS = [
        'name',
        'phone',
        'email',
        'message',
        'status',
        'type',
        'follow_up_date',
        'notes',
        'source_page_name',
    ];

    private const STATUSES = [
        'not_available',
        'active',
        'booked',
        'deactive',
        'follow_up',
        'follow_up_expired',
        'follow_up_complete',
    ];

    public function __construct(private readonly string $sourceWebsite) {}

    public function bindValue(Cell $cell, mixed $value): bool
    {
        $cell->setValueExplicit($value, DataType::TYPE_STRING);

        return true;
    }

    public function collection(Collection $rows): void
    {
        foreach ($rows as $index => $row) {
            $data = $row->toArray();
            $rowNumber = $index + 2;

            if (collect($data)->every(fn ($value) => $value === null || trim((string) $value) === '')) {
                continue;
            }

            $validator = Validator::make($data, [
                'name' => 'required|string|max:255',
                'phone' => 'nullable|string|max:20',
                'email' => 'nullable|email|max:255',
                'message' => 'nullable|string',
                'status' => ['nullable', 'string', Rule::in(self::STATUSES)],
                'type' => ['nullable', 'string', Rule::in(['hot', 'warm', 'cold'])],
                'follow_up_date' => 'nullable|date',
                'notes' => 'nullable|string',
                'source_page_name' => 'nullable|string|max:255',
            ]);

            if ($validator->fails()) {
                throw ValidationException::withMessages([
                    'file' => "Row {$rowNumber}: ".$validator->errors()->first(),
                ]);
            }

            $leadData = $validator->validated();
            $phone = trim((string) ($leadData['phone'] ?? ''));

            if ($phone !== '' && Lead::where('phone', $phone)->exists()) {
                throw ValidationException::withMessages([
                    'file' => "Row {$rowNumber}: a lead with this phone number already exists.",
                ]);
            }

            $leadData['phone'] = $phone !== '' ? $phone : null;
            $leadData['source_website'] = $this->sourceWebsite;
            $leadData['status'] = $leadData['status'] ?? 'active';
            $leadData['type'] = $leadData['type'] ?? 'hot';

            $lead = new Lead($leadData);
            $lead->notes = $leadData['notes'] ?? null;
            $lead->save();
        }
    }
}
