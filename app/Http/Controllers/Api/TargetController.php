<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\BaseController;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;
use Exception;
use Spatie\Permission\Models\Role;
use App\Models\Project;
use App\Models\User;
use App\Models\Target;
use Carbon\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class TargetController extends BaseController
{
	
    public function __construct()
    {
        
    }
	
	//get target 
	public function getTargets(Request $request)
    {
		
        $query = Target::with([
            'user:id,name',
            'project:id,name'
        ]);
    
        if ($request->filled('start_day')) {
            $query->whereDate('start_day', '>=', $request->start_day);
        }
    
        if ($request->filled('end_day')) {
            $query->whereDate('end_day', '<=', $request->end_day);
        }
		
		if ($request->filled('user_id')) {
			$query->where('user_id', (int) $request->user_id);
		}
    
        $targets = $query->latest()->get();
    
        return response()->json([
            'status' => true,
            'data' => $targets
        ]);
    }
    
	//create target
	public function createTarget(Request $request)
	{
		$validator = Validator::make($request->all(), [
			'project_id' => 'required|exists:projects,id',
			'booking_set' => 'required|integer',
			'booking_done' => 'nullable|integer',
			'site_visited' => 'required|integer',
			'site_visited_done' => 'nullable|integer',
			'start_day' => 'required|date',
			'end_day' => 'required|date|after_or_equal:start_day',
		]);

		// Validation fail
		if ($validator->fails()) {
			return response()->json([
				'status' => false,
				'message' => 'Validation error',
				'errors' => $validator->errors()
			], 422);
		}

		$startDay = Carbon::parse($request->start_day);
		$endDay = Carbon::parse($request->end_day);

		// Check start day is Monday
		if (!$startDay->isMonday()) {
			return response()->json([
				'status' => false,
				'message' => 'Start day must be Monday'
			], 422);
		}

		// Check end day is Sunday
		if (
			!$endDay->isSunday() ||
			$startDay->diffInDays($endDay) != 6
		) {
			return response()->json([
				'status' => false,
				'message' => 'End day must be Sunday of the same week'
			], 422);
		}

		// Check if target already exists for same user & week
		$alreadyExists = Target::where('user_id', auth()->id())
			->whereDate('start_day', $startDay->toDateString())
			->whereDate('end_day', $endDay->toDateString())
			->exists();

		if ($alreadyExists) {
			return response()->json([
				'status' => false,
				'message' => 'Target already exists for this week'
			], 422);
		}

		// Get validated data
		$data = $validator->validated();
		$data['user_id'] = auth()->id();

		// Create target
		$target = Target::create($data);

		return response()->json([
			'status' => true,
			'message' => 'Target created successfully',
			'data' => $target
		]);
	}
		
	// SHOW SINGLE TARGET
    public function show($id)
    {
        $target = Target::with(['user:id,name', 'project:id,name'])->find($id);

        if (!$target) {
            return response()->json([
                'status' => false,
                'message' => 'Target not found'
            ]);
        }

        return response()->json([
            'status' => true,
            'data' => $target
        ]);
    }
	
	// UPDATE TARGET
    public function update(Request $request, $id)
    {
        $target = Target::find($id);

        if (!$target) {
            return response()->json([
                'status' => false,
                'message' => 'Target not found'
            ]);
        }

        $target->update($request->all());

        return response()->json([
            'status' => true,
            'message' => 'Target updated successfully',
            'data' => $target
        ]);
    }
}