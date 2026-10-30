<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\LeadTimelineEvent;
use App\Models\User;
use App\Models\Lead;
use App\Models\LeadResponse;
use Carbon\Carbon;

class EventController extends Controller
{
	
	//add new comment
	public function addComment(Request $request, $leadId)
	{
		$user = auth()->user();
		$this->ensureLeadAccessible($leadId, $user);

		$request->validate([
			'message' => 'required|string'
		]);

		$event = LeadTimelineEvent::create([
			'lead_id' => $leadId,
			'user_id' => $user->id,
			'user_name' => $user->name,
			'user_role' => $user->getRoleNames()->first(),
			'event_type' => 'COMMENT',
			'message' => $request->message,
		]);

		return response()->json([
			'message' => 'Comment added successfully',
			'data' => [
				'id' => $event->id,
				'title' => $event->message,
				'user_name' => $event->user_name,
				'user_role' => $event->user_role,
				'type' => 'Remark',
				'created_at' => $event->created_at->format('d-M-Y H:i:s'),
			]
		]);
	}
	
	//get comments
	public function getComments($leadId)
	{
		$user = auth()->user();
		$this->ensureLeadAccessible($leadId, $user);
		$events = LeadTimelineEvent::where('lead_id', $leadId)
			->where('event_type', 'COMMENT')
			->when(!$user->hasRole('super-admin', 'web'), function ($query) use ($user) {
				$query->where('user_id', $user->id);
			})
			->latest()
			->get();

		return response()->json([
			'status' => true,
			'data' => $events->map(fn($event) => $this->timelineTransform($event))
		]);
	} 
	 
	public function getTimeline($leadId)
	{
		$user = auth()->user();
		$lead = Lead::find($leadId);
		abort_unless($lead, 404);

		$isSuperAdmin = $user->hasRole('super-admin', 'web');
		$eventsQuery = LeadTimelineEvent::where('lead_id', $leadId);

		if (!$isSuperAdmin) {
			$transferEvents = (clone $eventsQuery)
				->where('event_type', 'TRANSFER')
				->orderBy('id')
				->get(['id', 'assigned_to_user_id']);
			$userAssignments = $transferEvents
				->where('assigned_to_user_id', $user->id)
				->values();
			$isCurrentAssignee = (string) $lead->assigned_to === (string) $user->id;

			abort_unless(
				$isCurrentAssignee || $userAssignments->isNotEmpty() || $user->canViewAllLeads(),
				404
			);

			if ($userAssignments->isNotEmpty()) {
				$eventsQuery->where(function ($query) use ($user, $userAssignments, $transferEvents) {
					foreach ($userAssignments as $assignment) {
						$nextTransfer = $transferEvents->first(
							fn ($event) => $event->id > $assignment->id
						);

						$query->orWhere(function ($period) use ($user, $assignment, $nextTransfer) {
							$period->where(function ($periodEvents) use ($assignment, $nextTransfer, $user) {
								$periodEvents->where('id', '>=', $assignment->id)
									->when($nextTransfer, function ($periodEvents, $nextTransfer) {
										$periodEvents->where('id', '<', $nextTransfer->id);
									})
									->where('user_id', $user->id);
							})->orWhere('id', $assignment->id);
						});
					}
				});
			} else {
				$eventsQuery->where('user_id', $user->id);
			}
		}

		$events = $eventsQuery->latest()->get();

		return response()->json([
			'data' => $events->map(fn($event) => $this->timelineTransform($event))
		]);
	}
	 
	private function timelineTransform($event)
	{
		switch ($event->event_type) {
			case 'COMMENT':
				$type = 'Remark';
				$title = $event->message;
				break;

			case 'FOLLOW_UP':
				$type = 'Followup';
				$title = 'Follow up at ' . $event->follow_up_date;
				break;

			case 'CALL':
				$type = 'Call';
				$title = $event->call_outcome;
				break;

			case 'TRANSFER':
				$type = 'Transfer';
				$title = $event->message;
				break;
				
			case 'STATUS':
				$type = 'Status';
				$title = $event->message;
				break;

			case 'VISIT':
				$type = 'Visit';
				$title = $event->message;
				break;

			case 'TYPE':
				$type = 'Type';
				$title = $event->message;
				break;

			case 'PROJECT':
				$type = 'Project';
				$title = $event->message;
				break;	

			default:
				$type = 'Other';
				$title = $event->message;
		}

		return [
			'id' => $event->id, 
			'title' => $title,
			'type' => $type,
			'user_name' => $event->user_name,
			'user_role' => $event->user_role,
			'created_at' => $event->created_at->format('d-M-Y H:i:s'),
		];
	}
	
