<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Lead;
use App\Models\LeadResponse;
use App\Models\LeadTimelineEvent;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class LeadDetailsController extends Controller
{
    private function authorizeLead(Request $request, Lead $lead): bool
    {
        $user = $request->user();
        $admin = $user->hasRole('super-admin', 'web');
        abort_unless($admin || $user->canViewAllLeads() || (string) $lead->assigned_to === (string) $user->id, 404);

        return $admin;
    }

    private function time($value, bool $dateOnly = false): ?string
    {
        if (! $value) {
            return null;
        }

        return Carbon::parse($value)->timezone('Asia/Kolkata')->format($dateOnly ? 'd M Y' : 'd M Y, h:i A').($dateOnly ? '' : ' IST');
    }

    public function show(Request $request, Lead $lead)
    {
        $admin = $this->authorizeLead($request, $lead);
        $request->validate(['section' => 'nullable|in:timeline,responses,ownership', 'page' => 'nullable|integer|min:1|max:1000000']);
        if ($request->filled('section')) {
            return response()->json($this->section($request, $lead, $request->string('section')->toString(), $admin));
        }
        $lead->load(['assignedUser:id,name', 'project:id,name', 'subResponse.response', 'createdBy:id,name', 'updatedBy:id,name']);
        $labels = ['id' => 'Lead ID', 'assigned_to' => 'Current owner', 'project_id' => 'Project',
            'sub_response_id' => 'Current response', 'created_at' => 'Arrived at', 'updated_at' => 'Last updated',
            'created_by' => 'Created by', 'updated_by' => 'Updated by', 'source_website' => 'Source',
            'source_submitted_at' => 'Source submitted at'];
        $fields = collect($lead->getAttributes())->except(['deleted_at'])->map(function ($value, $key) use ($lead, $labels) {
            $value = match ($key) {
                'assigned_to' => $lead->assignedUser?->name ?? 'Unassigned',
                'project_id' => $lead->project?->name ?? $value,
                'sub_response_id' => $lead->subResponse ? trim(($lead->subResponse->response?->name ?? '').' / '.$lead->subResponse->name, ' /') : $value,
                'created_by' => $lead->createdBy?->name ?? $value,
                'updated_by' => $lead->updatedBy?->name ?? $value,
                'created_at', 'updated_at', 'source_submitted_at' => $this->time($value),
                'visit_date' => $this->time($value, true),
                'follow_up_date' => $this->time($value),
                default => $value,
            };

            return ['label' => $labels[$key] ?? ucwords(str_replace('_', ' ', $key)), 'value' => $value];
        })->values();

        return response()->json([
            'id' => $lead->id, 'name' => $lead->name, 'fields' => $fields,
            'current_owner' => $lead->assignedUser?->name ?? 'Unassigned',
            'activity_scope' => $admin ? 'Complete recorded history' : 'Your recorded activity and assignments',
            'sections' => collect(['timeline', 'responses', 'ownership'])->mapWithKeys(fn ($section) => [$section => $this->section($request, $lead, $section, $admin)]),
        ]);
    }

    private function section(Request $request, Lead $lead, string $section, bool $admin): array
    {
        if ($section === 'responses') {
            $query = LeadResponse::where('lead_id', $lead->id)->with('user:id,name');
            if (! $admin) {
                $query->where('user_id', $request->user()->id);
            }
        } else {
            $query = LeadTimelineEvent::where('lead_id', $lead->id);
            if ($section === 'ownership') {
                $query->where('event_type', 'TRANSFER');
            }
            if (! $admin) {
                $query->where(fn ($q) => $q->where('user_id', $request->user()->id)
                    ->orWhere(fn ($q) => $q->where('event_type', 'TRANSFER')->where('assigned_to_user_id', $request->user()->id)));
            }
        }
        $page = $query->orderByDesc('created_at')->orderByDesc('id')->paginate(25, ['*'], 'page', (int) $request->input('page', 1));
        $ids = $page->getCollection()->flatMap(fn ($row) => [$row->user_id, $row->assigned_to_user_id])->filter()->unique();
        $names = User::whereIn('id', $ids)->pluck('name', 'id');
        $items = $page->getCollection()->map(function ($row) use ($section, $lead, $names) {
            if ($section === 'responses') {
                return ['id' => $row->id, 'type' => 'RESPONSE', 'message' => $row->note,
                    'user_name' => $row->user?->name ?? 'Former user', 'created_at' => $this->time($row->created_at),
                    'recording_url' => $row->call_recording ? route('lead-management.recording', [$lead, $row]) : null];
            }

            return ['id' => $row->id, 'type' => $row->event_type, 'message' => $row->message,
                'user_name' => $row->user_name ?: ($names[$row->user_id] ?? 'System / former user'), 'user_role' => $row->user_role,
                'created_at' => $this->time($row->created_at), 'follow_up_date' => $this->time($row->follow_up_date),
                'call_outcome' => $row->call_outcome, 'phone' => $row->phone,
                'assigned_to' => $row->assigned_to_user_id ? ($names[$row->assigned_to_user_id] ?? 'Former user #'.$row->assigned_to_user_id) : null,
                'assignment_context_id' => $row->assignment_context_id];
        });

        return ['items' => $items, 'total' => $page->total(), 'page' => $page->currentPage(), 'has_more' => $page->hasMorePages()];
    }

    public function recording(Request $request, Lead $lead, LeadResponse $response)
    {
        $admin = $this->authorizeLead($request, $lead);
        abort_unless((int) $response->lead_id === (int) $lead->id && ($admin || (int) $response->user_id === (int) $request->user()->id), 404);
        abort_unless($response->call_recording && str_starts_with($response->call_recording, 'call_recordings/') && Storage::disk('public')->exists($response->call_recording), 404);

        return Storage::disk('public')->response($response->call_recording, null, ['Cache-Control' => 'private, no-store', 'X-Content-Type-Options' => 'nosniff']);
    }
}
