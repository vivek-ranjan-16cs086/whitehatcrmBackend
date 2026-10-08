<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\BaseController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Models\Lead;
use App\Models\LeadResponse;
use App\Models\Project;
use App\Models\Target;
use App\Models\LeadTimelineEvent;
use App\Models\User;
use App\Models\SubResponse;
use Spatie\Permission\Models\Role;
use Carbon\Carbon;
use App\Services\LeadService;
use App\Http\Resources\LeadResource;
use App\Services\NotificationService; 
use Illuminate\Support\Facades\Validator; 
use Illuminate\Support\Facades\Artisan; 

class LeadController extends BaseController
{
	protected $leadService;
	protected $notificationService; 

    public function __construct(
    LeadService $leadService,
    NotificationService $notificationService
	){
		$this->leadService = $leadService;
		$this->notificationService = $notificationService;
	}
	
	//get leads 
    public function getLeads(Request $request): JsonResponse
	{
		try {
			$user_id = $request->user_id;
			$user = auth()->user(); 

			$perPage = $request->get('per_page', 10); 

			$type = $request->get('type');	// hot, cold, warm,ready-to-buy
            	 	
			$status = $request->get('status'); // new, active, etc
			$startDate = $request->get('start_date');   
            $endDate = $request->get('end_date'); 
			$query = $this->leadService->getLeads($request)->with('project');

			// Type filter
			if (!empty($type)) {
				$query->where('type', $type);
			}

			// Status filter 
			if (!empty($status)) {
              $query->where('status', $status);
            }
			
			if ($user && $user->hasRole('salesperson')) {
				$query->where('assigned_to', $user->id)
					  ->where('status', '!=', 'deactive');
			}
			if (!empty($startDate) && !empty($endDate)) {
				$query->whereBetween('created_at', [
					$startDate . ' 00:00:00',
					$endDate . ' 23:59:59'
				]);
			} 
		    if ($user_id) {
				$query->where('assigned_to', $user_id); 
			}

			// Count
			$totalCount = (clone $query)->count();

			//  Pagination
			$leads = $query->latest()->paginate($perPage);
			
			// dd($leads);

			return response()->json([
				'status' => true,
				'message' => $status === 'active' 
					? 'Active leads list' 
					: 'Leads list',

				'data' => [
					'leads' => LeadResource::collection($leads),
					'total_leads' => $totalCount,
					'current_page' => $leads->currentPage(),
					'last_page' => $leads->lastPage(),
					'per_page' => $leads->perPage(),
				]
			]);

		} catch (\Exception $e) {

			return response()->json([
				'status' => false,
				'message' => 'Something went wrong',
				'error' => $e->getMessage()
			], 500);
		}
	}	
	
    //	store leads from whitehat and 360
	public function storeLead(Request $request, NotificationService $notificationService ): JsonResponse
	{
		if ($request->header('x-api-key') !== config('services.erp.key')) {
			return response()->json([
				'status' => false,
				'message' => 'Unauthorized'
			], 401);
		}
		\Log::info('Lead API Hit', $request->all());
		try {
			$request->validate([ 
				'source_website' => 'required|string',
				'name' => 'required|string',
				'phone' => 'nullable|string',
				'email' => 'nullable|email', 
			]);

			$data = $request->all();
            
			$existingLead = Lead::where('source_website', $data['source_website'])
				->where('phone', $data['phone'])
				->first();

			if ($existingLead) {

				$notificationService->sendToRole('super-admin', [
					'title' => 'Duplicate Lead Detected From '. ucfirst(pathinfo($data['source_website'], PATHINFO_FILENAME)),  
					'message' => "Lead '{$existingLead->name}' already exists in the system. Please review and reassign if required.",
					'type' => 'DUPLICATE_LEAD',
					'data' => [
						'leadId' => (string) $existingLead->id,
						'leadName' => (string) $existingLead->name,
						'phone' => (string) $existingLead->phone, 
					]
				]); 

				return response()->json([
					'status' => false,
					'message' => 'Lead already exists. Notification sent to Super Admin.',
					'lead_id' => $existingLead->id
				], 409); 
			}  
			// Find existing lead
			$query = Lead::where('source_website', $data['source_website']); 
			
			if (!empty($data['external_id'])) {
				$query->where('external_id', $data['external_id']);
			}

			$lead = $query->first();

			if (!$lead) {
				$lead = new Lead();
				$lead->status = 'active';
			}

			// Manual safe assignment
			$lead->external_id = $data['external_id'] ?? $lead->external_id;
			$lead->source_website = $data['source_website'];

			$lead->name = $data['name'] ?? $lead->name;
			$lead->phone = $data['phone'] ?? $lead->phone;
			$lead->email = $data['email'] ?? $lead->email;
			$lead->message = $data['message'] ?? $lead->message;
            $lead->created_by = auth()->id();
			$lead->save();
			
			$notificationService->sendToRole('super-admin', [
					'title' => 'Effecient Lead Detected From ' . ucfirst(pathinfo($lead->source_website, PATHINFO_FILENAME)),
					'message' => "Lead from '{$lead->name}' with mobile No- '{$lead->phone}' has been detected in the system. Please review and follow the lead.",
					'type' => 'LEAD_REMINDER',
					'data' => [
						'leadId' => (string) $lead->id,
						'leadName' => (string) $lead->name,
						'phone' => (string) $lead->phone, 
					]
				]); 
            
			return $this->sendResponse('Lead stored successfully', $lead);

		} catch (\Exception $e) {
			return $this->sendError('Failed to store lead', $e->getMessage(), 500);
		}
	}
	