    // get follow up
    public function getFollowUps1($leadId)
	{
		$this->ensureLeadAccessible($leadId, auth()->user());
		$event = LeadTimelineEvent::where('lead_id', $leadId)
			->where('event_type', 'FOLLOW_UP')
			->latest()
			->first();

		if (!$event) {
			return response()->json(['data' => null]);
		}

		return response()->json([
			'data' => [
				'lead_id' => $event->lead_id,
				'user_id' => $event->user_id,
				'user_name' => $event->user_name,
				'user_role' => $event->user_role,
				'event_type' => $event->event_type,
				'follow_up_date' => $event->follow_up_date?->format('d M Y, h:i A'),
			]
		]);
	}
	
	public function getFollowUps($leadId)
	{
		$this->ensureLeadAccessible($leadId, auth()->user());
		$event = LeadTimelineEvent::where('lead_id', $leadId)
			->where('event_type', 'FOLLOW_UP')
			->latest()
			->first();

		if (!$event) {
			return response()->json(['data' => null]);
		}

		$followUpDate = null;

		if (!empty($event->follow_up_date)) {
			try {
				$followUpDate = Carbon::parse($event->follow_up_date)
					->format('d M Y, h:i A');
			} catch (\Exception $e) {
				$followUpDate = null;
			}
		}

		return response()->json([
			'data' => [
				'lead_id' => $event->lead_id,
				'user_id' => $event->user_id,
				'user_name' => $event->user_name,
				'user_role' => $event->user_role,
				'event_type' => $event->event_type,
				'follow_up_date' => $followUpDate,
			]
		]);
	}

	public function getCalls($leadId)
	{
		$user = auth()->user();
		$this->ensureLeadAccessible($leadId, $user);
		$events = LeadTimelineEvent::where('lead_id', $leadId)
			->where('event_type', 'CALL')
			->when(!$user->canViewAllLeads(), function ($query) use ($user) {
				$query->where('user_id', $user->id); 
			})
			->latest()
			->get();

		$data = $events->map(function ($event) {
			//fix
			$leadResponse = LeadResponse::where('lead_id', $event->lead_id)
				->where('user_id', $event->user_id)
				->where('created_at', '<=', $event->created_at) // responses up to this event's time
				->latest()
				->first();

			return [
				'lead_id'        => $event->lead_id,
				'user_id'        => $event->user_id,
				'user_name'      => $event->user_name,
				'user_role'      => $event->user_role,
				'event_type'     => $event->event_type,
				'call_outcome'   => $event->call_outcome,
				
				'message'        => $leadResponse?->note,
				'call_recording' => $leadResponse?->call_recording
					? asset('storage/' . $leadResponse->call_recording)
					: null,
				'created_at'     => $leadResponse
					? $leadResponse->created_at->format('d M Y, h:i A')
					: $event->created_at->format('d M Y, h:i A'), 
			];
		});

		return response()->json([
			'success' => true,
			'data'    => $data
		]);
	}

	private function ensureLeadAccessible($leadId, User $user): void
	{
		$query = Lead::whereKey($leadId);

		if (!$user->canViewAllLeads()) {
			$query->where('assigned_to', $user->id);
		}

		abort_unless($query->exists(), 404);
	}
		
	public function addCall(Request $request, $leadId)
	{
		$user = auth()->user();
		$this->ensureLeadAccessible($leadId, $user);

		$request->validate([
			'call_outcome' => 'required|string',
			'note' => 'nullable|string',
		]);

		// file upload
		$recordingPath = null;

		if ($request->hasFile('call_recording')) {
			$recordingPath = $request->file('call_recording')
				->store('call_recordings', 'public');
		}

		// timeline event
		$event = LeadTimelineEvent::create([
			'lead_id' => $leadId,
			'user_id' => $user->id,
			'user_name' => $user->name,
			'user_role' => $user->getRoleNames()->first(),
			'event_type' => 'CALL',
			'call_outcome' => $request->call_outcome,
		]);

		// lead response table me save
		   $response = LeadResponse::create([
			'lead_id' => $leadId,
			'user_id' => $user->id,
			'call_recording' => $recordingPath,
			'note' => $request->note,
		]);

		return response()->json([
			'success' => true,
			'data' => [
				'lead_id' => $event->lead_id,
				'call_outcome' => $event->call_outcome,
				'message' => $response->note,
				'call_recording' => $recordingPath
					? asset('storage/' . $recordingPath)
					: null,
			]
		]);
	}

}