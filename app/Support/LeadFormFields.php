<?php

namespace App\Support;

class LeadFormFields
{
    public const FIELDS = [
        'name' => ['Name', 'text'], 'phone' => ['Mobile', 'tel'], 'email' => ['Email', 'email'],
        'status' => ['Status', 'select'], 'deactivate_reason' => ['Deactivation reason', 'select'], 'type' => ['Lead type', 'select'],
        'source_website' => ['Source', 'select'], 'source_page_name' => ['Page', 'text'],
        'assigned_to' => ['Assigned To', 'select'], 'project_id' => ['Project', 'select'],
        'visit_status' => ['Visit status', 'select'], 'visit_date' => ['Visit date', 'date'],
        'follow_up_date' => ['Follow-up date and time', 'datetime-local'],
        'lead_source' => ['Lead source', 'select'], 'inventory' => ['Inventory', 'select'],
        'budget_min' => ['Minimum budget', 'number'], 'budget_max' => ['Maximum budget', 'number'],
        'message' => ['Message', 'textarea'], 'notes' => ['Notes', 'textarea'],
    ];
}