	//assign leads to the salesperson
	public function assignLead(Request $request, NotificationService $notificationService)
	{
		try {
			// Validation
			$validated = $request->validate([
				'lead_id'     => 'required|exists:leads,id',
				'assigned_to' => 'required|exists:users,id',
			]);

			// Find Lead
			$lead = Lead::find($request->lead_id);
			
			$oldAssignedUserId = $lead->assigned_to;
            $assignedUserId = $validated['assigned_to'] ?? null;
			$oldAssignedUser = User::find($oldAssignedUserId);
			$oldAssignedUserName = $oldAssignedUser?->name ?? 'Unknown';

			$assignedUser = User::find($assignedUserId);
			$assignedUserName = $assignedUser?->name ?? 'Unknown';

			// Assign
			$lead->assigned_to = $request->assigned_to; 

			// update status when lead is assigned
			$lead->status = 'active';
            $lead->updated_by = auth()->id();
			$lead->save();
			
			LeadTimelineEvent::create([
				'lead_id' => $lead->id,
				'user_id' => auth()->id(),
				'user_name' => auth()->user()->name,
				'user_role' => auth()->user()->getRoleNames()->first(),
				'event_type' => 'TRANSFER',
				'message' => 'Lead assigned to ' . $assignedUserName,
				'assigned_to_user_id' => $assignedUserId,
			]);
			
			// If assignment changed → send notification
			if (!empty($assignedUserId) && (string)$oldAssignedUserId !== (string)$assignedUserId) {  
				try {
					$notificationService->sendLeadAssignedNotification($assignedUserId, $lead);
				} catch (\Exception $e) {
					\Log::error('Notification error', [
						'error' => $e->getMessage()
					]);
				}
			}


			return response()->json([
				'status'  => true,
				'message' => 'Lead assigned successfully',
				'data'    => new LeadResource($lead) 
			]);

		} catch (\Exception $e) {
			return response()->json([
				'status'  => false,
				'message' => 'Something went wrong',
				'error'   => $e->getMessage()
			], 500);
		}
	}
	
