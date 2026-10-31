<?php

namespace Tests\Feature;

use App\Models\Lead;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class LeadTableColumnsTest extends TestCase
{
    use RefreshDatabase;

    public function test_lead_list_includes_project_inventory_and_visit_details_and_sorts_projects_by_name(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole(Role::create(['name' => 'super-admin', 'guard_name' => 'web']));
        foreach (['Zebra Estate', 'Alpha Estate'] as $name) {
            $project = Project::create(['name' => $name, 'email' => 'test@example.com', 'phone' => '1234567890']);
            $lead = Lead::create(['name' => $name, 'assigned_to' => $admin->id, 'inventory' => '3 BHK', 'visit_date' => '2026-10-31', 'visit_status' => 'scheduled']);
            $lead->forceFill(['project_id' => $project->id])->save();
        }
        $this->actingAs($admin)->getJson(route('lead-management.ajax-list', [
            'length' => 20, 'start' => 0, 'columns' => [['data' => 'project']],
            'order' => [['column' => 0, 'dir' => 'asc']],
        ]))->assertOk()->assertJsonPath('data.0.project', 'Alpha Estate')
            ->assertJsonPath('data.1.project', 'Zebra Estate')
            ->assertJsonPath('data.0.inventory', '3 BHK')
            ->assertJsonPath('data.0.visit_date', '31 Oct 2026')
            ->assertJsonPath('data.0.visit_status', 'Scheduled');
    }
}
