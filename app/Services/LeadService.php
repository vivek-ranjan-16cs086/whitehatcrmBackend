<?php
namespace App\Services;

use App\Models\Lead;

class LeadService
{
   public function getLeads($request)
    {
        $query = Lead::query();
		$query = $query->with('assignedUser');
        // Global Search
        if (!empty($request->search['value'])) {
            $searchValue = $request->search['value'];

            $query->where(function ($q) use ($searchValue) {
                $q->where('name', 'like', "%$searchValue%")
                  ->orWhere('phone', 'like', "%$searchValue%")
                  ->orWhere('email', 'like', "%$searchValue%")
                  ->orWhere('message', 'like', "%$searchValue%")
                  ->orWhere('source_website', 'like', "%$searchValue%")
                  ->orWhere('status', 'like', "%$searchValue%")
				  ->orWhere('type', 'like', "%$searchValue%")
                  ->orWhere('created_at', 'like', "%$searchValue%");
            });
        }

        return $query;
    }
}