	//for update status
    public function updateLeadStatus(Request $request, $id, NotificationService $notificationService)
	{
			   
			try {
				$user = auth()->user();
				
				$lead = Lead::find($id);

				if (!$lead) {
					return response()->json([
						'status' => false,
						'message' => 'Lead not found'
					], 404);
				}
                
				if (
					$lead->status === 'follow_up' &&
					$lead->follow_up_date &&
					now()->gt($lead->follow_up_date)
				) {

					$lead->status = 'follow_up_expired';
					$lead->save();

					LeadTimelineEvent::create([
						'lead_id' => $lead->id,
						'user_id' => $user->id,
						'user_name' => $user->name,
						'user_role' => $user->getRoleNames()->first(),
						'event_type' => 'FOLLOW_UP_EXPIRED',
						'follow_up_date' => $request->follow_up_date,
						'message' => "Follow up expired", 
					]);
				}
				//if status is booked then user can not change status 
				if (
					$lead->status === 'booked' &&
					$request->has('status') &&
					$request->status !== 'booked'
				) {
					return response()->json([
						'status' => false,
						'message' => 'Booked lead status cannot be changed.'
					], 403);
				} 
				
				$oldStatus = $lead->status;
				$oldType = $lead->type;
				$oldProjectId = $lead->project_id;
				$oldVisitStatus = $lead->visit_status;
				$oldVisitDate = $lead->visit_date;

				// Validation
				$validator = Validator::make($request->all(), [
					'visitStatus' => 'sometimes|in:not_scheduled,scheduled,completed,visited,cancelled,re-visit-plan,re-visit-done',
					'visitDate'   => 'nullable|date|required_if:visitStatus,scheduled',

					'status' => 'sometimes|in:not_available,active,booked,deactive,follow_up,follow_up_expired,follow_up_complete',
					'type'   => 'sometimes|in:hot,cold,warm,ready-to-buy',

					'note'   => 'required|nullable|string',
					'project_id' => 'sometimes|nullable|exists:projects,id',

					'deactivate_reason' => 'sometimes|nullable|in:Plot or Villa,Budget,Location,Ready To Move,Rent,Invalid Mobile Number,Others,Switch Off',

					'lead_source' => 'sometimes|nullable|string|max:255',
					'budget.min' => 'sometimes|nullable|numeric|min:0',
					'budget.max' => 'sometimes|nullable|numeric|gte:budget.min',

					'inventory' => 'sometimes|nullable|string|max:100',
					'follow_up_date' => 'sometimes|date',
					'remarks' => 'nullable|string|required_if:status,follow_up_complete',
					'sub_response_id' => 'required_if:status,follow_up|nullable|exists:sub_responses,id',
					
				]);

				if ($validator->fails()) {
					return response()->json([
						'status' => false,
						'message' => 'Validation error',
						'errors' => $validator->errors()
					], 422);
				}

				if ($request->has('visitStatus')) {
					$lead->visit_status = $request->visitStatus;

					if ($request->visitStatus === 'not_scheduled') {
						$lead->visit_date = null;
					}
				}

				if ($request->has('visitDate')) {
					$lead->visit_date = $request->visitDate;
				}

				if ($request->filled('follow_up_date')) {
					$lead->follow_up_date = $request->follow_up_date;
				}

				if ($request->has('status')) {
					$lead->status = $request->status;  
				}

				if ($request->has('project_id')) {
					$lead->project_id = $request->project_id;
				}

				if ($request->has('status') && $request->status === 'deactive') {
					$lead->deactivate_reason = $request->deactivate_reason;
				} else {
					$lead->deactivate_reason = null;
				}

				if ($request->has('type')) {
					$lead->type = $request->type;
				}

				if ($request->has('lead_source')) {
					$lead->lead_source = $request->lead_source;
				}

				if ($request->has('budget')) {
					$lead->budget_min = $request->input('budget.min');
					$lead->budget_max = $request->input('budget.max');
				}

				if ($request->has('inventory')) {
					$lead->inventory = $request->inventory;
				}

				if ($request->has('remarks')) {
					$lead->remarks = $request->remarks;
				}
				if ($request->filled('sub_response_id')) {
					$lead->sub_response_id = $request->sub_response_id;
				}

				$lead->updated_by = $user->id;
				$lead->save();
				

				// Status changed
				if ($request->has('status') && $oldStatus != $lead->status) {
					LeadTimelineEvent::create([
						'lead_id' => $lead->id,
						'user_id' => $user->id,
						'user_name' => $user->name,
						'user_role' => $user->getRoleNames()->first(),
						'event_type' => 'STATUS',
						'message' => "Status changed from {$oldStatus} to {$lead->status}",
					]);
				}

				// Visit changed
				if (
					$oldVisitStatus != $lead->visit_status ||
					$oldVisitDate != $lead->visit_date
				) {
					LeadTimelineEvent::create([
						'lead_id' => $lead->id,
						'user_id' => $user->id,
						'user_name' => $user->name,
						'user_role' => $user->getRoleNames()->first(),
						'event_type' => 'VISIT',
						'message' => "Visit status updated to {$lead->visit_status}" .
							($lead->visit_date ? " on {$lead->visit_date}" : ''),
					]);
				}

				// Type changed
				if ($request->has('type') && $oldType != $lead->type) {
					LeadTimelineEvent::create([
						'lead_id' => $lead->id,
						'user_id' => $user->id,
						'user_name' => $user->name,
						'user_role' => $user->getRoleNames()->first(),
						'event_type' => 'TYPE',
						'message' => "Lead type updated to {$lead->type}",
					]);
				}

				// Project changed
				if ($request->has('project_id') && $oldProjectId != $lead->project_id) {
					LeadTimelineEvent::create([
						'lead_id' => $lead->id,
						'user_id' => $user->id,
						'user_name' => $user->name,
						'user_role' => $user->getRoleNames()->first(),
						'event_type' => 'PROJECT',
						'message' => "Project changed to " . ($lead->project?->name ?? 'N/A'),
					]);
				}

				// Follow up
				if ($request->filled('follow_up_date')) {
					LeadTimelineEvent::create([
						'lead_id' => $lead->id,
						'user_id' => $user->id,
						'user_name' => $user->name,
						'user_role' => $user->getRoleNames()->first(),
						'event_type' => 'FOLLOW_UP',
						'follow_up_date' => $request->follow_up_date,
						'message' => "Follow up scheduled",
					]);
				}

				// Comment / note
				if ($request->filled('note')) {

					LeadResponse::create([
						'lead_id' => $lead->id,
						'user_id' => $user->id,
						'note' => $request->note
					]);

					LeadTimelineEvent::create([
						'lead_id' => $lead->id,
						'user_id' => $user->id,
						'user_name' => $user->name,
						'user_role' => $user->getRoleNames()->first(),
						'event_type' => 'COMMENT',
						'message' => $request->note,
					]);
				}

				return response()->json([
					'status' => true,
					'message' => 'Lead updated successfully',
					'data' => new LeadResource($lead)
				], 200);

			} catch (\Exception $e) {
				return response()->json([
					'status' => false,
					'message' => 'Something went wrong',
					'error' => $e->getMessage()
				], 500);
			}
	}   

