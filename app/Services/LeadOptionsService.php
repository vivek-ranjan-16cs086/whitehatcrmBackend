<?php

namespace App\Services;

use App\Models\Lead;
use App\Models\Project;
use Illuminate\Support\Facades\Schema;

class LeadOptionsService
{
    public function all(): array
    {
        $columns = collect(Schema::getColumns('leads'))->keyBy('name');
        $options = [];
        foreach (['status', 'type', 'visit_status', 'deactivate_reason', 'lead_source', 'inventory'] as $field) {
            $column = $columns->get($field);
            $values = collect();
            if ($column) {
                // MySQL exposes all allowed enum values, including unused choices.
                if (preg_match('/^enum\((.*)\)$/is', $column['type'], $matches)) {
                    $values = collect(str_getcsv($matches[1], ',', "'", '\\'));
                }
                if (is_string($column['default']) && $column['default'] !== '') {
                    $values->push(trim($column['default'], "'"));
                }
                $values = $values->merge(Lead::withTrashed()->whereNotNull($field)
                    ->where($field, '!=', '')->distinct()->pluck($field));
            }
            if ($field === 'inventory' && Schema::hasColumn('projects', 'inventory')) {
                $values = $values->merge(Project::whereNotNull('inventory')
                    ->where('inventory', '!=', '')->distinct()->pluck('inventory'));
            }
            $options[$field] = $values->filter(fn ($value) => is_string($value) && trim($value) !== '')
                ->unique()->sort()->values()->all();
        }
        return $options;
    }
}
