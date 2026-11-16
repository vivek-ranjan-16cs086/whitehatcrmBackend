<?php

namespace App\Services;

use App\Models\Lead;
use App\Models\Project;
use App\Models\User;
use Carbon\Carbon;

class LeadHeaderFilterService
{
    public const FIELDS = ['source_website', 'source_page_name', 'assigned_to', 'status', 'visit_date', 'visit_status', 'inventory', 'project_id', 'type'];

    public function forUser(User $user): array
    {
        $base = Lead::query()->when(! $user->canViewAllLeads(), fn ($query) => $query->where('assigned_to', $user->id));
        $options = [];
        foreach (self::FIELDS as $field) {
            $values = (clone $base)->select($field)->distinct()->orderBy($field)->pluck($field);
            $empty = $values->contains(fn ($value) => $value === null || $value === '');
            $values = $values->filter(fn ($value) => $value !== null && $value !== '');
            $options[$field] = match ($field) {
                'assigned_to' => User::with('roles')->whereIn('id', $values)->orderBy('name')->get()->map(fn ($owner) => [
                    'value' => (string) $owner->id,
                    'label' => $owner->name.($owner->roles->isNotEmpty() ? ' - '.$owner->roles->pluck('name')->join(', ') : ''),
                ])->all(),
                'project_id' => Project::whereIn('id', $values)->orderBy('name')->get()->map(fn ($project) => ['value' => (string) $project->id, 'label' => $project->name])->all(),
                'visit_date' => $values->map(fn ($value) => ['value' => Carbon::parse($value)->format('Y-m-d'), 'label' => Carbon::parse($value)->format('d M Y')])->unique('value')->values()->all(),
                default => $values->map(fn ($value) => [
                    'value' => (string) $value,
                    'label' => in_array($field, ['status', 'type', 'visit_status'], true) ? ucwords(str_replace(['_', '-'], ' ', $value)) : (string) $value,
                ])->values()->all(),
            };
            if ($empty) {
                array_unshift($options[$field], ['value' => '__empty__', 'label' => $field === 'assigned_to' ? 'Unassigned' : 'Not set']);
            }
        }

        return $options;
    }
}