	public function getStatusList()
	{
		$statuses = [
			'Not Available', 
			'Active',
			'Booked',
			'Deactive',
			'Follow Up',
			'Follow Up Expired', 
			'Follow Up Complete'
		];

		$formattedStatus = array_map(function ($status) {
			return [
				'label' => $status,
				'value' => strtolower(str_replace(' ', '_', $status))
			];
		}, $statuses);

		return response()->json([
			'success' => true,
			'data' => [
				'status' => $formattedStatus
			]
		]);
	}
	
	public function getLeadSourceList()
	{
		$sources = [
			'Calling',
			'Whitehat',
			'WHATSAPP',
			'CATCHING',
			'Youtube',
			'4QT',
			'360youtube',
			'99Acre',
			'Advert IQ',
			'Referral',
			'Other',
			'Facebook', 
			'Instagram',
			'Housing.com',
			'PPC',
			'Google',
			'360propguide Website',
			'Static Websites',
			'IVR',
			'Walk-in',
			'Broker',
			'Dealer',
			'Print Media',
			'Out Door Media'
		];

		$formattedSources = array_map(function ($source) {
			return [
				'label' => $source,
				 'value' => $source
			];
		}, $sources);

		return response()->json([
			'success' => true,
			'data' => [
				'lead_source' => $formattedSources
			]
		]);
	}
	
	public function getDeactivateList()
	{
		$options = [
			'Plot or Villa',
			'Budget',
			'Location',
			'Ready To Move',
			'Rent',
			'Invalid Mobile Number',
			'Others',
			'Switch Off'
		];

		$formattedOptions = array_map(function ($option) {
			return [
				'label' => $option,
				'label' => $option
			];
		}, $options);

		return response()->json([
			'success' => true,
			'data' => [
				'options' => $formattedOptions
			]
		]);
	}

	public function getSingleLead($id)
	{
		try {
			$user = auth()->user();

			$leadQuery = Lead::where('id', $id);

			// for salesperson
			if ($user->role == 'salesperson') {
				$leadQuery->where('assigned_to', $user->id);
			}

			// super admin can see all leads

			$lead = Lead::with(['leadResponses' => function ($q) {
				$q->latest()->take(2);
			}])->find($id);

			if (!$lead) {
				return response()->json([
					'status' => false,
					'message' => 'Lead not found'
				]);
			}

			return response()->json([
				'status' => true,
				'data' => new LeadResource($lead)
			]);

		} catch (\Exception $e) {
			return response()->json([
				'status' => false,
				'message' => 'Something went wrong',
				'error' => $e->getMessage() 
			], 500);
		}
	}
	

	public function createNewLead(Request $request)
	{
		$validator = Validator::make($request->all(), [
			'name' => 'required|string|max:255',
			'phone' => 'nullable',
			'email' => 'nullable|email',
			'status' => 'nullable|in:not_available,active,booked,deactive,follow_up,follow_up_expired,follow_up_complete',
			'type' => 'nullable|in:hot,cold,warm,ready-to-buy',

			'assigned_to' => 'nullable|exists:users,id',
			'follow_up_date' => 'nullable|date',

			'visit_status' => 'nullable|in:not_scheduled,scheduled,completed',
			'visit_date' => 'nullable|date',

			'notes' => 'nullable|string',
			'lead_source' => 'sometimes|nullable|in:Calling,Whitehat,WHATSAPP,CATCHING,Youtube,4QT,360youtube,99Acre,Advert IQ,Referral,Other,Facebook,Instagram,Housing.com,PPC,Google,360propguide Website,Static Websites,IVR,Walk-in,Broker,Dealer,Print Media,Out Door Media',
		]); 

		//Validation fail
		if ($validator->fails()) {
			return response()->json([
				'status' => false,
				'message' => 'Validation error',
				'errors' => $validator->errors()
			], 422);
		}

		// Create lead
		$data = $validator->validated();
		$data['created_by'] = auth()->id();

		$lead = Lead::create($data);
		$user = auth()->user();
		if ($request->filled('follow_up_date')) {

			LeadTimelineEvent::create([
				'lead_id' => $lead->id,
				'user_id' => $user->id,
				'user_name' => $user->name,
				'user_role' => $user->getRoleNames()->first(),
				'event_type' => 'FOLLOW_UP',
				'follow_up_date' => $request->follow_up_date,
			]);
		}

		return response()->json([
			'status' => true,
			'message' => 'Lead created successfully',
			'data' => new LeadResource($lead)
		]);
	}
	
