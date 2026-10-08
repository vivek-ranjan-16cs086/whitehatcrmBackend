<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\BaseController;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;
use Exception;
use Spatie\Permission\Models\Role;
use App\Models\Project;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\JsonResponse;

class ProjectController extends BaseController
{
	
    public function __construct()
    {
        
    }
	
	//get all projects
	public function getProjects()
    {
        return response()->json([
            'status' => true,
            'data' => Project::latest()->get()
        ]);
    }

     // store projects
    public function storeProject(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'email' => 'required|email',
            'phone' => 'required',
			'inventory' => 'required|in:1 BHK,2 BHK,3 BHK,4 BHK,5 BHK,6 BHK,Plots,Villa,Studio Apartment,Shops',
			'min_price' => 'required',
			'max_price' => 'required'
        ]);

        $project = Project::create($request->all());

        return response()->json([
            'status' => true,
            'message' => 'Project created',
            'data' => $project
        ]);
    }
    
     // show single project by id
    public function showProject($id)
    {
        $project = Project::find($id);

        if (!$project) {
            return response()->json([
                'status' => false,
                'message' => 'Project not found'
            ], 404);
        }

        return response()->json([
            'status' => true,
            'data' => $project
        ]);
    }

     // update project details
    public function updateProject(Request $request, $id)
    {
        $project = Project::find($id);

        if (!$project) {
            return response()->json([
                'status' => false,
                'message' => 'Project not found'
            ], 404);
        }

        $project->update($request->all());

        return response()->json([
            'status' => true,
            'message' => 'Project updated',
            'data' => $project
        ]);
    }

    // Delete project
    public function destroyProject($id)
    {
        $project = Project::find($id);

        if (!$project) {
            return response()->json([
                'status' => false,
                'message' => 'Project not found'
            ], 404);
        }

        $project->delete();

        return response()->json([
            'status' => true,
            'message' => 'Project deleted'
        ]);
    }

    //inventory api
	public function getInventoryList()
	{
		$options = [
			'1 BHK',
			'2 BHK',
			'3 BHK',
			'4 BHK',
			'5 BHK',
			'6 BHK',
			'Plots',
			'Villa',
			'Studio Apartment',
			'Shops'
		];

		$formattedOptions = array_map(function ($option) {
			return [
				'label' => $option,
				'value' => $option
			];
		}, $options);

		return response()->json([
			'success' => true,
			'data' => [
				'options' => $formattedOptions
			]
		]);
	}
	
	public function changeStatus($id)
	{
		$project = Project::find($id);

		if (!$project) {
			return response()->json([
				'status' => false,
				'message' => 'Project not found'
			], 404);
		}

		$project->enable = $project->enable == 1 ? 0 : 1;
		$project->save();

		return response()->json([
			'status' => true,
			'message' => $project->enable ? 'Project activated successfully' : 'Project deactivated successfully',
			'data' => [
				'id' => $project->id,
				'enable' => $project->enable
			]
		]);
	}
}