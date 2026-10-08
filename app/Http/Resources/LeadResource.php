<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LeadResource extends JsonResource
{
    public function toArray(Request $request): array
    {
		
        return [
            'id' => $this->id,
            'name' => $this->name,
            'phone' => $this->phone,
            'email' => $this->email,
			'message' => $this->message,
            'status' => $this->status,
			'deactivate_reason' => $this->deactivate_reason,
            'follow_up_date' => $this->follow_up_date,
			'visitStatus' => $this->visit_status,
            'visitDate'   => $this->visit_date,
			'notes' => $this->whenLoaded('leadResponses', function () {
				return $this->leadResponses->map(function ($note) {
					return [
						'id' => $note->id,
						'user_id' => $note->user_id,
						'user_name' => $note->user?->name,
						'note' => $note->note,
						'created_at' => $note->created_at->format('d M Y, h:i A'),
					];
				});
			}),
			'lead_source' => $this->source_website,
			'budget' => $this->formatBudget($this->budget_min) . ' - ' . $this->formatBudget($this->budget_max),
			'inventory' => $this->inventory,
			'type' => $this->type,
            'assigned_to' => [
				'id'   => $this->assignedUser?->id,
				'name' => $this->assignedUser?->name,
			],
			'project' => [
				'id' => $this->project?->id,
				'name' => $this->project?->name,
			],
			'created_by' => [
				'id' => $this->createdBy?->id,
				'name' => $this->createdBy?->name,
			],
			'updated_by' => [
				'id' => $this->updatedBy?->id,
				'name' => $this->updatedBy?->name,	
		    ],
			'sub_response' => [
				'id' => optional($this->subResponse)->id,
				'name' => optional($this->subResponse)->name,
				'response' => optional(optional($this->subResponse)->response)->name
			],

			'created_at' => $this->created_at->format('d M Y'),

            'updated_at' => $this->updated_at->format('d M Y'),
			
        ];
    }
	
	private function formatBudget($amount)
	{
		if (empty($amount) || $amount <= 0) { 
			return null;
		}

		if ($amount >= 10000000) {
			return number_format($amount / 10000000, 2) . ' Cr';
		}

		return number_format($amount / 100000, 0) . ' Lac';
	}
}