	public function shuffleLeads(Request $request, NotificationService $notificationService)
	{
		try {
			// Validation
			$validated = $request->validate([
				'lead_ids'     => 'required|array',
				'lead_ids.*'   => 'exists:leads,id',
				'assigned_to'  => 'required|exists:users,id',
			]);

			$leadIds = $validated['lead_ids'];
			$assignedUserId = $validated['assigned_to'];
			$assignedUser = User::find($assignedUserId);
            $assignedUserName = $assignedUser?->name ?? 'Unknown';

			// Get leads
			$leads = Lead::whereIn('id', $leadIds)->get();

			$updatedLeads = [];

			foreach ($leads as $lead) {
				$oldAssignedUserId = $lead->assigned_to;
				$oldAssignedUser = User::find($oldAssignedUserId);
                $oldAssignedUserName = $oldAssignedUser?->name ?? 'Unknown';

				// Update lead
				$lead->assigned_to = $assignedUserId;
				$lead->status = 'active';
				$lead->updated_by = auth()->id();
				$lead->save();
                
				LeadTimelineEvent::create([
					'lead_id' => $lead->id,
					'user_id' => auth()->id(),
					'user_name' => auth()->user()->name,
					'user_role' => auth()->user()->getRoleNames()->first(),
					'event_type' => 'TRANSFER',
					'message' => 'Lead transferred from ' . $oldAssignedUserName . ' to ' . $assignedUserName,
					'assigned_to_user_id' => $assignedUserId,
				]);
				$updatedLeads[] = $lead;

				// Send notification only if changed
				if ((string)$oldAssignedUserId !== (string)$assignedUserId) {
					try {
						$notificationService->sendLeadAssignedNotification($assignedUserId, $lead);
					} catch (\Exception $e) {
						\Log::error('Notification error', [
							'lead_id' => $lead->id,
							'error'   => $e->getMessage()
						]);
					}
				}
			}

			return response()->json([
				'status'  => true,
				'message' => 'Leads transfered successfully',
				'data'    => LeadResource::collection($updatedLeads)
			]);

		} catch (\Exception $e) {
			return response()->json([
				'status'  => false,
				'message' => 'Something went wrong',
				'error'   => $e->getMessage()
			], 500);
		}
	}
	
