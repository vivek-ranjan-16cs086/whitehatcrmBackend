<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Target;
use App\Models\User;
use App\Models\Project;
use Illuminate\Http\Request;

class TargetController extends Controller
{
    /**
     * Listing Page
     */
    public function index() 
    {
        $users = User::whereHas('roles.permissions', function ($query) {
            $query->where('name', 'view.lead')
                ->where('guard_name', 'web');
        })->orderBy('name')->get();
        $projects = Project::all();

        return view('admin.targets.index', compact('users', 'projects'));
    }

    /**
     * AJAX List (DataTable)
     */
    public function ajaxList(Request $request)
    {
        $draw = $request->draw;
        $start = $request->start;
        $length = $request->length;

        $columnIndex = $request->order[0]['column'] ?? 0;
        $columnName = $request->columns[$columnIndex]['data'] ?? 'created_at';
        $columnSortOrder = $request->order[0]['dir'] ?? 'desc';

        $sortableColumns = [
            'user' => 'user',
            'project' => 'project',
            'booking_set' => 'booking_set',
            'booking_done' => 'booking_done',
            'site_visited' => 'site_visited',
            'site_visited_done' => 'site_visited_done',
            'start_day' => 'start_day',
            'end_day' => 'end_day',
            'status' => 'status',
            'created_at' => 'created_at',
        ];

        $columnName = $sortableColumns[$columnName] ?? 'created_at';
        $columnSortOrder = in_array($columnSortOrder, ['asc', 'desc'], true)
            ? $columnSortOrder
            : 'desc';

        $query = Target::with(['user', 'project']);

        //Global Search
        if ($request->filled('search.value')) {
            $search = $request->input('search.value');

            $query->where(function ($q) use ($search) {
                $q->whereHas('user', function ($q2) use ($search) {
                        $q2->where('name', 'like', "%{$search}%");
                    }
                    )
                        ->orWhereHas('project', function ($q2) use ($search) {
                    $q2->where('name', 'like', "%{$search}%");
                }
                )
                    ->orWhere('booking_set', 'like', "%{$search}%")
                    ->orWhere('booking_done', 'like', "%{$search}%")
                    ->orWhere('site_visited', 'like', "%{$search}%")
                    ->orWhere('site_visited_done', 'like', "%{$search}%");
            });
        }

        //Custom Filters
        if ($request->filled('employee')) {
            $query->whereHas('user', function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->employee . '%');
            });
        }
        if ($request->filled('project')) {
            $query->whereHas('project', function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->project . '%');
            });
        }
        if ($request->filled('booking_set')) {
            $query->where('booking_set', '>=', $request->booking_set);
        }
        if ($request->filled('booking_done')) {
            $query->where('booking_done', '>=', $request->booking_done);
        }
        if ($request->filled('site_visited')) {
            $query->where('site_visited', '>=', $request->site_visited);
        }
        if ($request->filled('site_visited_done')) {
            $query->where('site_visited_done', '>=', $request->site_visited_done);
        }
        if ($request->filled('start_day')) {
            $query->whereDate('start_day', '>=', $request->start_day);
        }
        if ($request->filled('end_day')) {
            $query->whereDate('end_day', '<=', $request->end_day);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $totalRecords = Target::count();
        $totalRecordswithFilter = (clone $query)->count();

        if ($columnName === 'user') {
            $query->orderBy(
                User::select('name')->whereColumn('users.id', 'targets.user_id'),
                $columnSortOrder
            );
        } elseif ($columnName === 'project') {
            $query->orderBy(
                Project::select('name')->whereColumn('projects.id', 'targets.project_id'),
                $columnSortOrder
            );
        } else {
            $query->orderBy($columnName, $columnSortOrder);
        }

        $records = $query
            ->skip($start)
            ->take($length)
            ->get();

        $data_arr = [];
        $sno = $start + 1;

        foreach ($records as $row) {

            // Status Badge
            $statusBadge = match ($row->status) {
                    'active' => "<span class='badge bg-success'>Active</span>",
                    'completed' => "<span class='badge bg-primary'>Completed</span>",
                    default => "<span class='badge bg-warning text-dark'>Pending</span>",
                };

            // Actions (Edit modal + delete)
            $action = "
                <button class='btn btn-sm btn-outline-primary btn-edit-target'
                    data-id='{$row->id}'
                    data-user='{$row->user_id}'
                    data-project='{$row->project_id}'
                    data-booking_set='{$row->booking_set}'
                    data-booking_done='{$row->booking_done}'
                    data-site_visited='{$row->site_visited}'
                    data-site_visited_done='{$row->site_visited_done}'
                    data-start_day='{$row->start_day}'
                    data-end_day='{$row->end_day}'
                    data-status='{$row->status}'>
                    <i class='bi-pencil'></i> 
                </button>

                <button class='btn btn-sm btn-outline-danger btn-delete-target'
                    data-id='{$row->id}'>
                    <i class='bi-trash'></i>
                </button>
            ";

            $data_arr[] = [
                "id" => $sno++,
                "user" => e($row->user->name ?? '-'),
                "project" => e($row->project->name ?? '-'),
                "booking_set" => $row->booking_set,
                "booking_done" => $row->booking_done ?? 0,
                "site_visited" => $row->site_visited,
                "site_visited_done" => $row->site_visited_done ?? 0,
                "start_day" => $row->start_day,
                "end_day" => $row->end_day,
                "status" => $statusBadge,
                "created_at" => $row->created_at
                ? date('d M Y', strtotime($row->created_at))
                : '-',
                "action" => $action,
            ];
        }

        return response()->json([
            "draw" => intval($draw),
            "iTotalRecords" => $totalRecords,
            "iTotalDisplayRecords" => $totalRecordswithFilter,
            "aaData" => $data_arr
        ]);
    }

    /**
     * Store (AJAX)
     */
    public function store(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'project_id' => 'required|exists:projects,id',
            'booking_set' => 'required|integer|min:0',
            'booking_done' => 'nullable|integer|min:0',
            'site_visited' => 'required|integer|min:0',
            'site_visited_done' => 'nullable|integer|min:0',
            'start_day' => 'required|date',
            'end_day' => 'required|date|after_or_equal:start_day',
            'status' => 'required|in:active,completed,pending'
        ]);

        Target::create($request->all());

        return response()->json([
            'status' => true,
            'message' => 'Target created successfully.'
        ]);
    }

    /**
     * Update (AJAX)
     */
    public function update(Request $request, Target $target)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'project_id' => 'required|exists:projects,id',
            'booking_set' => 'required|integer|min:0',
            'booking_done' => 'nullable|integer|min:0',
            'site_visited' => 'required|integer|min:0',
            'site_visited_done' => 'nullable|integer|min:0',
            'start_day' => 'required|date',
            'end_day' => 'required|date|after_or_equal:start_day',
            'status' => 'required|in:active,completed,pending'
        ]);

        $target->update($request->all());

        return response()->json([
            'status' => true,
            'message' => 'Target updated successfully.'
        ]);
    }

    /**
     * Delete (AJAX)
     */
    public function destroy(Target $target)
    {
        $target->delete();

        return response()->json([
            'status' => true,
            'message' => 'Target deleted successfully.'
        ]);
    }

    /**
     * Status Update (AJAX)
     */
    public function updateStatus(Request $request, Target $target)
    {
        $request->validate([
            'status' => 'required|in:active,completed,pending'
        ]);

        $target->update(['status' => $request->status]);

        return response()->json([
            'status' => true,
            'message' => 'Target status updated successfully.'
        ]);
    }
}