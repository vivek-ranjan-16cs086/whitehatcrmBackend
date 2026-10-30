<?php

namespace App\Imports;

use App\Models\Lead;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
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
        'external_id',
        'status',
        'type',
        'assigned_to',
        'follow_up_date',
        'notes',
        'visit_status',
        'visit_date',
        'sub_response_id',
        'project_id',
        'deactivate_reason',
        'lead_source',
        'inventory',
        'budget_min',
        'budget_max',
        'remarks',
        'source_page_id',
        'source_page_name',
        'source_form_id',
        'source_form_name',
        'campaign_id',
        'campaign_name',
        'adset_id',
        'adset_name',
        'ad_id',
        'ad_name',
        'purchase_timeline',
        'site_visit_preference',
        'funding_method',
        'source_submitted_at',
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

    private const VISIT_STATUSES = [
        'not_scheduled',
        'scheduled',
        'completed',
    ];

    private const DEACTIVATE_REASONS = [
        'Plot or Villa',
        'Budget',
        'Location',
        'Ready To Move',
        'Rent',
        'Invalid Mobile Number',
        'Others',
        'Switch Off',
    ];

    public function __construct(private readonly string $sourceWebsite) {}

    public function bindValue(Cell $cell, mixed $value): bool
    {
        $cell->setValueExplicit($value, DataType::TYPE_STRING);

        return true;
    }

    public function collection(Collection $rows): void
    {
        $availableColumns = array_flip(Schema::getColumnListing('leads'));

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
                'external_id' => 'nullable|integer',
                'status' => ['nullable', 'string', Rule::in(self::STATUSES)],
                'type' => ['nullable', 'string', Rule::in(['hot', 'warm', 'cold'])],
                'assigned_to' => 'nullable|integer|exists:users,id',
                'follow_up_date' => 'nullable|date',
                'notes' => 'nullable|string',
                'visit_status' => ['nullable', 'string', Rule::in(self::VISIT_STATUSES)],
                'visit_date' => 'nullable|date',
                'sub_response_id' => 'nullable|integer|exists:sub_responses,id',
                'project_id' => 'nullable|integer|exists:projects,id',
                'deactivate_reason' => ['nullable', 'string', Rule::in(self::DEACTIVATE_REASONS)],
                'lead_source' => 'nullable|string|max:255',
                'inventory' => 'nullable|string|max:255',
                'budget_min' => 'nullable|numeric|min:0',
                'budget_max' => 'nullable|numeric|min:0|gte:budget_min',
                'remarks' => 'nullable|string',
                'source_page_id' => 'nullable|string|max:255',
                'source_page_name' => 'nullable|string|max:255',
                'source_form_id' => 'nullable|string|max:255',
                'source_form_name' => 'nullable|string|max:255',
                'campaign_id' => 'nullable|string|max:255',
                'campaign_name' => 'nullable|string|max:255',
                'adset_id' => 'nullable|string|max:255',
                'adset_name' => 'nullable|string|max:255',
                'ad_id' => 'nullable|string|max:255',
                'ad_name' => 'nullable|string|max:255',
                'purchase_timeline' => 'nullable|string|max:255',
                'site_visit_preference' => 'nullable|string|max:255',
                'funding_method' => 'nullable|string|max:255',
                'source_submitted_at' => 'nullable|date',
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

            $lead = new Lead;
            $leadData['phone'] = $phone !== '' ? $phone : null;
            $leadData['source_website'] = $this->sourceWebsite;
            $leadData['status'] = $leadData['status'] ?? 'active';
            $leadData['type'] = $leadData['type'] ?? 'hot';

            if (isset($availableColumns['created_by'])) {
                $leadData['created_by'] = auth()->id();
            }

            foreach ($leadData as $column => $value) {
                if (isset($availableColumns[$column])) {
                    $lead->setAttribute($column, $value);
                }
            }

            $lead->save();
        }
    }
}