	public function triggerReminder()
	{
		Artisan::call('crm:lead-reminder');

		return response()->json([
			'status' => true,
			'message' => 'Reminder job executed successfully'
		]);
	}
	
	
	//admin and salesperson
	public function weeklyVisitTarget(Request $request)
	{
		try {
			$user = auth()->user();
			$today = Carbon::now();

			// Base target query
			$targetQuery = Target::with('user')
				->where('status', 'active')
				->whereDate('start_day', '<=', $today)
				->whereDate('end_day', '>=', $today);

			//salesperson → only own
			if ($user->hasRole('salesperson')) {
				$targetQuery->where('user_id', $user->id);
			}

			$targets = $targetQuery->get();

			if ($targets->isEmpty()) {
				return response()->json([
					'status' => false,
					'message' => 'No active target found'
				]);
			}

			$data = [];
			$totalTarget = 0;
			$totalDone = 0;

			foreach ($targets as $target) {

				$start = Carbon::parse($target->start_day)->startOfDay();
				$end   = Carbon::parse($target->end_day)->endOfDay();

				$leadQuery = Lead::whereBetween('visit_date', [$start, $end])
					->where('assigned_to', $target->user_id)
					->whereNotNull('visit_date');

				$completed = (clone $leadQuery)
					->where('visit_status', 'completed')
					->count();

				$targetValue = $target->site_visited ?? 0;

				$progress = $targetValue > 0
					? round(($completed / $targetValue) * 100, 2)
					: 0;

				$data[] = [
					'user_id' => $target->user_id,
					'user_name' => $target->user?->name,

					'target' => $targetValue,
					'completed_visits' => $completed,
					'remaining' => max(0, $targetValue - $completed),
					'progress' => $progress . '%'
				];

				$totalTarget += $targetValue;
				$totalDone += $completed;
			}

			//Super Admin response
			if ($user->hasRole('super-admin')) {

				$overall = $totalTarget > 0
					? round(($totalDone / $totalTarget) * 100, 2)
					: 0;

				return response()->json([
					'status' => true,
					'role' => 'super-admin',
					'summary' => [
						'total_target' => $totalTarget,
						'total_done' => $totalDone,
						'progress' => $overall . '%'
					],
					'data' => $data
				]);
			}

			// Salesperson response
			return response()->json([
				'status' => true,
				'role' => 'salesperson',
				'data' => $data[0] ?? null
			]);

		} catch (\Exception $e) {
			return response()->json([
				'status' => false,
				'message' => 'Something went wrong',
				'error' => $e->getMessage()
			], 500);
		}
	}
	
	
	//admin and sales person
	public function weeklyBookingTarget(Request $request)
	{
		try {
			$user = auth()->user();
			$today = Carbon::now();

			$targetQuery = Target::with('user')
				->where('status', 'active')
				->whereDate('start_day', '<=', $today)
				->whereDate('end_day', '>=', $today);

			if ($user->hasRole('salesperson')) {
				$targetQuery->where('user_id', $user->id);
			}

			$targets = $targetQuery->get();

			if ($targets->isEmpty()) {
				return response()->json([
					'status' => false,
					'message' => 'No active target found'
				]);
			}

			$data = [];
			$totalTarget = 0;
			$totalDone = 0;

			foreach ($targets as $target) {

				$start = Carbon::parse($target->start_day)->startOfDay();
				$end   = Carbon::parse($target->end_day)->endOfDay();

				$leadQuery = Lead::whereBetween('created_at', [$start, $end])
					->where('assigned_to', $target->user_id);

				$done = (clone $leadQuery)
					->where('status', 'booked')
					->count();

				$targetValue = $target->booking_set ?? 0;

				$progress = $targetValue > 0
					? round(($done / $targetValue) * 100, 2)
					: 0;

				$data[] = [
					'user_id' => $target->user_id,
					'user_name' => $target->user?->name,

					'target' => $targetValue,
					'bookings_done' => $done,
					'remaining' => max(0, $targetValue - $done),
					'progress' => $progress . '%'
				];

				$totalTarget += $targetValue;
				$totalDone += $done;
			}

			//Super Admin
			if ($user->hasRole('super-admin')) {

				$overall = $totalTarget > 0
					? round(($totalDone / $totalTarget) * 100, 2)
					: 0;

				return response()->json([
					'status' => true,
					'role' => 'super-admin',
					'summary' => [
						'total_target' => $totalTarget,
						'total_done' => $totalDone,
						'progress' => $overall . '%'
					],
					'data' => $data
				]);
			}

			// Salesperson
			return response()->json([
				'status' => true,
				'role' => 'salesperson',
				'data' => $data[0] ?? null
			]);

		} catch (\Exception $e) {
			return response()->json([
				'status' => false,
				'message' => 'Something went wrong',
				'error' => $e->getMessage()
			], 500);
		}
	}
	
	
	public function performanceRanking(Request $request)
	{
		  
		try {
			$user_id = $request->user_id;
			$authUser = auth()->user();

			$role = Role::where('name', 'salesperson')->first();

			if (!$role) {
				return response()->json([
					'status' => false,
					'message' => 'Salesperson role not found'
				]);
			}

			$users = $role->users;
			 if ($user_id) {
				$users = $users->where('id', $user_id);
			}

			$data = [];

			foreach ($users as $user) {

				// Total Leads
				$totalLeads = Lead::where('assigned_to', $user->id)->count();

				// Converted Leads
				$converted = Lead::where('assigned_to', $user->id)
					->where('status', 'booked')
					->count();

				// Conversion Rate
				$conversionRate = $totalLeads > 0
					? round(($converted / $totalLeads) * 100)
					: 0;

				// Follow-up score 
				$totalFollowUps = Lead::where('assigned_to', $user->id)
					->whereIn('status', ['follow_up', 'follow_up_complete'])
					->count();

				$completedFollowUps = Lead::where('assigned_to', $user->id)
					->where('status', 'follow_up_complete')
					->count();

				$followUpScore = $totalFollowUps > 0
					? round(($completedFollowUps / $totalFollowUps) * 100)
					: 0;

				// Final Performance Score 
				$performanceScore = round(($conversionRate * 0.6) + ($followUpScore * 0.4));

				$data[] = [
					'user_id' => $user->id,
					'name' => $user->name,

					'total_leads' => $totalLeads,
					'converted' => $converted,
					'conversion_rate' => $conversionRate,
					'performance_score' => $performanceScore
				];
			}

			// Ranking sort
			$data = collect($data)->sortByDesc('performance_score')->values();

			// Add rank
			$data = $data->map(function ($item, $index) {
				$item['rank'] = $index + 1;
				return $item;
			});

			//SUPER ADMIN
			if ($authUser->hasRole('super-admin')) {

				$totalLeadsAll = collect($data)->sum('total_leads');
				$totalConvertedAll = collect($data)->sum('converted');

				$avgConversion = $totalLeadsAll > 0
					? round(($totalConvertedAll / $totalLeadsAll) * 100)
					: 0;

				return response()->json([
					'status' => true,
					'role' => 'super-admin',

					
					'summary' => [
						'total_leads' => $totalLeadsAll,
						'total_converted' => $totalConvertedAll,
						'avg_conversion' => $avgConversion
					],

					
					'data' => $data
				]);
			}

			//SALESPERSON
			if ($authUser->hasRole('salesperson')) {

				$myData = $data->firstWhere('user_id', $authUser->id);

				return response()->json([
					'status' => true,
					'role' => 'salesperson',
					'data' => $myData
				]);
			}

			return response()->json([
				'status' => false,
				'message' => 'Unauthorized'
			], 403);

		} catch (\Exception $e) {
			return response()->json([
				'status' => false,
				'message' => $e->getMessage()
			], 500);
		}
	}


