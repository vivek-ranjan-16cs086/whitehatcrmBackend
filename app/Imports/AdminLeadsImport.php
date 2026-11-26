<?php

namespace App\Imports;

use App\Models\Lead;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;
use App\Services\LeadEnumOptions;
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
        'lead_source',
        'visit_status',
        'inventory',
        'deactivate_reason',
    ];

    public function __construct(
        private readonly LeadEnumOptions $enums,
        private readonly string $sourceWebsite,
        private readonly ?string $leadSource,
    ) {}

    public function bindValue(Cell $cell, mixed $value): bool
    {
        $cell->setValueExplicit($value, DataType::TYPE_STRING);

        return true;
    }

    public function collection(Collection $rows): void
    {
        $enumRules = [];
        foreach (array_keys(config('lead-enums')) as $field) {
            $enumRules[$field] = ['nullable', 'string', $this->enums->rule($field)];
        }
        $enumRules['deactivate_reason'][] = 'required_if:status,deactive';

        foreach ($rows as $index => $row) {
            $data = $row->toArray();
            $rowNumber = $index + 2;

            if (collect($data)->every(fn ($value) => $value === null || trim((string) $value) === '')) {
                continue;
            }

            foreach (array_keys($enumRules) as $field) {
                $value = trim((string) ($data[$field] ?? ''));
                if ($value === '') {
                    unset($data[$field]);
                } else {
                    $data[$field] = $value;
                }
            }

            $validator = Validator::make($data, [
                'name' => 'required|string|max:255',
                'phone' => 'nullable|string|max:20',
                'email' => 'nullable|email|max:255',
                'message' => 'nullable|string',
                ...$enumRules,
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
            $leadData['lead_source'] ??= $this->leadSource;
            $leadData['status'] = $leadData['status'] ?? 'active';
            $leadData['type'] = $leadData['type'] ?? 'hot';

            $lead = new Lead($leadData);
            $lead->notes = $leadData['notes'] ?? null;
            $lead->deactivate_reason = $lead->status === 'deactive' ? $leadData['deactivate_reason'] : null;
            $lead->save();
        }
    }
}
