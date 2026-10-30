<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\LeadService;
use App\Models\Lead;
use App\Models\User;
use App\Models\Project;
use App\Models\SubResponse;
use App\Imports\AdminLeadsImport;
use Illuminate\Validation\ValidationException; 
use App\Services\NotificationService;   
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Maatwebsite\Excel\Facades\Excel;

class LeadController extends Controller
{
	private const MANUAL_LEAD_SOURCES = [
		'Facebook' => 'facebook.com',
		'Whitehat Realty' => 'whitehat.realty',
		'360PropGuide' => '360propguide.com',
	];

	private const EDITABLE_LEAD_FIELDS = [
		'name',
		'phone',
		'email',
		'message',
		'external_id',
		'status',
		'type',
		'source_website',
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

	protected $leadService;
	protected $notificationService;

    public function __construct(
    LeadService $leadService,
    NotificationService $notificationService
	){
		$this->leadService = $leadService;
		$this->notificationService = $notificationService;
	}
	

	//added
	
public function index(Request $request)
{
    $titles = [
        'total' => 'Total Leads',
        'active' => 'Active Leads',
        'follow-ups' => 'Follow Ups',
        'expired-follow-ups' => 'Expired Follow Ups',
        'booked' => 'Booked Leads',
    ];

    $view = $request->query('view', 'total');

    abort_unless(array_key_exists($view, $titles), 404);

// Get unique sources from the leads database
$leadSources = Lead::query()
    ->whereNotNull('source_website')
    ->where('source_website', '!=', '')
    ->distinct()
    ->orderBy('source_website')
    ->pluck('source_website');

    return view('admin.lead-management.index', [
        'selectedView' => $view,
        'pageTitle' => $titles[$view],
		'leadSources' => $leadSources,
        'manualLeadSources' => self::MANUAL_LEAD_SOURCES,
		'projects' => Project::query()->orderBy('name')->get(['id', 'name']),
		'subResponses' => SubResponse::query()->orderBy('name')->get(['id', 'name']),
    ]);
}
//...
	
	public function ajaxList1(Request $request)
    {
        $draw   = $request->draw;
        $start  = $request->start;
        $length = $request->length;

        $columnIndex     = $request->order[0]['column'] ?? 0;
        $columnName      = $request->columns[$columnIndex]['data'] ?? 'created_at';
        $columnSortOrder = $request->order[0]['dir'] ?? 'desc';

        $sortableColumns = [
            'name' => 'name',
            'phone' => 'phone',
            'email' => 'email',
            'message' => 'message',
            'source_website' => 'source_website',
            'source_page_name' => 'source_page_name',
            'status' => 'status',
            'type' => 'type',
            'assigned_to' => 'assigned_to',
            'follow_up_date' => 'follow_up_date',
            'created_at' => 'created_at',
        ];

        $columnName = $sortableColumns[$columnName] ?? 'created_at';
        $columnSortOrder = in_array($columnSortOrder, ['asc', 'desc'], true)
            ? $columnSortOrder
            : 'desc';

        $query = $this->leadService->getLeads($request)->with('assignedUser');
		//added
		
		$viewFilters = [
			'total' => null,
			'active' => 'active',
			'follow-ups' => 'follow_up',
			'expired-follow-ups' => 'follow_up_expired',
			'booked' => 'booked',
		];

		$view = $request->query('view', 'total');

		if (!array_key_exists($view, $viewFilters)) {
			return response()->json([
				'message' => 'Invalid lead view',
			], 422);
		}

		if ($viewFilters[$view] !== null) {
			$query->where('status', $viewFilters[$view]);
		}

		

        $totalRecords = Lead::count();
        $totalRecordswithFilter = (clone $query)->count();

        // if ($columnName === 'assigned_to') {
            // $query->orderBy(
                // User::select('name')->whereColumn('users.id', 'leads.assigned_to'),
                // $columnSortOrder
            // );
        // } else {
            // $query->orderBy($columnName, $columnSortOrder);
        // }
		$query->orderBy('id', 'desc');

		

        $records = $query
            ->skip($start)
            ->take($length)
            ->get();
		
        $data_arr = [];
        $sno = $start + 1;

        foreach ($records as $row) {
            $encodedId = base64_encode($row->id);

            $assignedUserId   = $row->assignedUser?->id ?? '';
            $assignedUserName = $row->assignedUser?->name ?? '';

            $action = "
                <button 
                    class='btn btn-xs btn-info editBtn rounded-circle text-light m-1'
                    data-id='{$row->id}'
                    data-name='" . e($row->name) . "'
                    data-phone='" . e($row->phone) . "'
                    data-email='" . e($row->email) . "'
                    data-message='" . e($row->message) . "'
                    data-source='" . e($row->source_website) . "'
                    data-status='" . e($row->status) . "'
					data-type='" . e($row->type) . "'
                    data-assigned='{$assignedUserId}'
                    data-assigned-name='" . e($assignedUserName) . "'
                    data-followup='{$row->follow_up_date}'
                >
                    <i class='bi-pencil'></i>
                </button>

                <a href='javascript:void(0)' onclick=\"return myConfirm('leads/delete/{$encodedId}')\" class='btn btn-xs btn-danger'>
                    <i class='bi-trash'></i>
                </a>
            ";

            $statusBadge = match ($row->status) {
                'new'       => "<span class='badge bg-success'>New</span>",
                'contacted' => "<span class='badge bg-primary'>Contacted</span>",
                'closed'    => "<span class='badge bg-dark'>Closed</span>",
                default     => "<span class='badge bg-secondary'>" . e(ucfirst($row->status)) . "</span>",
            };
			
			$typeBadge = match ($row->type) {
				'hot'  => "<span class='badge bg-danger'>Hot</span>",
				'warm' => "<span class='badge bg-warning text-dark'>Warm</span>",
				'cold' => "<span class='badge bg-primary'>Cold</span>",
				default => "<span class='badge bg-secondary'>" . e(ucfirst($row->type)) . "</span>",
			};

            $data_arr[] = [
                "id"             => $sno++,
                "name"           => e($row->name),
                "phone"          => e($row->phone),
                "email"          => e($row->email),
                "message"        => e(\Str::limit($row->message, 50)),
                "source_website" => e($row->source_website ?? '-'),
                "source_page_name" => e($row->source_page_name ?? '-'),
                "status"         => $statusBadge,
				"type"          =>  $typeBadge,
                "assigned_to"    => e($assignedUserName ?: 'Unassigned'),
                "follow_up_date" => $row->follow_up_date
                    ? date('d M Y', strtotime($row->follow_up_date))
                    : '-',
                "created_at"     => $row->created_at
                    ? date('d M Y H:i', strtotime($row->created_at))
                    : '-',
                "action"         => $action,
            ];
        }

        return response()->json([
            "draw"                 => intval($draw),
            "iTotalRecords"        => $totalRecords,
            "iTotalDisplayRecords" => $totalRecordswithFilter,
            "aaData"               => $data_arr
        ]);
    }
	
	
	public function ajaxList(Request $request)
	{
		$draw = (int) $request->input('draw', 0);
		$start = max(0, (int) $request->input('start', 0));
		$length = (int) $request->input('length', 10);

		// DataTables sorting parameters
		$columnIndex = $request->input('order.0.column');

		$columnName = $columnIndex !== null
			? $request->input("columns.$columnIndex.data")
			: null;

		$columnSortOrder = $request->input('order.0.dir', 'desc');

		// Allowed sortable columns
		$sortableColumns = [
			'name' => 'name',
			'phone' => 'phone',
			'email' => 'email',
			'message' => 'message',
			'source_website' => 'source_website',
			'source_page_name' => 'source_page_name',
			'status' => 'status',
			'type' => 'type',
			'assigned_to' => 'assigned_to',
			'follow_up_date' => 'follow_up_date',
			'created_at' => 'created_at',
		];

		$columnSortOrder = in_array(
			$columnSortOrder,
			['asc', 'desc'],
			true
		) ? $columnSortOrder : 'desc';

		// Base query
		$query = $this->leadService
			->getLeads($request)
			->with('assignedUser');

		// View filters
		$viewFilters = [
			'total' => null,
			'active' => 'active',
			'follow-ups' => 'follow_up',
			'expired-follow-ups' => 'follow_up_expired',
			'booked' => 'booked',
		];

		$view = $request->query('view', 'total');

		if (!array_key_exists($view, $viewFilters)) {
			return response()->json([
				'message' => 'Invalid lead view',
			], 422);
		}

		if ($viewFilters[$view] !== null) {
			$query->where('leads.status', $viewFilters[$view]);
		}


        // Date Range and Source filters
        $request->validate([
            'date_from' => 'nullable|date_format:Y-m-d',
            'date_to' => 'nullable|date_format:Y-m-d|after_or_equal:date_from',
            'source' => 'nullable|string|max:255',
        ]);

        // From date - lead creation date
        if ($request->filled('date_from')) {
            $query->whereDate(
                'leads.created_at',
                '>=',
                $request->input('date_from')
            );
        }

        // To date - lead creation date
        if ($request->filled('date_to')) {
            $query->whereDate(
                'leads.created_at',
                '<=',
                $request->input('date_to')
            );
        }

        // Source website
        if ($request->filled('source')) {
            $query->where(
                'leads.source_website',
                $request->input('source')
            );
        }
//...
		// Record counts
		$totalRecords = Lead::count();

		$totalRecordswithFilter = (clone $query)->count();

		// Sorting
		// Default: ID DESC
		$query->reorder('leads.id', 'desc');

		// Apply DataTables column sorting
		if ($columnName && isset($sortableColumns[$columnName])) {

			// Clear default ordering
			$query->reorder();

			if ($columnName === 'assigned_to') {

				// Sort by assigned user's name
				$query->orderBy(
					User::select('name')
						->whereColumn(
							'users.id',
							'leads.assigned_to'
						)
						->limit(1),
					$columnSortOrder
				);

			} else {

				$query->orderBy(
					'leads.' . $sortableColumns[$columnName],
					$columnSortOrder
				);
			}

			// Secondary sorting
			$query->orderBy('leads.id', 'desc');
		}

		// Pagination
		if ($length !== -1) {
			$query->skip($start)->take(max(0, $length));
		}

		$records = $query->get();

		$data_arr = [];
		$sno = $start + 1;

		foreach ($records as $row) {

			$encodedId = base64_encode((string) $row->id);

			$assignedUserId = $row->assignedUser?->id ?? '';
			$assignedUserName = $row->assignedUser?->name ?? '';

			// Action buttons
			$action = "
				<button
					class='btn btn-xs btn-info editBtn rounded-circle text-light m-1'
					data-id='{$row->id}'
					data-name='" . e($row->name) . "'
					data-phone='" . e($row->phone) . "'
					data-email='" . e($row->email) . "'
					data-message='" . e($row->message) . "'
					data-source='" . e($row->source_website) . "'
					data-status='" . e($row->status) . "'
					data-type='" . e($row->type) . "'
					data-assigned='{$assignedUserId}'
					data-assigned-name='" . e($assignedUserName) . "'
					data-followup='" . e($row->follow_up_date) . "'
				>
					<i class='bi-pencil'></i>
				</button>

				<a
					href='javascript:void(0)'
					onclick=\"return myConfirm('leads/delete/{$encodedId}')\"
					class='btn btn-xs btn-danger'
				>
					<i class='bi-trash'></i>
				</a>
			";

			// Status badge
			$statusBadge = match ($row->status) {
				'new' => "<span class='badge bg-success'>New</span>",
				'contacted' => "<span class='badge bg-primary'>Contacted</span>",
				'closed' => "<span class='badge bg-dark'>Closed</span>",
				default => "<span class='badge bg-secondary'>"
					. e(ucfirst($row->status ?? 'Unknown'))
					. "</span>",
			};

			// Lead type badge
			$typeBadge = match ($row->type) {
				'hot' => "<span class='badge bg-danger'>Hot</span>",
				'warm' => "<span class='badge bg-warning text-dark'>Warm</span>",
				'cold' => "<span class='badge bg-primary'>Cold</span>",
				default => "<span class='badge bg-secondary'>"
					. e(ucfirst($row->type ?? 'Unknown'))
					. "</span>",
			};


			// DataTables row
			$data_arr[] = [
				'id' => $sno++, 
				'name' => e($row->name),
				'phone' => e($row->phone),
				'email' => e($row->email),
				'message' => e(\Str::limit($row->message, 50)),
				'source_website' => e($row->source_website ?? '-'),
				'source_page_name' => $row->pageName,
				//added
				
//'source_page_name' => e(
   // Lead::resolveSourcePageName(
       // $row->message,
       // $row->source_website,
       // $row->source_page_name
   // ) ?? '-'
//),
//...

				'status' => $statusBadge,
				'type' => $typeBadge,
				'assigned_to' => e($assignedUserName ?: 'Unassigned'),
				'follow_up_date' => $row->follow_up_date
					? date('d M Y', strtotime($row->follow_up_date))
					: '-',
				'created_at' => $row->created_at
					? date('d M Y H:i', strtotime($row->created_at))
					: '-',
				'edit_data' => array_intersect_key(
					$row->getAttributes(),
					array_flip(self::EDITABLE_LEAD_FIELDS)
				),
				'assigned_user_name' => $assignedUserName,
				'action' => $action,
			];
		}

		// DataTables response
		return response()->json([
			'draw' => $draw,
			'recordsTotal' => $totalRecords,
			'recordsFiltered' => $totalRecordswithFilter,
			'data' => $data_arr,

			// Legacy DataTables compatibility
			'iTotalRecords' => $totalRecords,
			'iTotalDisplayRecords' => $totalRecordswithFilter,
			'aaData' => $data_arr,
		]);
	}


	
	public function updateDetails1(Request $request, NotificationService $notificationService)
	{
		try { 
			$validated = $request->validate([ 
				'id'             => 'required|integer|exists:leads,id',
				'name'           => 'required|string|max:255',
				'phone'          => 'nullable|string|max:20',
				'email'          => 'nullable|email|max:255',
				'message'        => 'nullable|string',
				'source_website' => 'required|string|max:255',
				'status'         => 'nullable|string|max:50',
				'type'           => 'nullable|string|max:50',
				'assigned_to'    => 'nullable',
				'follow_up_date' => 'nullable|date',
				'notes'          => 'nullable|string',
			]);

			$lead = Lead::find($validated['id']);

			if (!$lead) {
				return response()->json([
					'success' => false,
					'message' => 'Lead not found.'
				], 404);
			}

			$oldAssignedUserId = $lead->assigned_to;
            $assignedUserId = $validated['assigned_to'] ?? null;
			if ($assignedUserId && !is_numeric($assignedUserId)) {
				$assignedUserId = base64_decode($assignedUserId);
			}


			$lead->name = $validated['name'];
			$lead->phone = $validated['phone'] ?? null;
			$lead->email = $validated['email'] ?? null;
			$lead->message = $validated['message'] ?? null;
			$lead->source_website = $validated['source_website'];
			$lead->status = $validated['status'] ?? $lead->status ?? 'new';
			$lead->type = $validated['type'] ?? $lead->type ?? 'hot';
			$lead->assigned_to = $assignedUserId;
			$lead->follow_up_date = $validated['follow_up_date'] ?? null;
			$lead->notes = $validated['notes'] ?? null;

			$lead->save();

			
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
				'success' => true,
				'message' => 'Lead updated successfully.',
				'data'    => $lead
			], 200);

		} catch (ValidationException $e) {
			return response()->json([
				'success' => false,
				'message' => 'Validation failed.',
				'errors'  => $e->errors()
			], 422);

		} catch (\Exception $e) {
			return response()->json([
				'success' => false,
				'message' => 'Something went wrong while updating the lead.',
				'error'   => $e->getMessage()
			], 500);
		}
	}
	
	public function updateDetails(Request $request, NotificationService $notificationService)
    {
        try { 
			$validatedId = $request->validate([
				'id' => 'required|integer|exists:leads,id',
			]);
			$lead = Lead::findOrFail($validatedId['id']);

            $user = $request->user();
            if (
                !$user->canViewAllLeads() &&
                (string) $lead->assigned_to !== (string) $user->id
            ) {
                return response()->json([
                    'success' => false,
                    'message' => 'Lead not found.'
                ], 404);
            }

			$availableColumns = array_flip(Schema::getColumnListing('leads'));
			$fieldRules = [
				'name' => 'required|string|max:255',
				'phone' => 'nullable|string|max:20',
				'email' => 'nullable|email|max:255',
				'message' => 'nullable|string',
				'external_id' => 'nullable|integer',
				'status' => ['nullable', 'string', Rule::in([
					'not_available',
					'active',
					'booked',
					'deactive',
					'follow_up',
					'follow_up_expired',
					'follow_up_complete',
				])],
				'type' => ['nullable', 'string', Rule::in(['hot', 'warm', 'cold'])],
				'source_website' => [
					'required',
					'string',
					'max:255',
					Rule::in(array_unique([
						...array_values(self::MANUAL_LEAD_SOURCES),
						(string) $lead->source_website,
					])),
				],
				'assigned_to' => 'nullable|integer|exists:users,id',
				'follow_up_date' => 'nullable|date',
				'notes' => 'nullable|string',
				'visit_status' => ['nullable', 'string', Rule::in([
					'not_scheduled',
					'scheduled',
					'completed',
				])],
				'visit_date' => 'nullable|date',
				'sub_response_id' => 'nullable|integer|exists:sub_responses,id',
				'project_id' => 'nullable|integer|exists:projects,id',
				'deactivate_reason' => ['nullable', 'string', Rule::in([
					'Plot or Villa',
					'Budget',
					'Location',
					'Ready To Move',
					'Rent',
					'Invalid Mobile Number',
					'Others',
					'Switch Off',
				])],
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
			];
			$rules = array_intersect_key($fieldRules, $availableColumns);
			$validated = $request->validate([
				'id' => 'required|integer|exists:leads,id',
				...$rules,
			]);

            $oldStatus = $lead->status;
            $oldAssignedUserId = $lead->assigned_to;

            $assignedUserId = $validated['assigned_to'] ?? null;

			foreach (self::EDITABLE_LEAD_FIELDS as $field) {
				if (array_key_exists($field, $validated) && isset($availableColumns[$field])) {
					$lead->setAttribute(
						$field,
						$field === 'assigned_to' ? $assignedUserId : $validated[$field]
					);
				}
			}

            $lead->assigned_to = $assignedUserId;

            $lead->save();

            
            // ASSIGNMENT NOTIFICATION
            if (!empty($assignedUserId) && (string)$oldAssignedUserId !== (string)$assignedUserId) {
                try {
                    $notificationService->sendLeadAssignedNotification($assignedUserId, $lead);
                } catch (\Exception $e) {
                    \Log::error('Assignment Notification Error', [
                        'error' => $e->getMessage()
                    ]);
                }
            }

           //admin notification
            if (!$user->hasCrmPermission('edit.lead') && $oldStatus !== $lead->status) {
				try {
					$notificationService->sendLeadStatusUpdateNotification($lead, $oldStatus);
				} catch (\Exception $e) {
					\Log::error('Super Admin Notification Error', [
						'error' => $e->getMessage()
					]);
				}
			}

            return response()->json([
                'success' => true,
                'message' => 'Lead updated successfully.',
                'data'    => $lead
            ], 200);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors'  => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Something went wrong while updating the lead.',
                'error'   => $e->getMessage()
            ], 500);
        }
    }
	
	public function store(Request $request, NotificationService $notificationService)
	{
		try { 
			$validated = $request->validate([ 
				'name'           => 'required|string|max:255',
				'phone'          => 'required|string|max:20',
				'email'          => 'nullable|email|max:255',
				'message'        => 'nullable|string',
				'source_website' => ['required', 'string', 'max:255', Rule::in(array_values(self::MANUAL_LEAD_SOURCES))],
				'status'         => 'nullable|string|max:50',
				'type'           => 'nullable|string|max:50',
				'assigned_to'    => 'nullable',
				'follow_up_date' => 'nullable|date',
				'notes'          => 'nullable|string',
			]);

			$assignedUserId = $validated['assigned_to'] ?? null;
			if ($assignedUserId && !is_numeric($assignedUserId)) {
				$assignedUserId = base64_decode($assignedUserId);
			}

			$lead = new Lead();
			$lead->name = $validated['name'];
			$lead->phone = $validated['phone'];
			$lead->email = $validated['email'] ?? null;
			$lead->message = $validated['message'] ?? null;
			$lead->source_website = $validated['source_website'] ?? 'Manual';
			$lead->status = $validated['status'] ?? 'new';
			// $lead->type = $validated['type'] ?? 'hot';
			//added
			
$lead->type = !empty($validated['type']) ? $validated['type'] : 'cold';

			$lead->assigned_to = $assignedUserId;
			$lead->follow_up_date = $validated['follow_up_date'] ?? null;
			$lead->notes = $validated['notes'] ?? null;

			$lead->save();

			// If assignment changed → send notification
			if (!empty($assignedUserId)) {
				try {
					$notificationService->sendLeadAssignedNotification($assignedUserId, $lead);
				} catch (\Exception $e) {
					\Log::error('Notification error', [
						'error' => $e->getMessage()
					]);
				}
			}

			return response()->json([
				'status' => true,
				'message' => 'Lead created successfully',
				'data'    => $lead
			], 200);

		} catch (ValidationException $e) {
			return response()->json([
				'status' => false,
				'message' => 'Validation failed.',
				'errors'  => $e->errors()
			], 422);

		} catch (\Exception $e) {
			return response()->json([
				'status' => false,
				'message' => 'Something went wrong while creating the lead.',
				'error'   => $e->getMessage()
			], 500);
		}
	}

	public function import(Request $request)
	{
		$validated = $request->validate([
			'file' => 'required|file|mimes:csv,txt|max:10240',
			'source_website' => ['required', 'string', Rule::in(array_values(self::MANUAL_LEAD_SOURCES))],
		]);

		try {
			DB::transaction(function () use ($request, $validated): void {
				Excel::import(
					new AdminLeadsImport($validated['source_website']),
					$request->file('file')
				);
			});

			return response()->json([
				'status' => true,
				'message' => 'Leads imported successfully.',
			]);
		} catch (ValidationException $e) {
			return response()->json([
				'status' => false,
				'message' => $e->errors()['file'][0] ?? 'The uploaded CSV contains invalid data.',
				'errors' => $e->errors(),
			], 422);
		} catch (\Throwable $e) {
			\Log::error('Admin lead CSV import failed', [
				'error' => $e->getMessage(),
				'user_id' => $request->user()?->id,
			]);

			return response()->json([
				'status' => false,
				'message' => 'Could not import the CSV file. No leads were imported.',
			], 500);
		}
	}

	public function downloadImportTemplate()
	{
		return response()->download(resource_path('templates/lead-import-template.csv'), 'lead-import-template.csv', [
			'Content-Type' => 'text/csv; charset=UTF-8',
		]);
	}
	
	
}