	//pipeline health
	public function pipelineHealth()
	{
		try {
			$user = auth()->user();

			// query
			$baseQuery = Lead::query();

			if ($user->hasRole('salesperson')) {
				$baseQuery->where('assigned_to', $user->id);
			}

			// pipeline
			$totalPipelineCount = (clone $baseQuery)->count();

			// closed lead
			$totalClosed = (clone $baseQuery)
				->where('status', 'booked')
				->count();

			// active leads
			$activeLeadsQuery = (clone $baseQuery)->whereIn('status', [
				'new', 'active', 'follow_up'
			]);

			$totalActiveLeads = (clone $activeLeadsQuery)->count();

			// results
			if ($totalPipelineCount == 0) {
				return response()->json([
					"status" => true,
					"data" => [
						"pipeline_health" => 0,
						"at_risk_leads" => 0,
						"breakdown" => [
							"overdue_followups" => 0,
							"no_followup" => 0,
							"stale_leads" => 0
						]
					]
				]);
			}

			// follow
			$withFollowUp = (clone $activeLeadsQuery)
				->whereNotNull('follow_up_date')
				->count();

			$followUpHealth = $totalActiveLeads > 0
				? ($withFollowUp / $totalActiveLeads) * 100
				: 0;

			// conversion
			$conversion = $totalPipelineCount > 0
				? ($totalClosed / $totalPipelineCount) * 100
				: 0;

			// score
			$freshLeads = (clone $activeLeadsQuery)
				->where('created_at', '>=', Carbon::now()->subDays(3))
				->count();

			$agingScore = $totalActiveLeads > 0
				? ($freshLeads / $totalActiveLeads) * 100
				: 0;

			//score
			$activityScore = 70;
			$velocityScore = 50;

			//pipeline
			$pipelineHealth = round(
				($conversion * 0.25) +
				($followUpHealth * 0.25) +
				($activityScore * 0.20) +
				($agingScore * 0.15) +
				($velocityScore * 0.15)
			);

			// risk lead

			// Overdue followups
			$overdueFollowups = (clone $activeLeadsQuery)
				->whereNotNull('follow_up_date')
				->where('follow_up_date', '<', Carbon::now())
				->count();

			// No followup
			$noFollowup = (clone $activeLeadsQuery)
				->whereNull('follow_up_date')
				->count();

			// Stale leads (>7 days)
			$staleLeads = (clone $activeLeadsQuery)
				->where('created_at', '<', Carbon::now()->subDays(7))
				->count();

			$atRiskLeads =  $noFollowup;

			// response
			return response()->json([
				"status" => true,
				"message" => "Pipeline health fetched successfully",
				"data" => [
					"pipeline_health" => $pipelineHealth,
					"at_risk_leads" => $atRiskLeads,
					"breakdown" => [
						"overdue_followups" => $overdueFollowups,
						"no_followup" => $noFollowup,
						"stale_leads" => $staleLeads
					]
				]
			]);

		} catch (\Exception $e) {
			return response()->json([
				"status" => false,
				"message" => $e->getMessage()
			], 500);
		}
	}
	
