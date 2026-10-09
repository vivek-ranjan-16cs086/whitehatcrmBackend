<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\LeadService;
use App\Models\Lead;
use App\Models\User;
use Illuminate\Validation\ValidationException; 
use App\Services\NotificationService;   

class LeadController extends Controller
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
	
	public function index(){
		return view('admin.lead-management.index');
	}
	
	public function ajaxList(Request $request)
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

        $totalRecords = Lead::count();
        $totalRecordswithFilter = (clone $query)->count();

        if ($columnName === 'assigned_to') {
            $query->orderBy(
                User::select('name')->whereColumn('users.id', 'leads.assigned_to'),
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

            
            $oldStatus = $lead->status;
            $oldAssignedUserId = $lead->assigned_to;

            $assignedUserId = $validated['assigned_to'] ?? null;

            if ($assignedUserId && !is_numeric($assignedUserId)) {
                $assignedUserId = base64_decode($assignedUserId);
            }

            //UPDATE DATA
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
            if (auth()->user()->hasRole('salesperson') && $oldStatus !== $lead->status) {
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
				'source_website' => 'nullable|string|max:255',
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
			$lead->type = $validated['type'] ?? 'hot';
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
	
	
}