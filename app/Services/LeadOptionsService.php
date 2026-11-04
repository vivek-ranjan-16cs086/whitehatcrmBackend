<?php

namespace App\Services;

use App\Models\Lead;
use App\Models\Project;
use Illuminate\Support\Facades\Schema;

class LeadOptionsService
{
    public function all(): array
    {
        $columns = array_flip(Schema::getColumnListing('leads'));
        $options = [];
        foreach (['status', 'type', 'visit_status', 'deactivate_reason', 'lead_source', 'inventory'] as $field) {
            $values = collect();
            if (isset($columns[$field])) {
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