	//dashboard card
	public function dashboardCards()
	{
		try {
			$user = auth()->user();
			$today = Carbon::now();

			// sales person
			$role = Role::where('name', 'salesperson')->first();

			if (!$role) {
				return response()->json([
					'status' => false,
					'message' => 'Salesperson role not found'
				]);
			}

			$users = $role->users;

			$data = [];

			foreach ($users as $u) {

				//performance ranking
				$totalLeads = Lead::where('assigned_to', $u->id)->count();

				$converted = Lead::where('assigned_to', $u->id)
					->where('status', 'booked')
					->count();

				// followup ranking
				$totalFollowUps = Lead::where('assigned_to', $u->id)
					->whereIn('status', ['follow_up', 'follow_up_complete'])
					->count();

				$data[] = [
					'total_leads' => $totalLeads,
					'converted' => $converted,
					'followups' => $totalFollowUps
				];
			}

			$totalLeadsAll = collect($data)->sum('total_leads');
			$totalConvertedAll = collect($data)->sum('converted');

			$avgConversion = $totalLeadsAll > 0
				? round(($totalConvertedAll / $totalLeadsAll) * 100)
				: 0;

			//ranking
			$totalFollowups = collect($data)->sum('followups');
			$totalAgents = count($data);

			$avgPerAgent = $totalAgents > 0
				? round($totalFollowups / $totalAgents)
				: 0;

			// data

			$leadQuery = Lead::query();

			if ($user->hasRole('salesperson')) {
				$leadQuery->where('assigned_to', $user->id);
			}

			$leads = $leadQuery->get();

			$totalLeads = $leads->count();

			$activeLeads = $leads->whereIn('status', [
				'new', 'active', 'follow_up'
			])->count();

			$followups = $leads->whereIn('status', [
				'follow_up'
			])->count(); 
			
            $bookedLeads = $leads->where('status', 'booked')->count();
			$expiredLeads = $leads->where('status', 'follow_up_expired')->count();
			
			// target
			$target = Target::where('status', 'active')
				->when($user->hasRole('salesperson'), function ($q) use ($user) {
					$q->where('user_id', $user->id);
				})
				->whereDate('start_day', '<=', $today)
				->whereDate('end_day', '>=', $today)
				->first(); 

			$start = $target ? Carbon::parse($target->start_day)->startOfDay() : null;
			$end   = $target ? Carbon::parse($target->end_day)->endOfDay() : null;

			$visitAchieved = 0;
			$bookingAchieved = 0;

			if ($target) {
				$visitAchieved = Lead::whereBetween('visit_date', [$start, $end])
					->where('assigned_to', $target->user_id ?? $user->id)
					->where('visit_status', 'completed')
					->count();

				$bookingAchieved = Lead::whereBetween('created_at', [$start, $end])
					->where('assigned_to', $target->user_id ?? $user->id)
					->where('status', 'booked')
					->count();
			}

			return response()->json([
				'status' => true,
				'data' => [

					'total_leads' => $totalLeads,
					'active_leads' => $activeLeads,
					'followups' => $followups,
					'performance_percentage' => $avgConversion,
					'followup_percentage' => $avgPerAgent,

					'weekly_visit_target' => $visitAchieved,
					'weekly_booking_target' => $bookingAchieved,
					'booked_leads' => $bookedLeads,
					'expired_leads' => $expiredLeads,
				]
			]);

		} catch (\Exception $e) {
			return response()->json([
				'status' => false,
				'message' => $e->getMessage()
			], 500);
		}
	} 

    //followup leads
    public function getFollowUp(Request $request)
	{
		try {

			$perPage = $request->get('per_page', 10);
			$user = auth()->user();
			$userId = $request->get('user_id');

			$query = Lead::with([
				'assignedUser:id,name'
			])
			->whereNotNull('assigned_to')
			->where('status', 'follow_up');

			if ($user->hasRole('salesperson')) {
				$query->where('assigned_to', $user->id);
			}
			if ($user->hasRole('super-admin') && !empty($userId)) {
				$query->where('assigned_to', $userId);
			}

			$leads = $query->latest()->paginate($perPage);

			return response()->json([
				'status' => true,
				'message' => 'Follow up leads list',
				'data' => [
					'leads' => $leads->items(),
					'current_page' => $leads->currentPage(),
					'last_page' => $leads->lastPage(),
					'total' => $leads->total(),
					'per_page' => $leads->perPage(),
				]
			]);

		} catch (\Exception $e) {

			return response()->json([
				'status' => false,
				'message' => 'Something went wrong',
				'error' => $e->getMessage()
			], 500);
		}
	}
	
	public function getVisitStatusOptions(): JsonResponse
	{
		try {

			$visitStatusOptions = [
				[
					'label' => 'Scheduled',
					'value' => 'scheduled',
				],
				[
					'label' => 'Visited',
					'value' => 'visited',
				],
				[
					'label' => 'Re-visit Plan',
					'value' => 're-visit-plan',
				],
				[
					'label' => 'Re-visit Done',
					'value' => 're-visit-done',
				],
				[
					'label' => 'Cancelled',
					'value' => 'cancelled',
				],
				[
					'label' => 'Not Schedule',
					'value' => 'not_scheduled',
				],
			];

			return response()->json([
				'status' => true,
				'message' => 'Visit status options fetched successfully.',
				'data' => $visitStatusOptions,
			], 200);

		} catch (\Exception $e) {

			return response()->json([
				'status' => false,
				'message' => 'Something went wrong.',
				'error' => $e->getMessage(),
			], 500);
		}
	}
}