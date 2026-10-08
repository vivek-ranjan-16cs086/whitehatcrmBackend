<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Project;
use Illuminate\Http\Request;

class ProjectController extends Controller
{
    /**
     * Display the projects listing page.
     */
    public function index()
    {
        return view('admin.projects.index');
    }

    public function ajaxList(Request $request)
    {
        $draw = $request->draw;
        $start = $request->start;
        $length = $request->length;

        $columnIndex = $request->order[0]['column'] ?? 0;
        $columnName = $request->columns[$columnIndex]['data'] ?? 'created_at';
        $columnSortOrder = $request->order[0]['dir'] ?? 'desc';

        $sortableColumns = [
            'name',
            'email',
            'phone',
            'address',
            'enable',
            'created_at',
        ];

        if (!in_array($columnName, $sortableColumns)) {
            $columnName = 'created_at';
        }

        $query = Project::query();

        // Search
        if ($request->filled('search.value')) {
            $search = $request->input('search.value');

            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        $totalRecords = Project::count();
        $totalRecordswithFilter = (clone $query)->count();

        $records = $query->orderBy($columnName, $columnSortOrder)
            ->skip($start)
            ->take($length)
            ->get();

        $data_arr = [];
        $sno = $start + 1;

        foreach ($records as $row) {
               
            $statusBadge = $row->enable
                ? "<span class='badge bg-success'>Active</span>"
                : "<span class='badge bg-secondary'>Inactive</span>";

            $action = "
				<button type='button' class='btn btn-sm btn-outline-primary btn-edit-project'
					data-id='{$row->id}'
					data-name='" . e($row->name) . "'
					data-email='" . e($row->email) . "'
					data-phone='" . e($row->phone) . "'
					data-address='" . e($row->address) . "'
					data-inventory='".e($row->inventory)."'
					data-enable='{$row->enable}'>
				   <i class='bi-pencil'></i>
				</button>

				<a href='javascript:void(0)' 
				   onclick=\"return myConfirm('projects/delete/{$row->id}')\" 
				   class='btn btn-sm btn-outline-danger'>
					<i class='bi-trash'></i>
				</a>
			";

            $data_arr[] = [
                "id" => $sno++,
                "name" => e($row->name),
                "email" => e($row->email),
                "phone" => e($row->phone),
                "address" => e(\Str::limit($row->address, 30)),
                "enable" => $statusBadge,
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
     * Store a newly created project in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'required|string|max:20',
            'enable' => 'required|boolean',
            'address' => 'nullable|string',
        ]);

        Project::create($request->all());

        if ($request->ajax()) {
            return response()->json([
                'status' => true,
                'message' => 'Project created successfully.'
            ]);
        }
        return redirect()->route('admin.projects.index')->with('success', 'Project created successfully.');
    }

    /**
     * Display the edit project page.
     */
    public function edit(Project $project)
    {
        return view('admin.projects.edit', compact('project'));
    }

    /**
     * Update the specified project in storage.
     */
    public function update(Request $request, Project $project)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'required|string|max:20',
            'enable' => 'required|boolean',
            'address' => 'nullable|string',
        ]);

        $project->update($request->all());

        if ($request->ajax()) {
            return response()->json([
                'status' => true,
                'message' => 'Project updated successfully.'
            ]);
        }
        return redirect()->route('admin.projects.index')->with('success', 'Project updated successfully.');
    }

 	
	public function destroy($id)
    {
        try {
            $project = Project::findOrFail($id);
            if ($project->delete()) {
                //Log::error('Model not found: ' . $e->getMessage());	
                return response()->json([
                    'message' => 'Project deleted successfully.',
                    'status' => true
                ], 200);
            }
        } catch (ModelNotFoundException $e) {
            Log::error('Model not found: ' . $e->getMessage());
            return response()->json([
                'message' => 'Model not found.',
                'status' => false
            ], 404);
        } catch (\Exception $e) {
            Log::error('Error fetching: ' . $e->getMessage());
            return response()->json([
                'message' => 'Internal Server Error',
                'status' => false
            ], 500);
        }
    }
}
